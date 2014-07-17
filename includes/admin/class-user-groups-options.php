<?php 

	/**
	 * User Group Studiorum Options
	 *
	 * @package     Studiorum User Groups
	 * @subpackage  Studiorum/User Groups/Options
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       0.1.0
	 */

	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ){
		exit;
	}

	class Studiorum_User_Groups_Options
	{

		/**
		 * Actions and filters
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function __construct()
		{

			// Add our options to the studiorum options panel
			add_action( 'studiorum_settings_setup_start', array( $this, 'studiorum_settings_setup_start__addFilters' ) );

		}/* __construct() */


		/**
		 * Add our filters to add our options to the main studiorum settings panel
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function studiorum_settings_setup_start__addFilters()
		{

			// Add the settings section
			add_filter( 'studiorum_settings_settings_sections', array( $this, 'studiorum_settings_settings_sections__addUserGroupSettingsSection' ) );

			// Add the fields to the new section
			add_filter( 'studiorum_settings_settings_fields', array( $this, 'studiorum_settings_settings_fields__addUserGroupSettingsFields' ) );

		}/* studiorum_settings_setup_start__addFilters() */


		/**
		 * Add the user groups settings section to the Studiorum settings panel
		 *
		 * @since 0.1
		 *
		 * @param array $settingsSections existing settings sections
		 * @return array $settingsSections modified settings sections
		 */

		public function studiorum_settings_settings_sections__addUserGroupSettingsSection( $settingsSections )
		{

			if( !$settingsSections || !is_array( $settingsSections ) ){
				$settingsSections = array();
			}

			$settingsSections[] = array(
				'section_id'	=>	'user_group_options',
				'tab_slug'		=>	'lectio',
				'order'			=> 	2,
				'title'			=>	__( 'User Group Settings', 'studiorum-user-groups' ),
				'description'	=>	__( 'As you have the User Group add-on enabled, you are now able to determine what happens to submissions for users in a group.', 'studiorum-user-groups' ),
			);

			return $settingsSections;

		}/* studiorum_settings_settings_sections__addUserGroupSettingsSection() */


		/**
		 * Add the user group fields to the Studiorum settings panel
		 *
		 * @since 0.1
		 *
		 * @param array $settingsFields existing settings fields
		 * @return array $settingsFields modified settings fields
		 */

		public function studiorum_settings_settings_fields__addUserGroupSettingsFields( $settingsFields )
		{

			if( !$settingsFields || !is_array( $settingsFields ) ){
				$settingsFields = array();
			}

			$settingsFields[] = array(	// Single Drop-down List
				'field_id'	=>	'studiorum_user_groups_groups_see_each_others_submissions',
				'section_id'	=>	'user_group_options',
				'title'	=>	__( 'Group Submission Visibility?', 'studiorum-user-groups' ),
				'type'	=>	'select',
				'default'	=>	'true',	// the index key of the label array below which yields 'Yellow'.
				'label'	=>	array( 
					'true'	=>	'True',		
					'false'	=>	'False'
				),
				'description'	=>	__( 'When a student submits a piece of work, should others in that student\'s group also be able to see it?', 'studiorum-user-groups' ),
			);

			return $settingsFields;

		}/* studiorum_settings_settings_fields__addUserGroupSettingsFields() */

	}/* class Studiorum_User_Groups_Options */

	$Studiorum_User_Groups_Options = new Studiorum_User_Groups_Options;