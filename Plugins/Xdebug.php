<?php

class Grease_Xdebug implements Grease_Plugin
{
	private $grease;
	private $parent;

	private $xdebug;
	private $debugContext;
	private $debugContextRoot;
	private $debugStack;
	private $activeDebugSession = FALSE;

	public function init($grease)
	{
		$this->grease = $grease;
		$this->xdebug = new libxdebugd();
		$this->xdebug->connect();
		return ['parent' => 'left'];
	}

	public function onMarginClick($ev)
	{
		$bufferId = $this->grease->getTabSelected();
		$lineNumber = (int)$this->grease->buffers[$bufferId]['textctrl']->LineFromPosition($ev->GetPosition());

echo 'linenumber: '.$lineNumber."\n";
print_r($this->grease->buffers[$bufferId]['breakpoints']);
echo '---';

		if(!isset($this->grease->buffers[$bufferId]['breakpoints'][$lineNumber]))
		{
			$this->grease->buffers[$bufferId]['textctrl']->MarkerAdd($lineNumber, 0);
			$this->grease->buffers[$bufferId]['breakpoints'][$lineNumber] = $this->onDebugBreakpointSetLine($this->grease->buffers[$bufferId]['realpath'], $lineNumber+1);
echo 'set breakpoint: '.$lineNumber."\n";

		}
		else
		{
echo 'unset breakpoint: '.$lineNumber."\n";
			$this->grease->buffers[$bufferId]['textctrl']->MarkerDelete($lineNumber, 0);
			$this->onDebugBreakpointRemoveLine($this->grease->buffers[$bufferId]['breakpoints'][$lineNumber]);
			unset($this->grease->buffers[$bufferId]['breakpoints'][$lineNumber]);
		}
	}

	public function initMenuItems()
	{
		return [
			'debug_run' => ['name' => 'Debug - Start / Run', 'description' => 'Debug - Start / Run', 'callback' => 'onDebugStart'],
			'debug_step_into' => ['name' => 'Debug - Step Into', 'description' => "Debug - Step Into\nSteps to the next statement, if there is a function call involved it will break on the first statement in that function", 'callback' => 'onDebugStepInto'],
			/*'debug_step_out' => ['name' => 'Debug - Step Out', 'description' => "Debug - Step Out\nSteps out of the current scope and breaks on the statement after returning from the current function", 'callback' => 'onDebugStepOut'],
			'debug_step_over' => ['name' => 'Debug - Step Over', 'description' => "Debug - Step Over\nSteps to the next statement, if there is a function call on the line from which the step_over is issued then the debugger engine will stop at the statement after the function call in the same scope as from where the command was issued", 'callback' => 'onDebugStepOver'],*/
			'debug_stop' => ['name' => 'Debug - Stop', 'description' => 'Debug - Stop', 'callback' => 'onDebugStop']
		];
	}

	public function initViews($parent, $sizer)
	{
		$this->parent = $parent;
		$this->debugContext = new wxTreeCtrl($parent, wxID_ANY, wxDefaultPosition, wxDefaultSize, wxTR_HIDE_ROOT|wxTR_HAS_BUTTONS);
		$this->debugContextRoot = $this->debugContext->AddRoot('wxphp');
		$this->debugStack = new wxListCtrl ($parent, wxID_ANY, wxDefaultPosition, wxDefaultSize, wxLC_LIST);

		$sizer->Add($this->debugContext, 1, wxALL|wxEXPAND, 5);
		$sizer->Add($this->debugStack, 1, wxALL|wxEXPAND, 5);

		$this->debugContext->Hide();
		$this->debugStack->Hide();
	}

