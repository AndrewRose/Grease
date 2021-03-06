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

define('ROOT_DIR', '/opt/grease');
include_once(ROOT_DIR.'/libxdebugd.php');
include_once(ROOT_DIR.'/plugin.php');

class Grease extends wxFrame
{
	public $settingsFile;
	public $tree;
	public $treeRoot;
	public $notebook;
	public $buffers = [];
	public $searchTextCtrl = FALSE;
	public $searchReplaceSearchTextCtrl = FALSE;
	public $leftPanel;
	public $readonlyBuffers = [];
	public $filesOpen = [];

	public $settings =
	[
		'theme' => 'grease',
		'font-size' => 10,
		'font-face' => 'Courier New',

		'xdebug' =>
		[
			'host' => '127.0.0.1',
			'port' => '9000'
		],

		'projects' =>
		[

		],

		'plugins' => [
			'Xdebug',
			'Console',
			'About'
		],

		'themes' =>
		[
			'grease' =>
			[
				'background-color' => '0xC0C0C0',
				'wxSTC_STYLE_DEFAULT' => '0x1E5A14',
				'wxSTC_HPHP_DEFAULT' => '0x1E5A14',
				'wxSTC_HPHP_HSTRING' => '0x1E5A14',
				'wxSTC_HPHP_SIMPLESTRING' => '0x1E5A14',
				'wxSTC_HPHP_WORD' => '0x1E5A14',
				'wxSTC_HPHP_NUMBER' => '0x1E5A14',
				'wxSTC_HPHP_VARIABLE' => '0x1E5A14',
				'wxSTC_HPHP_COMMENT' => '0x1E5A14',
				'wxSTC_HPHP_COMMENTLINE' => '0x1E5A14',
				'wxSTC_HPHP_HSTRING_VARIABLE' => '0x1E5A14',
				'wxSTC_HPHP_OPERATOR' => '0x1E5A14'
			]
		],

		'state' =>
		[
			'files' =>
			[

			]
		]
	];
	public $theme = FALSE;

	public $styles =
	[
		'php' =>
		[
			'wxSTC_STYLE_DEFAULT' => wxSTC_STYLE_DEFAULT,
			'wxSTC_HPHP_DEFAULT' => wxSTC_HPHP_DEFAULT,
			'wxSTC_HPHP_HSTRING' => wxSTC_HPHP_HSTRING,
			'wxSTC_HPHP_SIMPLESTRING' => wxSTC_HPHP_SIMPLESTRING,
			'wxSTC_HPHP_WORD' => wxSTC_HPHP_WORD,
			'wxSTC_HPHP_NUMBER' => wxSTC_HPHP_NUMBER,
			'wxSTC_HPHP_VARIABLE' => wxSTC_HPHP_VARIABLE,
			'wxSTC_HPHP_COMMENT' => wxSTC_HPHP_COMMENT,
			'wxSTC_HPHP_COMMENTLINE' => wxSTC_HPHP_COMMENTLINE,
			'wxSTC_HPHP_HSTRING_VARIABLE' => wxSTC_HPHP_HSTRING_VARIABLE,
			'wxSTC_HPHP_OPERATOR' => wxSTC_HPHP_OPERATOR
		]
	];

	public $searchWindow = FALSE;
	public $plugins = [];

