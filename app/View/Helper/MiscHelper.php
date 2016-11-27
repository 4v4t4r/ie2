<?php
App::uses('AppHelper', 'View/Helper');

class MiscHelper extends AppHelper {
	public $helpers = ['Html'];

	const NAVBAR_ITEM = '<li class="%s"><a href="%s">%s</a></li>';
	const NAVBAR_MENU = '<li class="dropdown %s">'.
		'<a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button">'.
		'%s <span class="caret"></span></a><ul class="dropdown-menu">%s</ul></li>';

	public function navbarItem($name, $url, $active=false) {
		$url = $this->Html->url($url);

		return sprintf(self::NAVBAR_ITEM, ($active ? 'active' : ''), $url, $name);
	}

	public function navbarDropdown($name, $active, $children) {
		return sprintf(self::NAVBAR_MENU, ($active ? 'active' : ''), $name, implode('', $children));
	}
}