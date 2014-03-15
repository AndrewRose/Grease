<?php

class Grease_Console implements Grease_Plugin
{
	private $grease;
	private $parent;

	public $consoleWindow = FALSE;
	public $consoleTextctrl = FALSE;

	public function init($grease)
	{
		$this->grease = $grease;
		return ['parent' => 'bottom'];
	}

	public function initMenuItems()
	{
			return ['console' => ['name' => 'Console', 'description' => 'Console', 'callback' => 'onConsoleShow']];
	}

	public function initViews($parent, $sizer)
	{
		$this->parent = $parent;
		$this->consoleWindow = new wxPanel($parent, wxID_ANY, wxDefaultPosition, new wxSize(-1, 300), wxTAB_TRAVERSAL, 'Console');
		$this->consoleWindow->Hide();

		$sizer = new wxBoxSizer(wxVERTICAL);

		$this->consoleTextctrl = new wxStyledTextCtrl($this->consoleWindow, wxID_ANY);
		$this->consoleTextctrl->SetReadOnly(TRUE);
		$this->consoleTextctrl->StyleSetForeground (wxSTC_STYLE_LINENUMBER, new wxColour (75, 75, 75) );
		$this->consoleTextctrl->StyleSetBackground (wxSTC_STYLE_LINENUMBER, new wxColour (220, 220, 220));
		$this->consoleTextctrl->SetMarginType (0, wxSTC_MARGIN_NUMBER);

		$this->consoleTextentry = new wxTextCtrl($this->consoleWindow, wxID_ANY, wxEmptyString, wxDefaultPosition, new wxSize(-1, 25), wxTE_PROCESS_ENTER);
		$this->consoleTextentry->SetMaxSize(new wxSize(-1, 25));
		$this->consoleTextentryCmdHistoryIdx = -1;
		$this->consoleTextentryCmdHistory = [];

		$this->consoleTextentry->Connect(wxID_ANY, wxEVT_KEY_DOWN, [$this, 'onConsoleCommandTextEntry']);


		$sizer->Add($this->consoleTextctrl, 1, wxALL|wxEXPAND, 5);
		$sizer->Add($this->consoleTextentry, 1, wxALL|wxEXPAND, 5);

		$this->consoleWindow->SetSizer($sizer);
	}

	public function onConsoleCommandTextEntry($ev)
	{

		$keycode = $ev->GetKeyCode();
		if($keycode == 315) // up
		{
			if($this->consoleTextentryCmdHistoryIdx==-1)
			{
				$this->consoleTextentryCmdHistoryIdx = sizeof($this->consoleTextentryCmdHistory)-1;
			}
			else if($this->consoleTextentryCmdHistoryIdx>0)
			{
				$this->consoleTextentryCmdHistoryIdx--;
			}

			$this->consoleTextentry->Replace(0, strlen($this->consoleTextentryCmdHistory[$this->consoleTextentryCmdHistoryIdx]),  $this->consoleTextentryCmdHistory[$this->consoleTextentryCmdHistoryIdx]);
		}
		else if($keycode == 317) // down
		{
			if($this->consoleTextentryCmdHistoryIdx < sizeof($this->consoleTextentryCmdHistory)-1)
			{
				$this->consoleTextentryCmdHistoryIdx++;
			}

			$this->consoleTextentry->Replace(0, strlen($this->consoleTextentryCmdHistory[$this->consoleTextentryCmdHistoryIdx]),  $this->consoleTextentryCmdHistory[$this->consoleTextentryCmdHistoryIdx]);
		}
		else if($keycode == 13)
		{
			$cmd = $this->consoleTextentry->GetLineText(0);
			$this->consoleTextentryCmdHistory[] = $cmd;
			$this->consoleTextentryCmdHistoryIdx = -1;

			ob_start();
			eval($cmd);
			$ret = ob_get_contents();
			ob_end_clean();

			$this->consoleTextctrl->SetReadOnly(FALSE);
			$this->consoleTextctrl->AppendText('>>> '.$cmd."\n".$ret."\n");
			$this->consoleTextctrl->SetReadOnly(TRUE);
			$this->consoleTextentry->Clear();
	//		$this->consoleTextctrl->ScrollToEnd();

			$lineCount = substr_count($ret, "\n")+2;
			while($lineCount--)
			{
				$this->consoleTextctrl->LineScrollDown();
			}
		}
		else
		{
			$ev->Skip();
		}
	}

	public function onConsoleShow($ev)
	{
		if($this->consoleWindow->IsShownOnScreen())
		{
			$this->consoleWindow->Show(FALSE);
			$this->grease->secondarySplitterWindow->Unsplit($this->consoleWindow);
		}
		else
		{
			$this->consoleWindow->Show(TRUE);
			$this->grease->secondarySplitterWindow->SplitHorizontally($this->grease->primarySplitterWindow, $this->consoleWindow);
		}
	}
}