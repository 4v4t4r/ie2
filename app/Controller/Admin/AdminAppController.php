<?php
App::uses('AppController', 'Controller');

class AdminAppController extends AppController {

	public function beforeFilter() {
		parent::beforeFilter();

		// We're doing a backend request, require backend access
		$this->Auth->protect(env('GROUP_ADMIN'));

		// Set the active menu item
		$this->set('at_backendpanel', true);
	}
}
