<?php
/**
 * UserRolesForm Helper
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('AppHelper', 'View/Helper');
App::uses('CakeNumber', 'Utility');
App::uses('UserAttributesRolesController', 'UserRoles.Controller');
App::uses('DataType', 'DataTypes.Model');

/**
 * UserRolesForm Helper
 *
 * @package NetCommons\UserRoles\View\Helper
 */
class UserRoleFormHelper extends AppHelper {

/**
 * 使用ヘルパー
 *
 * @var array
 */
	public $helpers = array(
		'Form',
		'NetCommons.NetCommonsForm',
		'NetCommons.NetCommonsHtml',
	);

/**
 * タブ出力
 *
 * @param string $activeKey アクティブキー
 * @return string HTMLタグ
 */
	public function settingTabs($activeKey) {
		$html = '';

		$tabs = array(
			UserRolesAppController::WIZARD_USER_ROLES => array(
				'label' => __d('user_roles', 'General setting'),
				'url' => array(
					'controller' => 'user_roles',
					'action' => 'edit',
					'key' => $this->_View->viewVars['roleKey']
				)
			),
			UserRolesAppController::WIZARD_USER_ROLES_PLUGINS => array(
				'label' => __d('user_roles', 'Select site-manager plugins to use'),
				'url' => array(
					'controller' => 'user_roles_plugins',
					'action' => 'edit',
					'key' => $this->_View->viewVars['roleKey']
				)
			),
			UserRolesAppController::WIZARD_USER_ATTRIBUTES_ROLES => array(
				'label' => __d('user_roles', 'Information policy setting'),
				'url' => array(
					'controller' => 'user_attributes_roles',
					'action' => 'edit',
					'key' => $this->_View->viewVars['roleKey']
				)
			),
		);

		if (in_array($this->_View->viewVars['roleKey'], UserRole::$systemRoles, true)) {
			unset($tabs[UserRolesAppController::WIZARD_USER_ROLES_PLUGINS]);
			unset($tabs[UserRolesAppController::WIZARD_USER_ATTRIBUTES_ROLES]);
		} elseif (! Hash::get($this->_View->data, 'UserRoleSetting.is_site_plugins')) {
			unset($tabs[UserRolesAppController::WIZARD_USER_ROLES_PLUGINS]);
		}

		$html .= '<ul class="nav nav-tabs" role="tablist">';

		foreach ($tabs as $key => $tab) {
			if ($key === $activeKey) {
				$activeClass = ' class="active"';
			} else {
				$activeClass = '';
			}
			$html .= '<li' . $activeClass . '>';
			$html .= $this->NetCommonsHtml->link($tab['label'], $tab['url']);
			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}

/**
 * コピー元の権限のSELECTボックス出力
 *
 * @param string $fieldName フィールド名(Modelname.fieldname形式)
 * @param array $attributes タグ属性
 * @return string Formatted HTMLタグ
 */
	public function selectOriginUserRoles($fieldName, $attributes = array()) {
		$html = '';

		if (Hash::get($this->data, 'UserRole.0.is_system')) {
			return $html;
		}

		$domId = $this->domId($fieldName);
		$ngInit = $domId . ' = \'' . Hash::get($this->data, $fieldName) . '\';';

		$html .= '<div class="form-group" ng-init="' . $ngInit . '">';

		if ($this->_View->request->params['action'] === 'edit' || isset($attributes['userRoleKey'])) {
			$html .= $this->NetCommonsForm->label($fieldName, $attributes['label']);
		} else {
			$attributes = Hash::merge(array(
				'type' => 'select',
				'div' => false,
				'options' => $this->_View->viewVars['userRolesName'],
				'ng-model' => $this->domId($fieldName),
			), $attributes);
			$html .= $this->NetCommonsForm->input($fieldName, $attributes);
		}

		$html .= $this->displayUserRoleDescriptions($fieldName, $attributes);

		$html .= '</div>';

		return $html;
	}

/**
 * コピー元の権限の説明出力
 *
 * @param string $fieldName フィールド名(Modelname.fieldname形式)
 * @param array $attributes タグ属性
 * @return string Formatted HTMLタグ
 */
	public function displayUserRoleDescriptions($fieldName, $attributes = array()) {
		$html = '';

		$html .= '<div class="table-responsive">';
		$html .= '<table class="table user-roles-origin-role-key">';
		$html .= '<tbody>';

		if (Hash::get($this->data, 'UserRoleSetting.id')) {
			$key = Hash::get($this->data, $fieldName);
			$userRoleName = Hash::get($this->_View->viewVars['userRolesName'], $key);
			$description = Hash::get($this->_View->viewVars['userRolesDescription'], $key);

			$html .= '<tr ng-class="{active: ' . $this->domId($fieldName) . ' === \'' . $key . '\'}">';
			$html .= '<td><div class="text-nowrap">' . $userRoleName . '</div></td>';
			$html .= '<td>' . $description . '</td>';
			$html .= '</tr>';
		} else {
			$userRoleKey = Hash::get($this->data, 'UserRoleSetting.role_key');
			foreach ($this->_View->viewVars['userRolesDescription'] as $key => $description) {
				if ($userRoleKey === $key) {
					continue;
				}
				$userRoleName = Hash::get($this->_View->viewVars['userRolesName'], $key);

				$html .= '<tr ng-class="{active: ' . $this->domId($fieldName) . ' === \'' . $key . '\'}">';
				$html .= '<td><div class="text-nowrap">' . $userRoleName . '</div></td>';
				$html .= '<td>' . $description . '</td>';
				$html .= '</tr>';
			}
		}

		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}

/**
 * ユーザ毎のプラグインの利用(サイト管理系プラグイン)checkboxの出力
 *
 * @param string $fieldName フィールド名(Modelname.fieldname形式)
 * @param array $attributes タグ属性
 * @return string HTMLタグ
 */
	public function checkboxUserRole($fieldName, $attributes = array()) {
		$html = '';

		$originRoleKey = $this->_View->data['UserRoleSetting']['origin_role_key'];
		if ($originRoleKey === UserRole::USER_ROLE_KEY_COMMON_USER) {
			$html .= $this->NetCommonsForm->checkbox($fieldName,
				Hash::merge($attributes, array('disabled' => true)
			));
		} else {
			$html .= $this->NetCommonsForm->checkbox($fieldName,
				Hash::merge($attributes, array()
			));
		}

		return $html;
	}

/**
 * 権限管理の個人情報設定SELECTボックス出力
 *
 * @param array $userAttribute UserAttributeデータ
 * @return string HTMLタグ
 */
	public function selectUserAttributeRole($userAttribute) {
		$userAttributeKey = $userAttribute['UserAttribute']['key'];
		$userAttributeRole = Hash::extract(
			$this->_View->request->data['UserAttributesRole'],
			'{n}.UserAttributesRole[user_attribute_key=' . $userAttributeKey . ']'
		);

		$id = $userAttributeRole[0]['id'];
		$fieldName = 'UserAttributesRole.' . $id . '.UserAttributesRole.other_user_attribute_role';

		if ($userAttributeRole[0]['other_editable']) {
			$this->_View->request->data = Hash::insert(
				$this->_View->request->data, $fieldName, UserAttributesRolesController::OTHER_EDITABLE
			);
		} elseif ($userAttributeRole[0]['other_readable']) {
			$this->_View->request->data = Hash::insert(
				$this->_View->request->data, $fieldName, UserAttributesRolesController::OTHER_READABLE
			);
		} else {
			$this->_View->request->data = Hash::insert(
				$this->_View->request->data, $fieldName, UserAttributesRolesController::OTHER_NOT_READABLE
			);
		}

		$ngModel = $this->domId($fieldName);
		$ngValue = Hash::get($this->_View->request->data, $fieldName);

		$html = '';
		$html .= '<div class="form-group user-attribute-roles-edit" ' .
						'ng-init="' . $ngModel . ' = \'' . $ngValue . '\'">';
		$html .= '<div class="input-group input-group-sm" ' .
						'ng-class="{\'bg-success\': ' . $ngModel . ' !== \'other_not_readable\'}">';

		$label = h($userAttribute['UserAttribute']['name']);
		if ($userAttribute['UserAttributeSetting']['required']) {
			$label .= $this->_View->element('NetCommons.required', array('size' => 'h5'));
		}
		$html .= $this->Form->label($fieldName, $label, array('class' => 'input-group-addon'));

		if ($this->_View->request->data['UserRoleSetting']['is_usable_user_manager']) {
			$disabled = true;
		} elseif ($this->_View->params['controller'] === 'user_role_add') {
			$disabled = false;
			$html .= $this->Form->hidden(
				'UserAttributesRole.' . $id . '.UserAttributesRole.id', array('value' => false)
			);
			$html .= $this->Form->hidden(
				'UserAttributesRole.' . $id . '.UserAttributesRole.role_key', array('value' => false)
			);
			$html .= $this->Form->hidden(
				'UserAttributesRole.' . $id . '.UserAttributesRole.user_attribute_key'
			);
		} else {
			$disabled = false;
			$html .= $this->Form->hidden(
				'UserAttributesRole.' . $id . '.UserAttributesRole.id'
			);
			$html .= $this->Form->hidden(
				'UserAttributesRole.' . $id . '.UserAttributesRole.role_key'
			);
			$html .= $this->Form->hidden(
				'UserAttributesRole.' . $id . '.UserAttributesRole.user_attribute_key'
			);
		}

		$options = $this->__optionsUserAttributeRole($userAttribute);
		if (count($options) <= 1) {
			$disabled = true;
		}
		$attributes = array(
			'type' => 'select',
			'class' => 'form-control',
			'empty' => false,
			'disabled' => $disabled,
			'ng-model' => $ngModel,
		);
		$html .= $this->Form->select($fieldName, $options, $attributes);

		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

/**
 * 権限管理の個人情報設定SELECTボックス出力
 *
 * @param array $userAttribute UserAttributeデータ
 * @return string HTMLタグ
 */
	public function viewUserAttributeRole($userAttribute) {
		$userAttributeKey = $userAttribute['UserAttribute']['key'];
		$userAttributeRole = Hash::extract(
			$this->_View->request->data['UserAttributesRole'],
			'{n}.UserAttributesRole[user_attribute_key=' . $userAttributeKey . ']'
		);

		$id = $userAttributeRole[0]['id'];
		$fieldName = 'UserAttributesRole.' . $id . '.UserAttributesRole.other_user_attribute_role';

		$ngModel = $this->domId($fieldName);
		if ($userAttributeRole[0]['other_editable']) {
			$ngValue = UserAttributesRolesController::OTHER_EDITABLE;
		} elseif ($userAttributeRole[0]['other_readable']) {
			$ngValue = UserAttributesRolesController::OTHER_READABLE;
		} else {
			$ngValue = UserAttributesRolesController::OTHER_NOT_READABLE;
		}

		$html = '';
		$html .= '<div class="form-group user-attribute-roles-edit" ' .
						'ng-init="' . $ngModel . ' = \'' . $ngValue . '\'" ' .
						'ng-show="' . $ngModel . ' !== \'other_not_readable\'">';
		$html .= '<div class="input-group input-group-sm" ' .
						'ng-class="{\'bg-success\': ' . $ngModel . ' !== \'other_not_readable\'}">';

		$label = h($userAttribute['UserAttribute']['name']);
		if ($userAttribute['UserAttributeSetting']['required']) {
			$label .= $this->_View->element('NetCommons.required', array('size' => 'h5'));
		}
		$html .= $this->Form->label($fieldName, $label, array('class' => 'input-group-addon'));

		if ($this->_View->request->data['UserRoleSetting']['is_usable_user_manager']) {
			$disabled = true;
		} else {
			$disabled = false;
		}

		$options = $this->__optionsUserAttributeRole($userAttribute);
		if (count($options) <= 1) {
			$disabled = true;
		}

		$html .= '<div class="form-control text-nowrap">';
		$html .= Hash::get($options, $ngValue);
		$html .= '</div>';

		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

/**
 * 権限管理の個人情報設定のOptions
 *
 * @param array $userAttribute UserAttributeデータ
 * @return string HTMLタグ
 */
	private function __optionsUserAttributeRole($userAttribute) {
		$userAttributeKey = $userAttribute['UserAttribute']['key'];
		$userAttributeRole = Hash::extract(
			$this->_View->request->data['UserAttributesRole'],
			'{n}.UserAttributesRole[user_attribute_key=' . $userAttributeKey . ']'
		);

		$otherReadable = UserAttributesRolesController::OTHER_READABLE;
		$otherNotReadable = UserAttributesRolesController::OTHER_NOT_READABLE;
		$dataTypeKey = $userAttribute['UserAttributeSetting']['data_type_key'];

		if (! $userAttribute['UserAttributeSetting']['display']) {
			//会員項目で非表示の場合「閲覧できない」
			$options = array(
				$otherNotReadable => __d(
					'user_roles', 'For non-display in the user role setting, can not read'
				),
			);
		} elseif ($userAttributeRole[0]['user_attribute_key'] === 'handlename') {
			//ハンドルの場合、「閲覧させない」を除外する
			$options = array(
				$otherReadable => __d('user_roles', 'Other members can also read'),
			);
		} elseif ($dataTypeKey === DataType::DATA_TYPE_PASSWORD) {
			//パスワードの場合、「閲覧させる」を除外する
			$options = array(
				$otherNotReadable => __d('user_roles', 'Other members can not also read'),
			);
		} elseif (! $userAttribute['UserAttributeSetting']['only_administrator_readable'] ||
				$this->_View->request->data['UserRoleSetting']['is_usable_user_manager']) {
			$options = array(
				$otherNotReadable => __d('user_roles', 'Other members can not read'),
				$otherReadable => __d('user_roles', 'Other members can also read'),
			);
		} else {
			$options = array(
				$otherNotReadable => __d('user_roles', 'Other members can not also read'),
			);
		}

		//以下の場合、編集の選択肢は表示させない
		// * ラベルタイプ
		// * 会員管理が使用できない
		if ($dataTypeKey === DataType::DATA_TYPE_LABEL ||
				! $this->_View->request->data['UserRoleSetting']['is_usable_user_manager']) {
			return $options;
		}

		//編集の選択肢を表示する(会員管理が使える場合のみ)
		$options[UserAttributesRolesController::OTHER_EDITABLE] = __d(
			'user_roles', 'Other members can also edited'
		);

		return $options;
	}

}
