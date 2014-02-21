#!/usr/bin/php
<?php

/*
 This file is part of Grease
 http://github.com/AndrewRose/Grease
 http://andrewrose.co.uk
 License: GPL; see below
 Copyright Andrew Rose (hello@andrewrose.co.uk) 2014

    Grease is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Grease is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Grease.  If not, see <http://www.gnu.org/licenses/>
*/

class Grease extends wxFrame
{
	public $tree;
	public $treeRoot;
	public $notebook;
	public $buffers = [];
	public $keyboardState = FALSE;
	public $currentSearchPos = FALSE;

	public $settings =
	[

		"theme" => "grease",
		"font-size" => 10,
		"font-face" => "Courier New",

		"projects" =>
		[

		],

		"themes" =>
		[
			"grease" =>
			[
				"background-color" => "0xC0C0C0",
				"wxSTC_STYLE_DEFAULT" => "0x1E5A14",
				"wxSTC_HPHP_DEFAULT" => "0x1E5A14",
				"wxSTC_HPHP_HSTRING" => "0x1E5A14",
				"wxSTC_HPHP_SIMPLESTRING" => "0x1E5A14",
				"wxSTC_HPHP_WORD" => "0x1E5A14",
				"wxSTC_HPHP_NUMBER" => "0x1E5A14",
				"wxSTC_HPHP_VARIABLE" => "0x1E5A14",
				"wxSTC_HPHP_COMMENT" => "0x1E5A14",
				"wxSTC_HPHP_COMMENTLINE" => "0x1E5A14",
				"wxSTC_HPHP_HSTRING_VARIABLE" => "0x1E5A14",
				"wxSTC_HPHP_OPERATOR" => "0x1E5A14"
			]
		]
	];
	public $theme = FALSE;

	public $styles =
	[
		"php" =>
		[
			"wxSTC_STYLE_DEFAULT" => wxSTC_STYLE_DEFAULT,
			"wxSTC_HPHP_DEFAULT" => wxSTC_HPHP_DEFAULT,
			"wxSTC_HPHP_HSTRING" => wxSTC_HPHP_HSTRING,
			"wxSTC_HPHP_SIMPLESTRING" => wxSTC_HPHP_SIMPLESTRING,
			"wxSTC_HPHP_WORD" => wxSTC_HPHP_WORD,
			"wxSTC_HPHP_NUMBER" => wxSTC_HPHP_NUMBER,
			"wxSTC_HPHP_VARIABLE" => wxSTC_HPHP_VARIABLE,
			"wxSTC_HPHP_COMMENT" => wxSTC_HPHP_COMMENT,
			"wxSTC_HPHP_COMMENTLINE" => wxSTC_HPHP_COMMENTLINE,
			"wxSTC_HPHP_HSTRING_VARIABLE" => wxSTC_HPHP_HSTRING_VARIABLE,
			"wxSTC_HPHP_OPERATOR" => wxSTC_HPHP_OPERATOR
		]
	];

	public $searchWindow = FALSE;

	public function onMarginClick($ev)
	{
//var_dump($ev);
//echo 'margin click: '.$ev->GetPosition()."\n";

		$lineNumber = $this->buffers[$this->getTabSelected()]['textctrl']->LineFromPosition($ev->GetPosition());

		if(!isset($this->buffers[$this->getTabSelected()]['breakpoints'][$lineNumber]))
		{
			$this->buffers[$this->getTabSelected()]['textctrl']->MarkerAdd($lineNumber, 0);
			$this->buffers[$this->getTabSelected()]['breakpoints'][$lineNumber] = TRUE;
		}
		else
		{
			$this->buffers[$this->getTabSelected()]['textctrl']->MarkerDelete($lineNumber, 0);
			unset($this->buffers[$this->getTabSelected()]['breakpoints'][$lineNumber]);
		}
	}

	public function onWindowClose()
	{
		unset($this->tree);
		exit();
	}

	public function onSearchWindowSearch($ev)
	{
// TODO needs to tie in with onSearchWindowInput which searched as the user types
echo "search!\n";
	}