	public function onDebugStart()
	{
		if(!$this->xdebug->connected)
		{
			$this->xdebug->connect();
			if(!$this->xdebug->connected)
			{
				wxMessageBox('Failed to connect to xdebugd!', 'Error');
				return;
			}
		}

		if(!$this->activeDebugSession)
		{
			$messageDialog = new wxMessageDialog($this->grease, 'Run and debug this tab?', 'Query', wxYES_NO);
			$resp = $messageDialog->ShowModal();
			if($resp == wxID_YES)
			{
				$id = $this->grease->getTabSelected();
				$key = rand();
				$file = $this->grease->buffers[$id]['file'];
// TODO handle configuration options for xdebug from config..
				exec('php -d xdebug.remote_host='.$this->grease->settings['xdebug']['host'].' -d xdebug.remote_port='.$this->grease->settings['xdebug']['port'].' -d xdebug.remote_autostart=1 -d xdebug.idekey='.$key.' '.$file.' > /dev/null &');
// As the process is sent to the background we need to keep poking xdebugd to see if the process has started and registered.
				$retries = 10;
				while($retries--)
				{
					if($this->xdebug->init($key))
					{
						break;
					}
					usleep(100000);
				}

				if($retries)
				{
					$this->activeDebugSession = $key;
					if(!$this->debugContext->IsShown())
					{
						$this->debugShowPanels();
					}
					$this->debugHonorBreakpoints();
				}
				return;
			}

			$data = json_decode($this->xdebug->getSessions(), TRUE);

			if(!is_array($data) || empty($data))
			{
				wxMessageBox('No active debugging sessions available', 'Error');
				return;
			}

			$sessions = array_keys($data, TRUE);
			$messageDialog = new wxSingleChoiceDialog($this->grease, 'Select session', 'Select active debugging session', count($sessions), $sessions, NULL, wxCHOICEDLG_STYLE);
			$resp = $messageDialog->ShowModal();

			if($resp == wxID_CANCEL)
			{
				return;
			}

			if(!$this->xdebug->init($messageDialog->GetStringSelection()))
			{
				return;
			}

			if(!$this->debugContext->IsShown())
			{
				$this->debugShowPanels();
			}

			$this->activeDebugSession = $messageDialog->GetStringSelection();

			$this->debugHonorBreakpoints(); // setup breakpoints that where defined before the session was started.
		}
		else
		{
			$data = json_decode($this->xdebug->run($this->activeDebugSession), TRUE);

			if($data == 0)
			{
				wxMessageBox('Script being debugged has ended unexpectedly!', 'Error');
				$this->onDebugStop();
				return FALSE;
			}

			if($data['status'] == 'break')
			{
				$this->debugOpenFileAndOrMoveToLine($data['filename'], $data['lineno']);
				$this->debugContext->DeleteChildren($this->debugContextRoot);
				$this->debugStack->DeleteAllItems();
				$this->scanDebugStack(json_decode($this->xdebug->getStack($this->activeDebugSession), TRUE));
				$this->scanDebugContext($this->debugContextRoot, json_decode($this->xdebug->getContext($this->activeDebugSession), TRUE));
				$this->debugOpenFileAndOrMoveToLine($data['filename'], $data['lineno']);

			}
			else if($data['status'] == 'stopping')
			{
				$this->xdebug->stop($this->activeDebugSession);
				$this->activeDebugSession = FALSE;
			}
		}
	}

	public function debugHonorBreakpoints()
	{
		foreach($this->grease->buffers as $bufferId => $buffer)
		{
			foreach($buffer['breakpoints'] as $lineNumber => $breakpoint)
			{
				if(!$breakpoint)
				{
echo 'Honoring breakpoint: '.$buffer['realpath'].':'.$lineNumber."\n";
					$this->grease->buffers[$bufferId]['breakpoints'][$lineNumber] = $this->onDebugBreakpointSetLine($buffer['realpath'], $lineNumber+1);
				}
			}
		}
	}

	public function onDebugBreakpointSetLine($filename, $lineno)
	{
		if(!$this->xdebug->connected || !$this->activeDebugSession)
		{
			return FALSE;
		}

		return $this->xdebug->breakpointSetLine($this->activeDebugSession, $filename, $lineno);
	}

	public function onDebugBreakpointRemoveLine($breakpointId)
	{
		if(!$this->xdebug->connected || !$this->activeDebugSession)
		{
			return FALSE;
		}

		$this->xdebug->breakpointRemoveLine($this->activeDebugSession, $breakpointId);
	}
	
	public function onDebugStop()
	{
		if($this->debugContext->IsShown())
		{
			$this->debugHidePanels();
		}

		if(!$this->xdebug->connected)
		{
			wxMessageBox('Not connected to xdebugd!', 'Error');
			return FALSE;
		}

		if(!$this->activeDebugSession)
		{
			wxMessageBox('No active debug session running!', 'Error');
			return FALSE;
		}

		$this->xdebug->stop($this->activeDebugSession);
		$this->activeDebugSession = FALSE;

		foreach($this->grease->readonlyBuffers as $bufferId)
		{
			$this->grease->buffers[$bufferId]['textctrl']->SetReadOnly(FALSE);

// TODO - figure out how to handle breakpoints when lines change..
/*			foreach($this->grease->buffers[$bufferId]['breakpoints'] as $lineNumber => $breakpointId)
			{
				$this->grease->buffers[$bufferId]['textctrl']->MarkerDelete($lineNumber, 0);
			}

			$this->grease->buffers[$bufferId]['breakpoints'] = [];
*/
		}
	}