	public function createToolbar()
	{
		$this->toolbar = new wxAuiToolBar($this, wxID_ANY, wxDefaultPosition, wxDefaultSize, wxAUI_TB_VERTICAL);

		$icons  = [
			'newtab' => ['name' => 'New Tab', 'description' => 'New Tab', 'callback' => [$this, 'onAddTab']],
			'fileopen' => ['name' => 'Open File', 'description' => 'Open File', 'callback' => [$this, 'onOpenFile']],
			'filesave' => ['name' => 'Save Tab', 'description' => 'Save Tab', 'callback' => [$this, 'onSaveTab']],
			'filesaveas' => ['name' => 'Save File As', 'description' => 'Save File As', 'callback' => [$this, 'onSaveTabAs']],
			FALSE,
			'find' => ['name' => 'Find', 'description' => 'Find', 'callback' => [$this, 'onFind']],
			'findreplace' => ['name' => 'Find / Replace', 'description' => 'Find / Replace', 'callback' => [$this, 'onFindReplace']],
		];

		$i = 1;
		foreach($icons as $iconName => $params)
		{
			if(!$params)
			{
				$this->toolbar->AddSeparator();
			}
			else
			{
				$icon = new wxBitmap();
				$icon->LoadFile(dirname(__FILE__).'/ico'.DIRECTORY_SEPARATOR.$iconName.'.ico', wxBITMAP_TYPE_ICO);
				$this->toolbar->AddTool($i, $params['name'], $icon, $params['description'], wxITEM_NORMAL);
				$this->Connect($i, wxEVT_COMMAND_MENU_SELECTED, $params['callback']);
				$i++;
			}
		}

		$this->toolbar->Realize();
		return $this->toolbar;
	}

	public function loadPlugins()
	{
		foreach($this->settings['plugins'] as $plugin)
		{
			include_once(ROOT_DIR.'/Plugins/'.$plugin.'.php');
			$classname = 'Grease_'.$plugin;
			$this->plugins[$plugin] = new $classname;
			$pluginParams = $this->plugins[$plugin]->init($this);

// fix
static $i=42;
			$this->toolbar->AddSeparator();
			foreach($this->plugins[$plugin]->initMenuItems() as $iconName => $params)
			{
				if(!$params)
				{
					$this->toolbar->AddSeparator();
				}
				else
				{
					$icon = new wxBitmap();
					$icon->LoadFile(dirname(__FILE__).'/ico'.DIRECTORY_SEPARATOR.$iconName.'.ico', wxBITMAP_TYPE_ICO);
					$this->toolbar->AddTool($i, $params['name'], $icon, $params['description'], wxITEM_NORMAL);
					$this->Connect($i, wxEVT_COMMAND_MENU_SELECTED, [$this->plugins[$plugin], $params['callback']]);
					$i++;
				}
			}
			$this->toolbar->Realize();

			if($pluginParams['parent'])
			{
				if($pluginParams['parent'] == 'bottom')
				{
					$this->plugins[$plugin]->initViews($this->secondarySplitterWindow, FALSE);
				}
				else if($pluginParams['parent'] == 'left')
				{
					$this->plugins[$plugin]->initViews($this->leftPanel, $this->leftSizer);
				}
			}
		}
	}

