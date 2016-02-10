<?php
/**
 * UserRolesController::index()のテスト
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsControllerTestCase', 'NetCommons.TestSuite');

/**
 * UserRolesController::index()のテスト
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\UserRoles\Test\Case\Controller\UserRolesController
 */
class UserRolesControllerIndexTest extends NetCommonsControllerTestCase {

/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array(
		'plugin.user_roles.user_attributes_role',
		'plugin.user_roles.user_role_setting',
	);

/**
 * Plugin name
 *
 * @var string
 */
	public $plugin = 'user_roles';

/**
 * Controller name
 *
 * @var string
 */
	protected $_controller = 'user_roles';

/**
 * index()アクションのテスト
 *
 * @return void
 */
	public function testIndex() {
		TestAuthGeneral::login($this);

		//テスト実行
		$this->_testGetAction(array('action' => 'index'), array('method' => 'assertNotEmpty'), null, 'view');

		//チェック
		$this->assertActionLink('add', array('action' => 'add'), true, $this->view);
		$this->assertNotContains('/user_roles/user_roles/edit/system_administrator', $this->view);

		$pattern = '/<a href=".*?' . preg_quote('/user_roles/user_roles/edit/administrator', '/') . '">/';
		$this->assertRegExp($pattern, $this->view);

		$pattern = '/<a href=".*?' . preg_quote('/user_roles/user_roles/edit/common_user', '/') . '">/';
		$this->assertRegExp($pattern, $this->view);

		TestAuthGeneral::logout($this);
	}

}