	public function onSearchWindowInput($ev)
	{
echo 'onSearchWindowInput: '.$ev->GetString()." - ".$this->currentSearchPos."\n";

		$activeTabId = $this->getTabSelected();

		$searchString = $ev->GetString();
		if(!$searchString)
		{
			return;
		}

		$searchStringLength = strlen($searchString);

		if($ev->GetEventType() == wxEVT_TEXT_ENTER)
		{
			$this->currentSearchPos += strlen($searchString);
		}

		$this->currentSearchPos = strpos($this->buffers[$activeTabId]['textctrl']->GetText(), $searchString, $this->currentSearchPos);
		$this->buffers[$activeTabId]['textctrl']->GotoPos($this->currentSearchPos);
		$this->buffers[$activeTabId]['textctrl']->SetSelection($this->currentSearchPos, $this->currentSearchPos+$searchStringLength);
	}

	public function onSearchWindowClose($ev)
	{
		if($this->searchWindow)
		{
			$this->searchWindow->Hide();
		}
	}

	public function onKeyDown($ev)
	{
		$keyCode = $ev->GetKeyCode();
//echo 'raw :'.$this->keyboardState->GetRawKeyFlags()."\n";
//echo $ev->GetKeyFlags()."\n";
// TODO need to catch escape key whilst focus is in the searchCtrl textCtrl..
		if($keyCode == 27)
		{
			if($this->searchWindow)
			{
				$this->searchWindow->Hide();
			}
		}

// BUG - Control always seems to be down ..
		if($this->keyboardState->CmdDown() && $keyCode == 70)
		{
			if($this->searchWindow)
			{
				$this->searchWindow->Show();
				$this->searchWindow->SetFocus();
			}
			else
			{
				$this->createSearchDialog();
			}
		}

		$ev->Skip();
	}

	public function createSearchDialog()
	{
		$id = $this->getTabSelected();

		$this->searchWindow = new wxPanel($this, wxID_ANY, $this->buffers[$id]['panel']->GetPosition(), new wxSize(220, 35));
		$sizer = new wxBoxSizer(wxVERTICAL);

		$search = new wxSearchCtrl($this->searchWindow, 55, wxEmptyString, wxDefaultPosition, wxDefaultSize, wxTE_PROCESS_ENTER);
		$search->ShowSearchButton(TRUE);
		$search->ShowCancelButton(TRUE);
		$search->Connect(55, wxEVT_COMMAND_SEARCHCTRL_SEARCH_BTN, [$this, 'onSearchWindowSearch']);
		$search->Connect(55, wxEVT_COMMAND_SEARCHCTRL_CANCEL_BTN, [$this, 'onSearchWindowClose']);
		$sizer->add($search, 1, wxALL|wxEXPAND, 5);

		$this->searchWindow->SetSizer($sizer);
		$this->searchWindow->Layout();

		$search->Connect(55, wxEVT_TEXT, [$this, 'onSearchWindowInput']);
		$search->Connect(55, wxEVT_TEXT_ENTER, [$this, 'onSearchWindowInput']);
		$search->SetFocus();
	}