	public function __construct( $parent=null )
	{
		parent::__construct( $parent, wxID_ANY, "Grease", wxDefaultPosition, new wxSize( 1600,900 ), wxDEFAULT_FRAME_STYLE|wxTAB_TRAVERSAL );

		$this->settingsFile = $_SERVER['HOME'].'/.grease';

		if(file_exists($this->settingsFile))
		{
			$this->settings = json_decode(file_get_contents($this->settingsFile), TRUE);
		}
		else
		{
			file_put_contents($this->settingsFile, json_encode($this->settings, JSON_PRETTY_PRINT));
		}
	
		$this->Connect(wxEVT_CLOSE_WINDOW, [$this, 'onWindowClose']);
		$this->theme = &$this->settings['themes'][$this->settings['theme']];

		$this->SetSizeHints(wxDefaultSize, wxDefaultSize);
//		$this->initMenu($this);

		$sizer = new wxBoxSizer(wxHORIZONTAL);

		$toolbar = $this->createToolbar();

		/* splitters */
		$this->secondarySplitterWindow = new wxSplitterWindow($this, wxID_ANY);
		$this->primarySplitterWindow = new wxSplitterWindow($this->secondarySplitterWindow, wxID_ANY, wxDefaultPosition, new wxSize(-1, 500));

		/* left panel */
$leftPanelMaxSize = 300; //($this->GetSize()->GetWidth()/100)*20;
		$this->leftPanel = new wxPanel($this->primarySplitterWindow, wxID_ANY, wxDefaultPosition, new wxSize($leftPanelMaxSize, -1));
		$this->leftSizer = new wxBoxSizer(wxVERTICAL);

		$this->tree = new wxTreeCtrl($this->leftPanel, wxID_ANY, wxDefaultPosition, wxDefaultSize, wxTR_HIDE_ROOT|wxTR_HAS_BUTTONS);
		$this->treeRoot = $this->tree->AddRoot('wxphp');

		foreach($this->settings['projects'] as $name => $details)
		{
			$this->treeAddRoot($details['name'], $details['path'], $name);
		}

		$this->tree->Connect(wxEVT_TREE_SEL_CHANGED, [$this, "onTreeClick"]);

		$this->leftSizer->Add($this->tree, 1, wxALL|wxEXPAND, 5);

		$this->leftPanel->SetSizer($this->leftSizer);
		$this->leftPanel->Layout();

		/* right panel */
$rightPanelMaxSize = $this->GetSize()->GetWidth()-$leftPanelMaxSize; //($this->GetSize()->GetWidth()/100)*80;
		$rightPanel = new wxPanel($this->primarySplitterWindow, wxID_ANY, wxDefaultPosition, new wxSize($rightPanelMaxSize, -1));
		$rightSizer = new wxBoxSizer(wxVERTICAL);

		$this->notebook = new wxAuiNotebook($rightPanel, wxID_ANY, wxDefaultPosition, wxDefaultSize, wxAUI_NB_DEFAULT_STYLE | wxAUI_NB_CLOSE_ON_ALL_TABS | wxNO_BORDER);
		$this->notebook->Connect(wxID_ANY, wxEVT_AUINOTEBOOK_PAGE_CHANGED, [$this, 'onTabChange']); // AUINotebook event doesn't work ..
		$this->notebook->Connect(wxID_ANY, wxEVT_AUINOTEBOOK_PAGE_CLOSE, [$this, 'onTabClose']); // ...

		//$this->notebook = new wxNotebook($rightPanel, wxID_ANY);
		//$this->notebook->Connect(wxID_ANY, wxEVT_NOTEBOOK_PAGE_CHANGED, [$this, 'onTabChange']); // but can change to normal Notebook and it will..

		$rightSizer->Add($this->notebook, 1, wxALL|wxEXPAND, 5);
		$rightPanel->SetSizer($rightSizer);
		$rightPanel->Layout();
		$rightSizer->Fit($rightPanel);

		/* main splitter window */
		$this->primarySplitterWindow->SplitVertically($this->leftPanel, $rightPanel);
//$this->console->createConsoleWindow($this->secondarySplitterWindow);

		$this->secondarySplitterWindow->Initialize($this->primarySplitterWindow);
		$this->primarySplitterWindow->SetSashGravity(0.0);
		$sizer->Add($this->secondarySplitterWindow, 1, wxALL|wxEXPAND, 5);
		$sizer->Add($toolbar, 0, wxALL, 5 );

		$this->SetSizer($sizer);

		$this->loadPlugins();

		$this->Layout();

		//$this->ShowFullScreen(true, wxFULLSCREEN_ALL);
		$this->Centre( wxBOTH );

		$this->createSearchDialog();
		$this->createSearchReplaceDialog();
		//$this->createConsoleWindow();

		$this->searchWindow->Hide();

		foreach($this->settings['state']['files'] as $file => $state)
		{
			$bufferId = $this->addTab(basename($file), $file, TRUE); // focus needs to be set to get position!
			$this->filesOpen[$file] = $bufferId;
		}
	}

	public function onMarginClick($ev)
	{
		foreach($this->plugins as $plugin)
		{
			$plugin->onMarginClick($ev);
		}
	}

