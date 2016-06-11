<?php
/**
 * UserRoles Controller
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('UserRolesAppController', 'UserRoles.Controller');

/**
 * UserRoles Controller
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\UserRoles\Controller
 */
class UserRolesController extends UserRolesAppController {

/**
 * use model
 *
 * @var array
 */
	public $uses = array(
		'Roles.DefaultRolePermission',
		'UserRoles.UserRole',
		'UserRoles.UserRoleSetting',
	);

/**
 * use component
 *
 * @var array
 */
	public $components = array(
		'M17n.SwitchLanguage' => array(
			'fields' => array(
				'UserRole.name', 'UserRole.description'
			)
		)
	);

/**
 * beforeFilter
 *
 * @return void
 */
	public function beforeFilter() {
		parent::beforeFilter();

		if (! in_array($this->params['action'], ['add', 'edit'], true)) {
			return;
		}
		$result = $this->UserRole->find('all', array(
			'recursive' => -1,
			'fields' => array('key', 'name', 'description'),
			'conditions' => array(
				'language_id' => Current::read('Language.id')
			),
			'order' => array('id' => 'asc')
		));

		$userRoles = Hash::combine($result, '{n}.UserRole.key', '{n}.UserRole.name');
		unset($userRoles[UserRole::USER_ROLE_KEY_SYSTEM_ADMINISTRATOR]);
		$this->set('userRoles', $userRoles);

		$userRoles = Hash::combine($result, '{n}.UserRole.key', '{n}.UserRole.description');
		unset($userRoles[UserRole::USER_ROLE_KEY_SYSTEM_ADMINISTRATOR]);
		$this->set('userRolesDescription', $userRoles);
	}

/**
 * index
 *
 * @return void
 */
	public function index() {
		$userRoles = $this->UserRole->find('all', array(
			'recursive' => -1,
			'conditions' => array(
				$this->UserRole->alias . '.language_id' => Current::read('Language.id')
			)
		));
		$this->set('userRoles', $userRoles);
	}

/**
 * edit
 *
 * @param string $roleKey user_roles.key
 * @return void
 */
	public function edit($roleKey = null) {
		//既存データ取得
		$userRole = $this->UserRole->find('all', array(
			'recursive' => -1,
			'conditions' => array('key' => $roleKey)
		));
		if (! $userRole) {
			return $this->throwBadRequest();
		}

		if ($this->request->is('put')) {
			//不要パラメータ除去
			unset($this->request->data['save'], $this->request->data['active_lang_id']);

			//他言語が入力されていない場合、表示されている言語データをセット
			$this->SwitchLanguage->setM17nRequestValue();

			//登録処理
			$result = $this->UserRole->saveUserRole($this->request->data);
			if ($result) {
				//正常の場合
				$this->NetCommons->setFlashNotification(
					__d('net_commons', 'Successfully saved.'), array('class' => 'success')
				);
				return $this->redirect('/user_roles/user_roles/index');
			}
			$this->NetCommons->handleValidationError($this->UserRole->validationErrors);

			$extract = Hash::extract(
				$userRole, '{n}.UserRole[language_id=' . Current::read('Language.id') . ']'
			);
			$this->request->data['UserRole'] = Hash::insert(
				$this->request->data['UserRole'], '{n}.is_system', Hash::get($extract, '0.is_system')
			);

		} else {
			$this->request->data['UserRole'] = Hash::extract($userRole, '{n}.UserRole');
		}

		$result = $this->UserRoleSetting->find('first', array(
			'recursive' => -1,
			'conditions' => array('role_key' => $roleKey),
		));
		$this->request->data = Hash::merge($this->request->data, $result);

		$this->set('roleKey', $roleKey);
		$this->set('subtitle', Hash::get($this->viewVars['userRoles'], $roleKey, ''));
		$this->set('isDeletable', $this->UserRole->verifyDeletable($roleKey));

		$defaultPermission = $this->DefaultRolePermission->getDefaultRolePermissions(
			$roleKey, 'group_creatable', DefaultRolePermission::TYPE_USER_ROLE
		);
		$this->request->data['DefaultRolePermission'] = $defaultPermission;
	}

/**
 * delete
 *
 * @return void
 */
	public function delete() {
		if (! $this->request->is('delete')) {
			return $this->throwBadRequest();
		}
		if (! $this->UserRole->deleteUserRole($this->data['UserRole'][0])) {
			$message = Hash::get($this->UserRole->validationErrors, 'key.0');
			$this->NetCommons->handleValidationError($this->UserRole->validationErrors, $message);
			return $this->redirect('/user_roles/user_roles/edit/' . $this->data['UserRole'][0]['key']);
		} else {
			return $this->redirect('/user_roles/user_roles/index/');
		}
	}
}