	public function __construct( $parent=null )
	{
		//echo json_encode($this->settings, JSON_PRETTY_PRINT);

		//parent::__construct(null , wxID_ANY, , wxDefaultPosition, wxDefaultSize);
		parent::__construct( $parent, wxID_ANY, "Grease", wxDefaultPosition, new wxSize( 600,480 ), wxDEFAULT_FRAME_STYLE|wxTAB_TRAVERSAL );

		if(file_exists('.grease'))
		{
			$this->settings = json_decode(file_get_contents('.grease'), TRUE);
		}

		$this->Connect(wxEVT_CLOSE_WINDOW, [$this, 'onWindowClose']);
		$this->theme = &$this->settings['themes'][$this->settings['theme']];

		print_r($this->theme);

		$this->SetSizeHints( wxDefaultSize, wxDefaultSize );
		$this->initMenu($this);

		$sizer = new wxBoxSizer(wxVERTICAL);

		$toolbar = new wxAuiToolBar( $this, wxID_ANY, wxDefaultPosition, wxDefaultSize, wxAUI_TB_HORZ_LAYOUT );

		$IconNewTab = new wxBitmap();
		$IconNewTab->LoadFile('ico/newtab.bmp', wxBITMAP_TYPE_BMP);
		$iconFileOpen = new wxBitmap();
		$iconFileOpen->LoadFile('ico/fileopen.bmp', wxBITMAP_TYPE_BMP);
		$iconFileSave = new wxBitmap();
		$iconFileSave->LoadFile('ico/filesave.bmp', wxBITMAP_TYPE_BMP);
		$iconFileSaveAs = new wxBitmap();
		$iconFileSaveAs->LoadFile('ico/filesaveas.bmp', wxBITMAP_TYPE_BMP);

		$toolbar->AddTool( 1, "New Tab", $IconNewTab, "New Tab", wxITEM_NORMAL);
		$toolbar->AddTool( 4, "Open File", $iconFileOpen, "Open File", wxITEM_NORMAL);
		$this->Connect(4, wxEVT_COMMAND_MENU_SELECTED, [$this,"onOpenFile"]);

		$toolbar->AddTool(5, "Save File", $iconFileSave, "Save File", wxITEM_NORMAL);
		$this->Connect(5, wxEVT_COMMAND_MENU_SELECTED, [$this,"onSaveTab"]);

		$toolbar->AddTool( 6, "Save File As", $iconFileSaveAs, "Save File As", wxITEM_NORMAL);
		$this->Connect(6, wxEVT_COMMAND_MENU_SELECTED, [$this,"onSaveTabAs"]);

		//$toolbar->AddSeparator();
		$toolbar->Realize();

		$sizer->Add($toolbar, 0, wxALL, 5 );

		/* priamry splitter */
		$primarySplitterWindow = new wxSplitterWindow($this, wxID_ANY);


		/* left panel */
		$leftPanel = new wxPanel($primarySplitterWindow, wxID_ANY, wxDefaultPosition, new wxSize( 200,-1 ));
		$leftSizer = new wxBoxSizer(wxVERTICAL );

		$this->tree = new wxTreeCtrl($leftPanel, wxID_ANY, wxDefaultPosition, wxDefaultSize, wxTR_HIDE_ROOT|wxTR_HAS_BUTTONS);

		$this->treeRoot = $this->tree->AddRoot('wxphp');

		foreach($this->settings['projects'] as $name => $details)
		{
			$this->treeAddRoot($details['name'], $details['path'], $name);
		}

		$this->tree->Connect( wxEVT_TREE_SEL_CHANGED, [$this, "onTreeClick"]);
		$leftSizer->Add($this->tree, 1, wxALL|wxEXPAND, 5);
		$leftPanel->SetSizer($leftSizer);
		$leftPanel->Layout();

		/* right panel */
		$rightPanel = new wxPanel($primarySplitterWindow, wxID_ANY);
		$rightSizer = new wxBoxSizer(wxVERTICAL );

		$this->notebook = new wxAuiNotebook($rightPanel, wxID_ANY, wxDefaultPosition, wxDefaultSize, wxAUI_NB_DEFAULT_STYLE | wxAUI_NB_CLOSE_ON_ALL_TABS | wxNO_BORDER);
		//$this->notebook->Connect(wxID_ANY, wxVT_AUINOTEBOOK_PAGE_CHANGED, [$this, 'onTabChange']); // AUINotebook event doesn't work ..
		//$this->notebook->Connect(wxID_ANY, wxEVT_NOTEBOOK_PAGE_CHANGED, [$this, 'onTabChange']); // but can change to normal Notebook and it will..
//		$this->notebook->Connect(wxID_ANY, wxEVT_AUINOTEBOOK_PAGE_CLOSE, [$this, 'onTabClose']);

		$rightSizer->Add($this->notebook, 1, wxALL|wxEXPAND, 5);
		$rightPanel->SetSizer($rightSizer);
		$rightPanel->Layout();
		$rightSizer->Fit($rightPanel);

		/* main splitter window */
		$primarySplitterWindow->SplitVertically($leftPanel, $rightPanel);
		$sizer->Add($primarySplitterWindow, 1, wxALL|wxEXPAND, 5);

		$this->SetSizer($sizer);
		$this->Layout();

		//$this->ShowFullScreen(true, wxFULLSCREEN_ALL);
		$this->Centre( wxBOTH );

		$this->keyboardState = new wxKeyboardState(TRUE);
	}