	public function onWindowClose()
	{
		unset($this->tree);
		exit();
	}

	public function onSearchReplaceWindowInput($ev)
	{
		$this->onSearchWindowInput($ev, TRUE);
	}

	public function onSearchWindowInput($ev, $replace=FALSE)
	{
		$activeTabId = $this->getTabSelected();

		if($replace && $ev->GetKeyCode() == 343) // F4 replace
		{
			$this->buffers[$activeTabId]['textctrl']->ReplaceSelection($this->searchReplaceReplaceTextCtrl->GetValue());
		}

		if($ev->GetKeyCode() == 27)
		{
			$this->buffers[$activeTabId]['textctrl']->ClearSelections();
			if($replace)
			{
				$this->searchReplaceWindow->Hide();
			}
			else if($this->searchWindow)
			{
				$this->searchWindow->Hide();
			}
			return;
		}

		if($replace)
		{
			$searchString = $this->searchReplaceSearchTextCtrl->GetValue();
		}
		else
		{
			$searchString = $this->searchTextCtrl->GetValue();
		}

		if(!$searchString)
		{
			return;
		}

		$searchStringLength = strlen($searchString);

		if(in_array($ev->GetKeyCode(), [13, 342])) // enter or f3
		{
			$this->buffers[$activeTabId]['currentSearchPos'] += strlen($searchString);
		}

		$newpos = strpos($this->buffers[$activeTabId]['textctrl']->GetText(), $searchString, $this->buffers[$activeTabId]['currentSearchPos']);

		if($newpos !== FALSE)
		{
			$this->buffers[$activeTabId]['currentSearchPos'] = $newpos;
		}
		else // no more instances, return to beginning of document.
		{
//$this->searchTextCtrl->SetDefaultStyle(new wxTextAttr(wxRED));
			$this->buffers[$activeTabId]['currentSearchPos'] = strpos($this->buffers[$activeTabId]['textctrl']->GetText(), $searchString, 0);
		}

		$this->buffers[$activeTabId]['textctrl']->GotoPos($this->buffers[$activeTabId]['currentSearchPos']);
		$this->buffers[$activeTabId]['textctrl']->SetSelection($this->buffers[$activeTabId]['currentSearchPos'], $this->buffers[$activeTabId]['currentSearchPos']+$searchStringLength);
	}

	public function onSearchWindowClose($ev)
	{
		if($this->searchWindow)
		{
			$this->searchWindow->Hide();
		}
	}

	public function onFind($ev)
	{
		if($this->searchReplaceWindow->IsShownOnScreen())
		{
			$this->searchReplaceWindow->hide();
		}

		if($this->searchWindow->IsShownOnScreen())
		{
			$this->searchWindow->hide();
		}
		else if($this->getTabSelected())
		{
			$this->searchWindow->Show();
			$this->searchWindow->SetFocus();
		}
	}

	public function onFindReplace($ev)
	{
		if($this->searchWindow->IsShownOnScreen())
		{
			$this->searchWindow->hide();
		}

		if($this->searchReplaceWindow->IsShownOnScreen())
		{
			$this->searchReplaceWindow->hide();
		}
		else if($this->getTabSelected())
		{
			$this->searchReplaceWindow->Show();
		}
	}

	public function onKeyDown($ev)
	{
		$id = $this->getTabSelected();

		if(!$this->buffers[$id]['onTabModifiedConnected'])
		{
			$this->buffers[$id]['textctrl']->Connect(wxID_ANY, wxEVT_STC_CHANGE, [$this, 'onTabModified']);
			$this->buffers[$id]['onTabModifiedConnected'] = TRUE;
		}

		$keyCode = $ev->GetKeyCode();

		if($keyCode == 27)
		{
			if($this->searchWindow)
			{
				$this->searchWindow->Hide();
			}
		}

		if($ev->ControlDown() && $keyCode == 70)
		{
			$this->searchWindow->Show();
			$this->searchWindow->SetFocus();
		}

		$ev->Skip();
	}