	public function scanDebugContext($parentNode, $tree)
	{
		if(is_array($tree))
		{
			foreach($tree as $node => $props)
			{
				if($props['type'] == 'array' || $props['type'] == 'object')
				{
					$nodeId = $this->debugContext->AppendItem($parentNode, $node.' = '.$props['type']);
					if(isset($props['properties']))
					{
						$this->scanDebugContext($nodeId, $props['properties']);
					}
				}
				else
				{
					$this->debugContext->AppendItem($parentNode, $node.' = '.$props['value']);
				}
			}
		}
	}

	public function scanDebugStack($stack)
	{
		// where,	level, type, filename, lineno
		foreach($stack as $idx => $frame)
		{
			$this->debugStack->InsertItem($idx, basename(str_replace('file://', '', $frame['filename'])).':'.$frame['lineno'].' -> '.$frame['where']);
		}
	}

	public function debugOpenFileAndOrMoveToLine($filename, $lineno)
	{
		if(!isset($this->grease->filesOpen[str_replace('file://', '', $filename)]))
		{
			$filename = str_replace('file://', '', $filename);
			$bufferId = $this->grease->addTab(basename($filename), $filename, TRUE);
			$this->grease->filesOpen[$filename] = $bufferId;
			$this->grease->readonlyBuffers[] = $bufferId;
			$this->grease->buffers[$bufferId]['textctrl']->SetReadOnly(TRUE);
		}
		else
		{
			$bufferId = $this->grease->buffers[$this->grease->filesOpen[str_replace('file://', '', $filename)]]['id'];
echo 'debug: '.$bufferId."\n";
			$this->grease->notebook->SetSelection($this->grease->buffers[$bufferId]['position']);
			$this->grease->readonlyBuffers[] = $bufferId;
			$this->grease->buffers[$bufferId]['textctrl']->SetReadOnly(TRUE);
		}

		$lineno = $lineno-1;

		$this->grease->buffers[$bufferId]['textctrl']->ScrollToLine($lineno);
		$this->grease->buffers[$bufferId]['textctrl']->GoToLine($lineno);

		$startPos = $this->grease->buffers[$bufferId]['textctrl']->PositionFromLine($lineno);
		$endPos = $this->grease->buffers[$bufferId]['textctrl']->GetLineEndPosition($lineno);
echo 'start: '.$startPos.', end: '.$endPos."\n";
$this->grease->buffers[$bufferId]['textctrl']->SetSelBackground(TRUE, wxRED);
		$this->grease->buffers[$bufferId]['textctrl']->SetSelection($startPos, $endPos);

$this->grease->buffers[$bufferId]['textctrl']->SetSelBackground(TRUE, wxWHITE);

		//$buffer['textctrl']->SetCurrentPos($buffer['textctrl']->GetLineSelStartPosition($lineno));

		/*if($this->grease->buffers[$bufferId]['debugMarkerPosition'])
		{
echo 'deleting marker: '.$this->grease->buffers[$bufferId]['debugMarkerPosition']."\n";
			$this->grease->buffers[$bufferId]['textctrl']->MarkerDelete($this->grease->buffers[$bufferId]['debugMarkerPosition']-1, 1);
		}
		$this->grease->buffers[$bufferId]['textctrl']->MarkerAdd($lineno, 1);
		$this->grease->buffers[$bufferId]['debugMarkerPosition'] = $lineno-1;*/
	}

	public function onDebugStepInto()
	{
		if(!$this->xdebug)
		{
			wxMessageBox('Not connected to xdebugd!', 'Error');
			return;
		}

		if(!$this->activeDebugSession)
		{
			wxMessageBox('No active debug session running!', 'Error');
			return;
		}


		$this->debugContext->DeleteChildren($this->debugContextRoot);
		$this->debugStack->DeleteAllItems();

		$data = json_decode($this->xdebug->stepInto($this->activeDebugSession), TRUE);
//echo 'data:';
//print_r($data);
		if($data == 0)
		{
			wxMessageBox('Script being debugged has ended unexpectedly!', 'Error');
			$this->onDebugStop();
			return FALSE;
		}

		$this->scanDebugStack(json_decode($this->xdebug->getStack($this->activeDebugSession), TRUE));
		$this->scanDebugContext($this->debugContextRoot, json_decode($this->xdebug->getContext($this->activeDebugSession), TRUE));
		$this->debugOpenFileAndOrMoveToLine($data['filename'], $data['lineno']);
	}

	public function onDebugStepOver()
	{

	}

	public function onDebugStepOut()
	{

	}

	public function debugShowPanels()
	{
		$this->debugContext->Show();
		$this->debugStack->Show();
		$this->parent->Layout();
	}

	public function debugHidePanels()
	{
		$this->debugContext->Hide();
		$this->debugStack->Hide();
		$this->parent->Layout();
	}
}