	public function onTabChange($ev)
	{
echo "tab: ".$ev->GetSelection()." selected.\n";
// fire call backs for tab..
	}

	public function treeAddRoot($name, $dir, $project=FALSE)
	{
		$node = $this->tree->AppendItem($this->treeRoot, $name);
		/* set project name to root node */
		$nodeData = new wxTreeItemData();
		$nodeData->project = $project;
		$this->tree->SetItemData($node, $nodeData);
		$this->scanDirectory($dir, $node, $this->tree);

	}

	public function addTab($title='New', $file=FALSE, $focus=FALSE, $webview=FALSE)
	{
		static $id=1;

		$this->buffers[$id]['title'] = $title;
		$this->buffers[$id]['file'] = $file;
		if($file)
		{
			$this->buffers[$id]['fileMd5'] = md5(file_get_contents($file));
		}
		else
		{
			$this->buffers[$id]['fileMd5'] = FALSE;
		}

		$this->buffers[$id]['breakpoints'] = [];

		$this->buffers[$id]['panel'] = new wxPanel($this->notebook);
		$this->buffers[$id]['sizer'] = new wxBoxSizer(wxVERTICAL);

		if(!$webview)
		{
			$this->buffers[$id]['textctrl'] = new wxStyledTextCtrl($this->buffers[$id]['panel'], wxID_ANY);

			$this->buffers[$id]['textctrl']->SetMarginWidth (0, 50);
			$this->buffers[$id]['textctrl']->StyleSetForeground (wxSTC_STYLE_LINENUMBER, new wxColour (75, 75, 75) );
			$this->buffers[$id]['textctrl']->StyleSetBackground (wxSTC_STYLE_LINENUMBER, new wxColour (220, 220, 220));
			$this->buffers[$id]['textctrl']->SetMarginType (0, wxSTC_MARGIN_NUMBER);

			$this->buffers[$id]['textctrl']->SetWrapMode (wxSTC_WRAP_WORD);
			$this->buffers[$id]['textctrl']->SetStyleBits(7);
			$this->buffers[$id]['textctrl']->SetLexer(wxSTC_LEX_PHPSCRIPT);

			$this->buffers[$id]['textctrl']->SetCaretForeground(new wxColour(255, 255, 255));
			$this->buffers[$id]['textctrl']->SetCaretWidth(40);
			$this->buffers[$id]['textctrl']->SetCaretPeriod(200);

			//$this->buffers[$id]['textctrl']->StyleSetFontAttr(wxSTC_STYLE_DEFAULT, 8, "Courier New", false, false, false, wxFONTENCODING_DEFAULT);

			$backgroundColor = new wxColour();
			$backgroundColor->SetRGB($this->theme['background-color']);
			$foregroundSelection = new wxColour(0, 0, 0);
			$backgroundSelection = new wxColour(255, 255, 255);
			foreach($this->styles['php'] as $name => $style)
			{
				$this->buffers[$id]['textctrl']->StyleSetFontAttr($style, $this->settings['font-size'], $this->settings['font-face'], false, false, false, wxFONTENCODING_DEFAULT);

				$foregroundColor =  new wxColour();
				$foregroundColor->SetRGB($this->theme[$name]);

				$this->buffers[$id]['textctrl']->StyleSetBackground($style, $backgroundColor);
				$this->buffers[$id]['textctrl']->StyleSetForeground($style, $foregroundColor);
				$this->buffers[$id]['textctrl']->SetSelBackground($style, $backgroundSelection);
				$this->buffers[$id]['textctrl']->SetSelForeground($style, $foregroundSelection);
			}

			if($file)
			{
				//$this->buffers[$id]['textctrl']->SetText(file_get_contents($file));
				$this->buffers[$id]['textctrl']->LoadFile($file);
//$this->settings['projects']['Grease']['state']['files'][$file] = TRUE;
//file_put_contents('.grease', json_encode($this->settings, JSON_PRETTY_PRINT));
			}

			$this->buffers[$id]['panel']->SetId($id);

			$this->buffers[$id]['textctrl']->Connect(wxID_ANY, wxEVT_STC_CHANGE, [$this, 'onTabModified']);
			$this->buffers[$id]['textctrl']->Connect(wxID_ANY, wxEVT_KEY_DOWN, [$this, 'onKeyDown']);

			$this->buffers[$id]['textctrl']->SetSelection(0, 0);

			// setup the debugging margin
			$this->buffers[$id]['textctrl']->MarkerDefine(1, wxSTC_MARK_CIRCLE, wxRED);
			$this->buffers[$id]['textctrl']->SetMarginSensitive(1, TRUE);
			$this->buffers[$id]['textctrl']->Connect(wxID_ANY, wxEVT_STC_MARGINCLICK, [$this, 'onMarginClick']);

		}
		else
		{
			$this->buffers[$id]['textctrl'] = wxWebView::NewMethod($this->buffers[$id]['panel'], wxID_ANY, 'http://google.co.uk');
			var_dump($this->buffers[$id]['textctrl']->RunScript("alert('Hello world!');"));

			$this->buffers[$id]['textctrl']->Connect(wxID_ANY, wxEVT_WEBVIEW_NEWWINDOW, [$this, 'onWebviewNavigating']);

		}

		$this->buffers[$id]['sizer']->Add($this->buffers[$id]['textctrl'], 1, wxALL|wxEXPAND, 5);
		$this->buffers[$id]['panel']->SetSizer($this->buffers[$id]['sizer']);
		$this->notebook->AddPage($this->buffers[$id]['panel'], $title, $focus);

		$id++;
	}