	public function createSearchDialog()
	{
		$id = $this->getTabSelected();

		$this->searchWindow = new wxPanel($this->notebook, wxID_ANY, wxDefaultPosition, new wxSize(220, 35));
		$sizer = new wxBoxSizer(wxVERTICAL);

		$this->searchTextCtrl = new wxTextCtrl($this->searchWindow, wxID_ANY, wxEmptyString, wxDefaultPosition, wxDefaultSize, wxTE_PROCESS_ENTER);
		$sizer->add($this->searchTextCtrl, 1, wxALL|wxEXPAND, 5);

		$this->searchWindow->SetSizer($sizer);
		$this->searchWindow->Layout();

		$this->searchTextCtrl->Connect(wxID_ANY, wxEVT_KEY_UP, [$this, 'onSearchWindowInput']);
		$this->searchWindow->Hide();
	}

	public function createSearchReplaceDialog()
	{
		$id = $this->getTabSelected();

		$this->searchReplaceWindow = new wxPanel($this->notebook, wxID_ANY, wxDefaultPosition, new wxSize(220, 70));
		$sizer = new wxBoxSizer(wxVERTICAL);

		$this->searchReplaceSearchTextCtrl = new wxTextCtrl($this->searchReplaceWindow, wxID_ANY, wxEmptyString, wxDefaultPosition, wxDefaultSize, wxTE_PROCESS_ENTER);
		$sizer->add($this->searchReplaceSearchTextCtrl, 1, wxALL|wxEXPAND, 5);

		$this->searchReplaceReplaceTextCtrl = new wxTextCtrl($this->searchReplaceWindow, wxID_ANY, wxEmptyString, wxDefaultPosition, wxDefaultSize, wxTE_PROCESS_ENTER);
		$sizer->add($this->searchReplaceReplaceTextCtrl, 1, wxALL|wxEXPAND, 5);
		
		$this->searchReplaceWindow->SetSizer($sizer);
		$this->searchReplaceWindow->Layout();

		$this->searchReplaceSearchTextCtrl->Connect(wxID_ANY, wxEVT_KEY_UP, [$this, 'onSearchReplaceWindowInput']);
		$this->searchReplaceReplaceTextCtrl->Connect(wxID_ANY, wxEVT_KEY_UP, [$this, 'onSearchReplaceWindowInput']);
		$this->searchReplaceWindow->Hide();
	}

	public function onTabClose($ev)
	{
		$page = $this->notebook->GetPage($ev->GetSelection());
		$id = $page->GetId();
		if($this->buffers[$id]['modified'])
		{
			$messageDialog = new wxMessageDialog($this, 'Tab is being closed but has modifications, save changes?', 'Save changes?', wxYES_NO);
			$resp = $messageDialog->ShowModal();
			if($resp == wxID_YES)
			{
				$this->buffers[$id]['textctrl']->SaveFile($this->buffers[$id]['file']);
			}
		}

// TODO this needs to be put into a method
unset($this->settings['state']['files'][$this->buffers[$id]['file']]);
file_put_contents($this->settingsFile, json_encode($this->settings, JSON_PRETTY_PRINT));

		unset($this->buffers[$id]);
	}

