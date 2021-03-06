<?php
/**
 * 権限管理の詳細設定テンプレート
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

echo $this->NetCommonsHtml->css(array(
	'/user_roles/css/style.css',
	'/plugin_manager/css/style.css',
));
?>

<?php
	echo $this->UserRoleForm->settingTabs(UserRolesAppController::WIZARD_USER_ROLES_PLUGINS);
	echo $this->element('UserRoles.subtitle');
	echo $this->MessageFlash->description(
		__d('user_roles', 'Select site-manager plugin to use, and press [OK].')
	);
?>

<div class="panel panel-default">
	<?php echo $this->NetCommonsForm->create('UserRoleSetting'); ?>

	<div class="panel-body plugin-checkbox-outer clearfix">
		<?php
			$hidden = '';
			foreach ($this->data['PluginsRole'] as $i => $pluginsRole) {
				echo $this->UserRoleForm->checkboxUserRole('PluginsRole.' . $i . '.PluginsRole.is_usable_plugin',
					array(
						'label' => $pluginsRole['Plugin']['name'],
						'div' => false,
						'checked' => (bool)$pluginsRole['PluginsRole']['id'],
					)
				);
				$hidden .= $this->NetCommonsForm->hidden('PluginsRole.' . $i . '.PluginsRole.id');
				$hidden .= $this->NetCommonsForm->hidden('PluginsRole.' . $i . '.PluginsRole.role_key', array(
					'value' => $roleKey)
				);
				$hidden .= $this->NetCommonsForm->hidden('PluginsRole.' . $i . '.PluginsRole.plugin_key', array(
					'value' => $pluginsRole['Plugin']['key'])
				);
			}

			echo $hidden;
		?>
	</div>

	<div class="panel-footer text-center">
		<?php echo $this->Button->cancelAndSave(
				__d('net_commons', 'Cancel'),
				__d('net_commons', 'OK'),
				NetCommonsUrl::actionUrlAsArray(array('controller' => 'user_roles', 'action' => 'index'))
			); ?>
	</div>

	<?php echo $this->NetCommonsForm->end(); ?>

</div>