	public function onWebviewNavigating($ev)
	{
echo $ev->GetURL()."\n";
	}

	public function scanDirectory($dir, $parentNode, $tree)
	{
		foreach(scandir($dir) as $node)
		{
			if($node == '.')  continue;
			if($node == '..') continue;
			if($node{0} == '.') continue;

			$filename = $dir.DIRECTORY_SEPARATOR.$node;

			if(is_dir($filename))
			{
				$nodeId = $tree->AppendItem($parentNode, $node);

				$nodeData = new wxTreeItemData();
				$nodeData->filename = $filename;
				$nodeData->name = $node;

				$tree->SetItemData($nodeId, $nodeData);
				$this->scanDirectory($dir . DIRECTORY_SEPARATOR . $node, $nodeId, $tree);
			}
			else
			{
				$nodeId = $tree->AppendItem($parentNode, $node);

				$nodeData = new wxTreeItemData();
				$nodeData->filename = $filename;
				$nodeData->name = $node;

				$tree->SetItemData($nodeId, $nodeData);
			}
		}
	}

	public function initMenu($panel)
	{
		$menu = new wxMenuBar();
		$fileMenu = new wxMenu();

		$fileMenu->Append(1,"N&ew","New buffer");
		$fileMenu->Append(2,"E&xit","Quit this program");
		$menu->Append($fileMenu,"&File");

		$menuAbout = new wxMenu();
		$menuAbout->AppendCheckItem(4,"&About...","Show about dialog");
		$menu->Append($menuAbout,"&Help");

		$panel->SetMenuBar($menu);

		$this->Connect(1, wxEVT_COMMAND_MENU_SELECTED, [$this,"onAddTab"]);
		$this->Connect(2, wxEVT_COMMAND_MENU_SELECTED, [$this,"onQuit"]);
		$this->Connect(4, wxEVT_COMMAND_MENU_SELECTED, [$this,"onAbout"]);
	}

