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
				'type'	=>	'select',
				'title'	=>	__( 'Group Submission Visibility?', 'studiorum-user-groups' ) . '<span class="label-note">' . __( 'When a student submits a piece of work, should others in that student\'s group also be able to see it?' ) . '</span>',
				'help'			=>	__( 'As you have the group submissions add-on, you are able to determine the visibility of submissions within a group. i.e. if one student submits an assignment, should members of the group(s) that the submitter is in also be able to see that submission. This is a global setting, not on an assignment-by-assignment basis.', 'studiorum-lectio' ),
				'help_aside'	=>	__( '', 'studiorum-user-groups' ),
				'default'	=>	'true',	// the index key of the label array below which yields 'Yellow'.
				'label'	=>	array( 
					'true'	=>	'True',		
					'false'	=>	'False'
				),
				'attributes'	=>	array(
					'select'	=>	array(
						'style'	=>	"width: 285px;",
					),
				)
			);

			return $settingsFields;

		}/* studiorum_settings_settings_fields__addUserGroupSettingsFields() */

	}/* class Studiorum_User_Groups_Options */

	$Studiorum_User_Groups_Options = new Studiorum_User_Groups_Options;