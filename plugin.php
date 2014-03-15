<?php

interface Grease_Plugin
{
	public function init($grease); // return ['parent' = 'left|bottom']
	public function initMenuItems(); // return [['desciption' => '', 'icon' => 'Icon.png', 'callback' => ''], ...]
	public function initViews($parent, $sizer);
}