<?php

use ExpressionEngine\Service\JumpMenu\AbstractJumpMenu;

class Hop_minifizer_jump extends AbstractJumpMenu
{
	protected static $items = [
		'license' => [
			'icon' => 'fa-lock',
			'command' => 'license',
			'command_title' => '<b>License</b>',
			'dynamic' => false,
			'requires_keyword' => false,
			'target' => 'license'
		],
		'settings' => [
			'icon' => 'fa-gear',
			'command' => 'settings',
			'command_title' => '<b>Settings</b>',
			'dynamic' => false,
			'requires_keyword' => false,
			'target' => 'settings'
		],
	];
}