	public function onTabChange($ev)
	{
// TODO when auinotebook events fire - save search string and update the search string textCtrl between tabs 
// echo 'onTabChange: '.$this->getTabSelected()."\n";
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

		if($file)
		{
			foreach($this->buffers as $idx => $buffer)
			{
				if($buffer['file'] == $file)
				{
					$this->notebook->SetSelection($buffer['position']);
					return TRUE;
				}
			}
			$this->buffers[$id]['fileMd5'] = md5(file_get_contents($file));
			$this->filesOpen[realpath($file)] = $id;
		}
		else
		{
			$this->buffers[$id]['fileMd5'] = FALSE;
		}

		$this->buffers[$id]['id'] = $id;
		$this->buffers[$id]['title'] = $title;
		$this->buffers[$id]['file'] = $file;
		$this->buffers[$id]['realpath'] = realpath($file);

		$this->buffers[$id]['breakpoints'] = [];
		$this->buffers[$id]['currentSearchPos'] = 0;
		$this->buffers[$id]['modified'] = FALSE;
		$this->buffers[$id]['onTabModifiedConnected'] = FALSE;
		$this->buffers[$id]['debugMarkerPosition'] = FALSE;

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

			$fileType = end(explode('.', $file)); 
			if(1 || in_array($fileType, ['php', 'wxphp']))
			{
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
			}

			if($file)
			{
				//$this->buffers[$id]['textctrl']->SetText(file_get_contents($file));
				$this->buffers[$id]['textctrl']->LoadFile($file);

$this->settings['state']['files'][$file] = TRUE;
file_put_contents($this->settingsFile, json_encode($this->settings, JSON_PRETTY_PRINT));
			}

			$this->buffers[$id]['panel']->SetId($id);

//			$this->buffers[$id]['textctrl']->Connect(wxID_ANY, wxEVT_STC_CHANGE, [$this, 'onTabModified']);
			$this->buffers[$id]['textctrl']->Connect(wxID_ANY, wxEVT_KEY_DOWN, [$this, 'onKeyDown']);

			$this->buffers[$id]['textctrl']->SetSelection(0, 0);

			// setup the debugging margin
			$this->buffers[$id]['textctrl']->MarkerDefine(1, wxSTC_MARK_CIRCLE, wxRED);
			$this->buffers[$id]['textctrl']->SetMarginSensitive(1, TRUE);

			$this->buffers[$id]['textctrl']->MarkerDefine(2, wxSTC_MARK_ARROW, wxRED);
			$this->buffers[$id]['textctrl']->SetMarginSensitive(2, TRUE);

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
		$this->buffers[$id]['position'] = $this->notebook->GetSelection();

		return $id++;
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
				$nodeData->filename = realpath($filename);
				$nodeData->name = $node;

				$tree->SetItemData($nodeId, $nodeData);
				$this->scanDirectory($dir . DIRECTORY_SEPARATOR . $node, $nodeId, $tree);
			}
			else
			{
				$nodeId = $tree->AppendItem($parentNode, $node);

				$nodeData = new wxTreeItemData();
				$nodeData->filename = realpath($filename);
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
		$menuAbout->Append(4,"&About...","Show about dialog");
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
			$this->buffers[$id]['modified'] = TRUE;
			$this->notebook->SetPageText($this->notebook->GetSelection(), $this->buffers[$id]['title'].'*');
		}
		else if($diskMd5 == $bufferMd5)
		{
			$this->buffers[$id]['modified'] = FALSE;
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
			if(file_exists($filepath))
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
			$this->buffers[$id]['modified'] = FALSE;
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
			$file = $saveAsDialog->GetPath();
			$filename = $saveAsDialog->GetFileName();
			if(!file_exists($file))
			{
				$this->buffers[$id]['textctrl']->SaveFile($file);
				$this->buffers[$id]['file'] = $file;
				$this->filesOpen[realpath($file)] = $id;
				$this->buffers[$id]['title'] = $filename;
				$this->buffers[$id]['fileMd5'] = md5(file_get_contents($this->buffers[$id]['file']));
				$this->notebook->SetPageText($this->notebook->GetSelection(), $filename);
			}
			// save to file
			// update current tab with new file
		}
//TODO - be sure to update fileMd5
	}

	public function onTreeClick($ev)
	{
		$data = $this->tree->GetItemData($this->tree->GetSelection());
		if(isset($data->project))
		{
			$this->activeProject = $data->project;
		}
		else if(isset($data->filename))
		{
			if(is_file($data->filename))
			{
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

wxInitAllImageHandlers();
$xt = new myApp();
wxApp::SetInstance($xt);
wxEntry();
