<?php
class UsersController extends AppController {
	var $name = 'Users';
	var $uses = array('Maintainer');

	function dashboard() {
		$user = $this->Maintainer->find('dashboard');
		$this->set(compact('user'));
	}

	function login() {
		if (empty($this->data)) {
			return;
		}

		$type = (strstr($this->data['User']['login'], '@')) ? 'credentials' : 'username';

		$maintainer = Authsome::login($type, $this->data['User']);

		if (!$maintainer) {
			$this->Session->setFlash(__('Unknown user or incorrect Password', true));
			return;
		}

		$remember = (!empty($this->data['Maintainer']['remember']));
		if ($remember) {
			Authsome::persist('2 weeks');
		}

		if ($maintainer) {
			$this->Session->setFlash(__('You have been logged in', true));
			$this->redirect(array('controller' => 'users', 'action' => 'dashboard'));
		}
	}

	function logout() {
		$this->Authsome->logout();
		$this->Session->delete('User');
		$this->redirect(array('action' => 'login'));
	}

	function forgot_password() {
		if (!empty($this->data)) {
			try {
				if ($this->Maintainer->forgotPassword($this->data)) {
					$this->Session->setFlash(__('An email has been sent with instructions for resetting your password', true));
					$this->redirect(array('controller' => 'users', 'action' => 'login'));
				} else {
					$this->Session->setFlash(__('An error occurred', true));
					$this->log("Error sending email");
				}

			} catch (Exception $e) {
				$this->__flashAndRedirect($e->getMessage(), array('controller' => 'users', 'action' => 'forgot_password'));
			}
		}
	}

	function reset_password($username = null, $key = null) {
		if ($username == null || $key == null) {
			$this->Session->setFlash(__('An error occurred', true));
			$this->redirect(array('action' => 'login'));
		}

		$maintainer = $this->Maintainer->find('resetpassword', array('username' => $username, 'key' => $key));
		if (!isset($maintainer)) {
			$this->Session->setFlash(__('An error occurred', true));
			$this->redirect(array('controller' => 'users', 'action' => 'login'));
		}

		if (!empty($this->data) && isset($this->data['Maintainer']['password'])) {
			if ($this->Maintainer->save($this->data, array('fields' => array('id', 'password', 'activation_key'), 'callback' => 'reset_password', 'user_id' => $maintainer['Maintainer']['id']))) {
				$this->Session->setFlash(__('Your password has been reset successfully', true));
				$this->redirect(array('controller' => 'users', 'action' => 'login'));
			} else {
				$this->Session->setFlash(__('An error occurred please try again', true));
			}
		}

		$this->set(compact('maintainer', 'username', 'key'));
	}

	function change_password() {
		if (!empty($this->data)) {
			if ($this->Maintainer->save($this->data, array('fieldList' => array('id', 'password'), 'callback' => 'change_password'))) {
				$this->Session->setFlash(__('Your password has been successfully changed', true));
				$this->redirect(array('action' => 'dashboard'));
			} else {
				$this->Session->setFlash(__('Your password could not be changed', true));
			}
		}
	}
}
?>