	public function onTabModified($ev)
	{
		$id = $this->getTabSelected();
// TODO - save diskMd5 to $this->buffers so it's not read on each event.  Make sure to update the md5 on a file save.
// TODO - Not sure if it's even worth checking against the file as every modification causes an event that has to md5 the entire file so could be very slow on large files..
		$diskMd5 = $this->buffers[$id]['fileMd5']; //md5(file_get_contents($this->buffers[$id]['file']));
		$bufferMd5 = md5($this->buffers[$id]['textctrl']->GetText());

		if($this->buffers[$id]['file'] && ( $diskMd5 != $bufferMd5))
		{
			$this->notebook->SetPageText($this->notebook->GetSelection(), $this->buffers[$id]['title'].'*');
		}
		else if($diskMd5 == $bufferMd5)
		{
			$this->notebook->SetPageText($this->notebook->GetSelection(), $this->buffers[$id]['title']);
		}
	}

	public function getTabSelected()
	{
		$selection = $this->notebook->GetSelection();

		if($selection != -1)
		{
			$page = $this->notebook->GetPage($selection);
			return $page->GetId();
		}
		return FALSE;
	}

	public function onOpenFile()
	{
		$openDialog = new wxFileDialog($this, 'Open file', wxEmptyString, wxEmptyString, wxFileSelectorDefaultWildcardStr);
		if($openDialog->ShowModal() != wxID_CANCEL)
		{
			$filepath = $openDialog->GetPath();
			$filename = $openDialog->GetFilename();
			if(file_exists($filename))
			{
				$this->addTab($filename, $filepath, TRUE);
			}
		}
	}

	public function onSaveTab()
	{
		$id = $this->getTabSelected();
		if($id)
		{
			$this->buffers[$id]['textctrl']->SaveFile($this->buffers[$id]['file']);
			$this->buffers[$id]['fileMd5'] = md5(file_get_contents($this->buffers[$id]['file']));
			$this->notebook->SetPageText($this->notebook->GetSelection(), $this->buffers[$id]['title']); // remove * from title
		}
	}

	public function onSaveTabAs()
	{
		$id = $this->getTabSelected();
		if(!$id)
		{
			return FALSE;
		}

		$saveAsDialog = new wxFileDialog($this, 'Save file as', wxEmptyString, wxEmptyString, wxFileSelectorDefaultWildcardStr, wxFD_SAVE);
		if($saveAsDialog->ShowModal() != wxID_CANCEL)
		{
echo $saveAsDialog->GetPath().' '.$saveAsDialog->GetFilename()."\n";
			$filename = $saveAsDialog->GetPath();
			if(!file_exists($filename))
			{
				$this->buffers[$id]['textctrl']->SaveFile($filename);
				$this->buffers[$id]['file'] = $filename;
				$this->buffers[$id]['fileMd5'] = md5(file_get_contents($this->buffers[$id]['file']));
			}
			// save to file
			// update current tab with new file
		}
//TODO - be sure to update fileMd5
	}

	public function onTreeClick($ev)
	{
		$data = $this->tree->GetItemData($this->tree->GetSelection());
print_r($data);
		if(isset($data->project))
		{
			$this->activeProject = $data->project;
echo 'project: '.$data->project."\n";
		}
		else if(isset($data->filename))
		{
			if(is_file($data->filename))
			{
echo "file: ".$data->filename."\n";
				$this->addTab($data->name, $data->filename, TRUE);
			}
		}
		$ev->Skip();
	}

	public function onAddTab()
	{
//		$this->addTab('New', FALSE, TRUE, TRUE); // adds a new webview instead of new editor tab
		$this->addTab('New', FALSE, TRUE);
	}

	public function onQuit()
	{
		foreach($this->buffers as $id => &$buffer)
		{
			if($buffer['textctrl']->GetModify())
			{
				$saveDialog = new wxMessageDialog($this, $buffer['title'].' is unsaved, save now?');

				if($saveDialog->ShowModal() != wxID_CANCEL)
				{
					$buffer['textctrl']->SaveFile();
				}
			}
		}
		$this->Destroy();
	}

	public function onAbout()
	{
		wxMessageBox("Welcome to Grease\nFree for non-commerical use.", 'About');
	}
}

class myApp extends wxApp
{
	function OnInit()
	{
		$mf = new grease();
		$mf->Show();

		return FALSE;
	}

	function OnExit()
	{
		return FALSE;
	}
}

/*$pid = pcntl_fork();
if($pid)
{
	exit();
}*/

$xt = new myApp();
wxApp::SetInstance($xt);
wxEntry();
