<?php

class Grease_About implements Grease_Plugin
{
	private $grease;
	private $parent;

	public function init($grease)
	{
		$this->grease = $grease;
		return ['parent' => FALSE];
	}

	public function initMenuItems()
	{
		return ['about' => ['name' => 'About', 'description' => 'About', 'callback' => 'onAbout']];
	}

	public function initViews($parent, $sizer)
	{
		$this->parent = $parent;
	}

	public function onAbout($ev)
	{
		wxMessageBox("Welcome to Grease!\n\nCopyright(c) 2014 Andrew Rose (http://andrewrose.co.uk)\n\nFree for non-commerical use.", 'About');
	}

}