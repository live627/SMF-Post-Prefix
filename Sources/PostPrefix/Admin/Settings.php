<?php

/**
 * @package SMF Post Prefix
 * @version 4.0
 * @author Diego Andrés <diegoandres_cortes@outlook.com>
 * @copyright Copyright (c) 2022, SMF Tricks
 * @license https://www.mozilla.org/en-US/MPL/2.0/
 */

namespace PostPrefix\Admin;

if (!defined('SMF'))
	die('No direct access...');

class Settings
{
	 /**
	 * Settings::hookAreas()
	 *
	 * Adding the admin section
	 * @param array $admin_areas The admin areas/sections
	 * @return void
	 */
	public static function hookAreas(array &$admin_areas) : void
	{
		global $txt;

		// Load languages
		loadLanguage('PostPrefix/');

		// Set permission to see the area
		$admin_areas['layout']['permission'][] = 'postprefix_manage';

		// Add it after posts settings
		$insert = 'postsettings';
		$counter = 0;
		foreach ($admin_areas['layout']['areas'] as $area => $dummy)
			if (++$counter && $area == $insert )
				break;

		// Add the prefixes area to the menu
		$admin_areas['layout']['areas'] = array_merge(
			array_slice($admin_areas['layout']['areas'], 0, $counter),
			[
				'postprefix' => [
					'label' => $txt['PostPrefix_main'],
					'icon' => 'reports',
					'function' => __CLASS__ . '::index#',
					'permission' => ['postprefix_manage'],
					'subsections' => [
						'prefixes' => [$txt['PostPrefix_tab_prefixes']],
						'add' => [$txt['PostPrefix_tab_prefixes_add']],
						'options' => [$txt['PostPrefix_tab_options']],
					],
				],
			],
			array_slice($admin_areas['layout']['areas'], $counter)
		);

		// Permissions
		add_integration_function('integrate_load_permissions', 'PostPrefix\Integration\Permissions::load_permissions', false);
		add_integration_function('integrate_load_illegal_guest_permissions', 'PostPrefix\Integration\Permissions::illegal_guest', false);

		// Language
		add_integration_function('integrate_helpadmin', 'PostPrefix\Integration\Permissions::language', false);

		// Boards settings
		if (isset($_REQUEST['area']) && $_REQUEST['area'] == 'manageboards')
		{
			add_integration_function('integrate_edit_board', 'PostPrefix\Integration\Boards::edit_board', false);
			add_integration_function('integrate_modify_board', 'PostPrefix\Integration\Boards::modify_board', false);
		}
	}

	/**
	 * Settings::index()
	 * 
	 * Provides the subactions, the template and loads the correct method
	 * 
	 * @return void
	 */
	public function index() : void
	{
		global $context, $txt;

		// Create the tabs for the template.
		$context[$context['admin_menu_name']]['tab_data'] = [
			'title' => $txt['PostPrefix_tab_prefixes'],
			'description' => $txt['PostPrefix_tab_prefixes_desc'],
			'tabs' => [
				'prefixes' => ['description' => $txt['PostPrefix_tab_prefixes_desc']],
				'add' => ['description' => $txt['PostPrefix_tab_prefixes_add_desc']],
				'options' => ['description' => $txt['PostPrefix_tab_options_desc']],
			],
		];

		// Template
		loadtemplate('PostPrefix');

		// List of subactions
		$subactions = [
			'prefixes' => 'Manage::list',
			'add' => 'Manage::set_prefix#',
			'edit' => 'Manage::set_prefix#',
			'save' => 'Manage::save#',
			'delete' => 'Manage::delete',
			'status' => 'Manage::status',
			'groups' => 'Manage::groups#',
			'boards' => 'Manage::boards#',
			'options' => 'Settings::options',
		];
		
		call_helper(__NAMESPACE__ . '\\' . $subactions[isset($_REQUEST['sa']) ? $_REQUEST['sa'] : 'prefixes']);
	}

	/**
	 * Settings::options()
	 * 
	 * Provides the main settings
	 * 
	 * @return void
	 */
	public static function options() : void
	{
		global $context, $txt, $sourcedir, $scripturl, $modSettings;

		require_once($sourcedir . '/ManageServer.php');
		loadLanguage('ManageSettings');

		// Set all the page stuff
		$context['sub_template'] = 'show_settings';
		$context['page_title'] = $txt['PostPrefix_main']. ' - ' . $txt['PostPrefix_tab_options'];
		$context[$context['admin_menu_name']]['tab_data']['title'] = $context['page_title'];

		// Settings
		$config_vars = [
			['title', 'PostPrefix_tab_options'],
			['check', 'PostPrefix_enable_filter', 'subtext' => $txt['PostPrefix_enable_filter_desc']],
			['boards', 'PostPrefix_filter_boards', 'subtext' => $txt['PostPrefix_filter_boards_desc']],
			['select', 'PostPrefix_select_order', [
					$txt['PostPrefix_prefix_name'],
					$txt['PostPrefix_prefix_id'],
				],
				'help' => $txt['PostPrefix_select_order_desc']
			],
			['select', 'PostPrefix_post_selecttype', [
					$txt['PostPrefix_post_selecttype_select'],
					$txt['PostPrefix_post_selecttype_radio'],
				],
				'subtext' => $txt['PostPrefix_post_selecttype_desc1'],
				'help' => $txt['PostPrefix_post_selecttype_desc2']
			],
			'',
			['boards', 'PostPrefix_prefix_boards_require', 'subtext' => $txt['PostPrefix_prefix_boards_require_desc']],
			['check', 'PostPrefix_no_prefix_remove'],
			'',
			['permissions', 'postprefix_manage', 'label' => $txt['permissionname_postprefix_manage'], 'help' => $txt['permissionhelp_postprefix_manage']],
			['permissions', 'postprefix_set', 'label' => $txt['permissionname_postprefix_set'], 'help' => $txt['permissionhelp_postprefix_set']],
			'',
			['check', 'PostPrefix_prefix_linktree', 'subtext' => $txt['PostPrefix_prefix_linktree_desc']],
			['check', 'PostPrefix_prefix_boardindex', 'subtext' => $txt['PostPrefix_prefix_boardindex_desc']],
			['check', 'PostPrefix_prefix_all_msgs', 'subtext' => $txt['PostPrefix_prefix_all_msgs_desc'], 'disabled' => empty($modSettings['PostPrefix_prefix_boardindex'])],
			['check', 'PostPrefix_prefix_recent_page', 'subtext' => $txt['PostPrefix_prefix_recent_page_desc']],
		];

		// Post URL
		$context['post_url'] = $scripturl . '?action=admin;area=postprefix;sa=options;save';

		// Saving?
		if (isset($_GET['save'])) {
			checkSession();
			saveDBSettings($config_vars);
			redirectexit('action=admin;area=postprefix;sa=options');
		}
		prepareDBSettingContext($config_vars);
	}
}