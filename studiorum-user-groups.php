<?php
	/*
	 * Plugin Name: Studiorum User Groups
	 * Description: Allows you to create groups of users
	 * Version:     0.1
	 * Plugin URI:  #
	 * Author:      UBC, CTLT, Richard Tape
	 * Author URI:  http://ubc.ca/
	 * Text Domain: studiorum-user-groups
	 * License:     GPL v2 or later
	 * Domain Path: languages
	 *
	 * studiorum-user-groups is free software: you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation, either version 2 of the License, or
	 * any later version.
	 *
	 * studiorum-user-groups is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with studiorum-user-groups. If not, see <http://www.gnu.org/licenses/>.
	 *
	 * @package User Groups
	 * @category Core
	 * @author Richard Tape
	 * @version 0.1.0
	 */

	if( !defined( 'ABSPATH' ) ){
		die( '-1' );
	}

	if( !defined( 'STUDIORUM_USER_GROUPS_DIR' ) ){
		define( 'STUDIORUM_USER_GROUPS_DIR', plugin_dir_path( __FILE__ ) );
	}

	if( !defined( 'STUDIORUM_USER_GROUPS_URL' ) ){
		define( 'STUDIORUM_USER_GROUPS_URL', plugin_dir_url( __FILE__ ) );
	}

	class Studiorum_User_Groups
	{

		// The option name stored in the db
		var $optionName = 'studiorum_user_groups';

		// This site's users
		var $thisSitesUsers = array();

		// Are we editing a group?
		var $editingGroup = false;

		// For JS Vars
		var $localizationData = array();

		// When we add a group, store the new data so we can access it in AJAX hooks
		var $newGroupDataSanitized = false;

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

			if( !class_exists( 'WP_List_Table' ) ){
				require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
			}

			if( !is_admin() ){
				
				// Studiorum Lectio gives us the ability to filter which users can see private posts other than the author, let's add a group of users
				add_filter( 'studiorum_lectio_specific_users_who_can_see_private_submissions', array( $this, 'studiorum_lectio_specific_users_who_can_see_private_submissions__addUsersGroups' ), 10, 3 );
				
			}

			// Load our necessary includes
			add_action( 'after_setup_theme', array( $this, 'after_setup_theme__includes' ), 1 );

			add_action( 'admin_menu', array( $this, 'admin_menu__registerUserGroupsAdminPage' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts__loadJS' ) );

			// When the add user group form is submitted
			add_action( 'studiorum_user_groups_add_new_group_submitted', array( $this, 'studiorum_user_groups_add_new_group_submitted__processNewGroupSubmission' ) );

			// When a group needs to be edited
			add_action( 'studiorum_user_groups_edit_group_submitted', array( $this, 'studiorum_user_groups_edit_group_submitted__editExistingGroup' ) );

			// When a group has been edited
			add_action( 'studiorum_user_groups_edited_group_submitted', array( $this, 'studiorum_user_groups_edited_group_submitted__processEditGroup' ) );

			// Handle bulk delete
			add_action( 'studiorum_user_groups_bulk_delete', array( $this, 'studiorum_user_groups_bulk_delete__handleBulkDelete' ) );

			// Register ourself as an addon
			add_filter( 'studiorum_modules', array( $this, 'studiorum_modules__registerAsModule' ) );

			// Change message shown to authors for front-end submission author notice
			add_filter( 'studiorum_lectio_author_note_above_submission', array( $this, 'studiorum_lectio_author_note_above_submission__addGroupToNotice' ) );

			// After we 'add' a group, we run an action, which we hook into to update the list table
			add_action( 'wp_ajax_studiorum_user_groups_add_group', array( $this, 'wp_ajax_studiorum_user_groups_add_group' ) );

		}/* __construct() */


		/**
		 * Load our includes
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function after_setup_theme__includes()
		{

			require_once( trailingslashit( STUDIORUM_USER_GROUPS_DIR ) . 'includes/admin/class-user-groups-options.php' );
			require_once( trailingslashit( STUDIORUM_USER_GROUPS_DIR ) . 'includes/admin/class-user-groups-list-table.php' );
			require_once( trailingslashit( STUDIORUM_USER_GROUPS_DIR ) . 'includes/class-studiorum-user-groups-utils.php' );

			// Integrations
			require_once( trailingslashit( STUDIORUM_USER_GROUPS_DIR ) . 'includes/integrations/studiorum-lectio/class-integration-studiorum-lectio.php' );

		}/* after_setup_theme__includes() */


		/**
		 * Enqueue our JS. Mainly for selectize initially. Soon to be cusotm JS to assign groups automatically
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function admin_enqueue_scripts__loadJS( $hook )
		{

			if( $hook != 'users_page_studiorum-user-groups' ){
				return;
			}

			wp_enqueue_script( 'selectize', trailingslashit( STUDIORUM_USER_GROUPS_URL ) . 'includes/admin/assets/js/selectize.js', array( 'jquery' ) );
			wp_enqueue_script( 'selectize-loader', trailingslashit( STUDIORUM_USER_GROUPS_URL ) . 'includes/admin/assets/js/selectize-loader.js', array( 'jquery', 'selectize' ) );
			
			wp_enqueue_style( 'selectize', trailingslashit( STUDIORUM_USER_GROUPS_URL ) . 'includes/admin/assets/css/selectize.css' );
			wp_enqueue_style( 'selectize-custom', trailingslashit( STUDIORUM_USER_GROUPS_URL ) . 'includes/admin/assets/css/selectize.custom.css' );

			// Also load our AJAX methods JS
			wp_enqueue_script( 'studiorum-user-groups-ajax', trailingslashit( STUDIORUM_USER_GROUPS_URL ) . 'includes/admin/assets/js/admin-table-ajax.js', array( 'jquery', 'selectize-loader' ) );

			// We need to pass some variables to our JS
			global $localizationData;
			$localizationData = array();

			$usableUsers = $this->getUserDataForJS();

			$localizationData['userData'] = $usableUsers;

			// If we're editing, we should pass the group's users to the JS
			if( $this->editingGroup || ( isset( $_GET['action'] ) && sanitize_text_field( $_GET['action'] ) == 'edit-user-group' ) )
			{

				// Which group are we editing
				$editingGroup = isset( $_REQUEST['user-group'] ) ? sanitize_title_with_dashes( $_REQUEST['user-group'] ) : sanitize_title_with_dashes( $_GET['user-group'] );

				$userIDs = $this->processEditData( $editingGroup, 'group-users' );

				$editingUsers = $this->getUserDataForJSFromUserIDs( $userIDs );

				$localizationData['editingUsers'] = $editingUsers;

			}

			if( isset( $_GET['action'] ) && sanitize_text_field( $_GET['action'] ) == 'delete' && isset( $_GET['action2'] ) && $_GET['action2'] == '-1' ){
				do_action( 'studiorum_user_groups_bulk_delete' );
			}

			$this->localizationData = $localizationData;

			wp_localize_script( 'selectize-loader', 'sugData', $localizationData );

		}/* admin_enqueue_scripts__loadJS() */


		/**
		 * Get this site's users and convert the data into something useful for the JS
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */
		private function getUserDataForJS()
		{

			$thisSitesUsers = $this->getThisSitesUsers();

			// Now we have a big old array of user objects, let's turn that into something useful
			// We just want user name, nicename and email
			if( !$thisSitesUsers || !is_array( $thisSitesUsers ) || empty( $thisSitesUsers ) ){
				return false;
			}

			// Start fresh
			$usableData = $this->getUserDataForJSFromUserObjects( $thisSitesUsers );

			return $usableData;

		}/* getUserDataForJS() */


		/**
		 * Get user data based on an array of user objects for JS
		 *
		 * @since 0.1
		 *
		 * @param string $param description
		 * @return string|int returnDescription
		 */

		private function getUserDataForJSFromUserObjects( $userObjects = false )
		{

			if( !$userObjects ){
				return false;
			}

			$usableData = array();

			foreach( $userObjects as $key => $userObject )
			{

				$userLogin 		= $userObject->user_login;
				$userNiceName 	= $userObject->user_nicename;
				$userEmail 		= $userObject->user_email;
				$userID 		= $userObject->ID;

				$usableData[] = array( 'userID' => $userID, 'login' => $userLogin, 'nice_name' => $userNiceName, 'email' => $userEmail );

			}

			return $usableData;

		}/* getUserDataForJSFromUserObjects() */


		/**
		 * Get usable data for JS from an array of user IDs
		 *
		 * @since 0.1
		 *
		 * @param array $userIDs an array of user IDs
		 * @return string|int returnDescription
		 */
		private function getUserDataForJSFromUserIDs( $userIDs = false )
		{

			if( !$userIDs ){
				return false;
			}

			$userObjects = array();

			foreach( $userIDs as $key => $userID )
			{

				$userObject = get_user_by( 'id', $userID );

				$userObjects[] = $userObject;

			}

			$usableData = $this->getUserDataForJSFromUserObjects( $userObjects );

			return $usableData;

		}/* getUserDataForJSFromUserIDs() */


		/**
		 * Get an array of user objects for this site
		 *
		 * @since 0.1
		 *
		 * @param string $param description
		 * @return string|int returnDescription
		 */

		public function getThisSitesUsers()
		{

			if( isset( $this->thisSitesUsers ) && is_array( $this->thisSitesUsers ) && !empty( $this->thisSitesUsers ) )
			{
				$thisSitesUsers = $this->thisSitesUsers;
			}
			else
			{

				$roleToFetch = apply_filters( 'studiorum_user_groups_fetch_users_role', 'studiorum_student' );
		
				$thisSitesUsers = $this->getUsersOfRole( $roleToFetch );

				$this->thisSitesUsers = $thisSitesUsers;

			}

			return $thisSitesUsers;

		}/* getThisSitesUsers() */


		/**
		 * Regitser the user groups admin page
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function admin_menu__registerUserGroupsAdminPage()
		{

			$userGroupsPage = add_submenu_page( 
				'users.php', 
				'User Groups', 
				'User Groups', 
				'manage_options', 
				'studiorum-user-groups', 
				array( $this, 'add_submenu_page__userGroupMarkup' )
			);

		}/* admin_menu__registerUserGroupsAdminPage() */


		/**
		 * Markup for the user groups page
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function add_submenu_page__userGroupMarkup()
		{

			echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
				echo '<h2>' . __( 'User Groups', 'studiorum-user-groups' ) . '</h2>';
			echo '</div>';

			echo '<div id="col-container">';

				echo '<div id="col-right">';

					echo '<div class="col-wrap">';

						$this->_listTableMarkup();

					echo '</div>'; // .col-wrap

				echo '</div>'; // #col-right


				echo '<div id="col-left">';

					echo '<div class="col-wrap">';

						$this->_addNewGroupMarkup();

					echo '</div>'; // .col-wrap

				echo '</div>'; // #col-left

			echo '</div>'; // #col-container

		}/* add_submenu_page__userGroupMarkup() */


		/**
		 * Method which outputs the main list table for the user groups
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		private function _listTableMarkup()
		{

			// Create an instance of our package class...
			$userGroupListTable = new User_Groups_List_Table();

			// Fetch, prepare, sort, and filter our data...
			$userGroupListTable->prepare_items();

			$value = ( isset( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : '';

			echo '<form id="user-groups-filter" method="get">';

				echo '<input type="hidden" name="page" value="' . $value . '" />';

				$userGroupListTable->display();

			echo '</form>';

		}/* _listTableMarkup() */


		/**
		 * Markup which outputs the new group fields
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		private function _addNewGroupMarkup()
		{

			global $current_screen;

			if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'add-user-group' ){
				do_action( 'studiorum_user_groups_add_new_group_submitted' );
			}

			if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edit-user-group' ){
				do_action( 'studiorum_user_groups_edit_group_submitted' );
			}

			if( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'edited-user-group' ){
				do_action( 'studiorum_user_groups_edited_group_submitted' );
			}

			// Preload some values, but if we're editing, we grab them
			$titleValue = '';
			$userGroupValue = '';
			$editingGroup = '';
			$disabled = '';

			$submitButtonText = __( 'Add User Group', 'studiorum-user-groups' );

			$formAction = 'add-user-group';

			if( $this->editingGroup )
			{

				// Which group are we editing
				$editingGroup = isset( $_REQUEST['user-group'] ) ? sanitize_title_with_dashes( $_REQUEST['user-group'] ) : false;

				$titleValue 	= $this->processEditData( $editingGroup, 'group-title' );
				$userGroupValue = $this->processEditData( $editingGroup, 'group-users' );

				$formAction = 'edited-user-group';

				$disabled = 'disabled="disabled"';

				$submitButtonText = __( 'Edit User Group', 'studiorum-user-groups' );



			}

			?>

			<div class="form-wrap">

				<h3><?php __( 'Add New Group', 'studiorum-user-groups' ); ?></h3>

				<?php do_action( 'studiorum_user_groups_add_new_group_before_form' ); ?>

				<form id="add-user-group" method="post" action="" class="validate">

					<input type="hidden" name="action" value="<?php echo $formAction; ?>" />
					<input type="hidden" name="screen" value="<?php echo esc_attr( $current_screen->id ); ?>" />
					<input type="hidden" name="editGroupID" value="<?php echo esc_attr( $editingGroup ); ?>" />
					
					<?php wp_nonce_field( 'add-user-group', '_wpnonce_add-user-group' ); ?>

					<?php do_action( 'studiorum_user_groups_add_new_group_form_start' ); ?>

					<div class="form-field form-required">
						<label for="group-title"><?php _ex( 'Title', 'User Group Title' ); ?></label>
						<input name="group-title" id="group-title" type="text" value="<?php echo $titleValue; ?>" size="40" aria-required="true" <?php echo $disabled; ?> />
						<p><?php _e( 'A recognizable title. This is non-editable after creation.', 'studiorum-user-groups' ); ?></p>
					</div>

					<div class="form-field form-required">
						<label for="group-users"><?php _ex( 'Users', 'Users List Title' ); ?></label>
						<input name="group-users" id="group-users" type="text" value="<?php if( $userGroupValue != '' ){ echo implode( ',', $userGroupValue ); } ?>" size="40" aria-required="true" />
						<p><?php _e( 'Select the users you want in this group.', 'studiorum-user-groups' ); ?></p>
					</div>

					<?php do_action( 'studiorum_user_groups_add_new_group_form_end' ); ?>

					<?php submit_button( $submitButtonText ); ?>

					<?php do_action( 'studiorum_user_groups_add_new_group_form_after_submit' ); ?>

				</form>

			</div>

			<?php do_action( 'studiorum_user_groups_add_new_group_after_form' ); ?>

			<?php

		}/* _addNewGroupMarkup() */


		/**
		 * If we're editing, we have this method to sanitize the request data and fetch the relevant info from the options
		 *
		 * @since 0.1
		 *
		 * @param string $editingGroup Which group we are editing
		 * @param string $field which field we want
		 * @return string The value of the field requested
		 */

		private function processEditData( $editingGroup = false, $field = false )
		{

			if( !$editingGroup || !$field ){
				return false;
			}

			$data = get_option( $this->optionName, array() );

			if( !array_key_exists( $editingGroup, $data ) ){
				return false;
			}

			$return = false;

			switch( $field )
			{

				case 'group-title':
					
					$return = $data[$editingGroup]['title'];
					break;

				case 'group-users':

					$return = $data[$editingGroup]['users'];
					break;
				
				default:
					
					$return = false;
					break;

			}

			return $return;

		}/* processEditData() */


		private function getUsersOfRole( $role = 'subscriber' )
		{

			if( !$role ){
				return new WP_Error( '1', 'getUsersOfRole() requires a $role argument' );
			}

			$args = array(
				'role' => $role
			);

			$wp_user_search = new WP_User_Query( $args );

			$users = $wp_user_search->get_results();

			return $users;

		}/* getUsersOfRole() */


		/**
		 * Handle a new group submission
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function studiorum_user_groups_add_new_group_submitted__processNewGroupSubmission()
		{

			// Grab the nonce and verify it
			$nonce = ( isset( $_REQUEST['_wpnonce_add-user-group'] ) ) ? sanitize_text_field( $_REQUEST['_wpnonce_add-user-group'] ) : false;

			if( !$nonce ){
				$nonce = ( isset( $_REQUEST['nonce'] ) ) ? sanitize_text_field( $_REQUEST['nonce'] ) : false;
			}

			if( !wp_verify_nonce( $nonce, 'add-user-group' ) ){
				return false;
			}

			// grab the existing groups
			$existingData = get_option( $this->optionName, array() );

			$newDataToAdd = $this->validateFormData( 'new' );

			// Let's go ahead and add the group
			$this->addNewGroup( $newDataToAdd, $existingData );

		}/* studiorum_user_groups_add_new_group_submitted__processNewGroupSubmission() */


		/**
		 * Helper method to validate submitted data for new and editing submissions
		 *
		 * @since 0.1
		 *
		 * @param string $param description
		 * @return string|int returnDescription
		 */

		private function validateFormData( $action = 'new' )
		{

			// Grab and sanitize the title. We'll also create a slug which will be the key
			$title = isset( $_REQUEST['group-title'] ) ? sanitize_text_field( $_REQUEST['group-title'] ) : false;

			if( !$title ){
				return new WP_Error( '1', __( 'Please provide a title', 'studiorum-user-groups' ) );
			}

			// Make the slug, use 'save' as the context so additional entities are converted to hyphens or stripped 
			$slug = sanitize_title_with_dashes( $title, null, 'save' );

			// grab the existing groups
			$existingData = get_option( $this->optionName, array() );

			// If the slug already exists, bail
			if( $existingData && is_array( $existingData ) && !empty( $existingData ) )
			{

				if( $action == 'new' && array_key_exists( $slug, $existingData ) ){
					return new WP_Error( '1', __( 'A group with this name already exists', 'studiorum-user-groups' ) );
				}

			}

			// Grab and sanitize the users
			$usersToAddToGroup = isset( $_REQUEST['group-users'] ) ? sanitize_text_field( $_REQUEST['group-users'] ) : false;

			// Bail if there are no users
			if( !$usersToAddToGroup ){
				return new WP_Error( '1', __( 'Please select users to add to the group', 'studiorum-user-groups' ) );
			}

			// $usersToAddToGroup will be a comma-separated string of User IDs, let's convert that into an array
			$usersToAddToGroup = explode( ',', $usersToAddToGroup );

			$newDataToAdd = array(
				'title' => $title,
				'slug' => $slug,
				'users' => $usersToAddToGroup
			);

			$newDataToAdd = apply_filters( 'studiorum_user_groups_add_new_group_new_data', $newDataToAdd );

			return $newDataToAdd;

		}/* validateFormData() */


		/**
		 * Declare we are editing an existing group
		 *
		 * @since 0.1
		 *
		 * @param string $param description
		 * @return string|int returnDescription
		 */

		public function studiorum_user_groups_edit_group_submitted__editExistingGroup()
		{

			$this->editingGroup = true;

		}/* studiorum_user_groups_edit_group_submitted__editExistingGroup() */


		/**
		 * Process editing a group
		 *
		 * @since 0.1
		 *
		 * @param string $param description
		 * @return string|int returnDescription
		 */

		public function studiorum_user_groups_edited_group_submitted__processEditGroup()
		{

			// grab the existing groups
			$existingData = get_option( $this->optionName, array() );

			$newDataToAdd = $this->validateFormData( 'edit' );

			// Let's go ahead and add the group
			$this->addNewGroup( $newDataToAdd, $existingData );

		}/* studiorum_user_groups_edited_group_submitted__processEditGroup() */


		/**
		 * Add a new group to the groups option. Also runs for edit as it simply overwrites the array key for that entry.
		 *
		 * @since 0.1
		 *
		 * @param array $newDataToAdd The new data which we wish to add
		 * @param array $existingData The data already in the database
		 * @return array $newData The full data set
		 */

		public function addNewGroup( $newDataToAdd = false, $existingData = false, $refresh = true )
		{

			// Well, we need some data to add
			if( !$newDataToAdd ){
				return false;
			}

			// If we aren't passed the original data, grab it
			if( !$existingData ){
				$existingData = get_option( $this->optionName );
			}

			do_action( 'studiorum_user_groups_before_add_new_group', $newDataToAdd );

			$this->newGroupDataSanitized = $newDataToAdd;
			file_put_contents( WP_CONTENT_DIR . '/debug.log', "\n" . '$this->newGroupDataSanitized: ' . print_r( $this->newGroupDataSanitized, true ), FILE_APPEND  );
			$existingData[$newDataToAdd['slug']] = array( 'ID' => $newDataToAdd['slug'], 'title' => $newDataToAdd['title'], 'users' => $newDataToAdd['users'] );

			$return = update_option( $this->optionName, $existingData );

			do_action( 'studiorum_user_groups_after_add_new_group', $newDataToAdd, $return );

			if( !$refresh ){
				return $return;
			}

		}/* addNewGroup() */


		/**
		 * Helper method to get existing data
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return array A set of existing data
		 */

		public function getExistingData()
		{

			$existingData = get_option( $this->optionName, array() );

			return $existingData;

		}/* getExistingData() */


		/**
		 * Helper method to set the option. Overwrites everything in the option with what is passed.
		 *
		 * @since 0.1
		 *
		 * @param string $param description
		 * @return string|int returnDescription
		 */

		public function setData( $data )
		{

			update_option( $this->optionName, $data );

		}/* setData() */


		/**
		 * Adds the ability for a *group* of users to view the private posts submitted by a student
		 *
		 * @since 0.1
		 *
		 * @param (array) $specificUsersAbleToSeeThisPost - User IDs who can see the private post
		 * @return (array) $specificUsersAbleToSeeThisPost - User IDs who can see the private post
		 */

		public function studiorum_lectio_specific_users_who_can_see_private_submissions__addUsersGroups( $specificUsersAbleToSeeThisPost, $currentUserID, $post )
		{

			// Should a user's group of users all be able to see the private submissions from each other? On by default
			$groupSeesEachOthersSubmissionsFromOption = get_studiorum_option( 'user_group_options', 'studiorum_user_groups_groups_see_each_others_submissions', 'true' );
			$groupSeesEachOthersSubmissions = apply_filters( 'studiorum_user_groups_groups_see_each_others_submissions', $groupSeesEachOthersSubmissionsFromOption, $specificUsersAbleToSeeThisPost, $currentUserID, $post );
			
			// If we're not doing this, just bail
			if( !$groupSeesEachOthersSubmissions || $groupSeesEachOthersSubmissions == 'false' ){
				return $specificUsersAbleToSeeThisPost;
			}

			// Fetch this user's groups
			$groups = Studiorum_User_Groups_Utils::getUsersGroups();
			
			// If we have some groups, then we have an array of arrays
			if( !$groups || !is_array( $groups ) || empty( $groups ) ){
				return $specificUsersAbleToSeeThisPost;
			}

			// Get the post's author ID, so we can look for users in that author's groups
			$authorID = $post->post_author;

			foreach( $groups as $slug => $groupDetails )
			{
				
				$thisGroupsUsers = ( isset( $groupDetails['users'] ) ) ? $groupDetails['users'] : false;

				if( !$thisGroupsUsers || !is_array( $thisGroupsUsers ) || empty( $thisGroupsUsers ) ){
					continue;
				}

				// Check if the author of the post is in the current user's groups
				if( !in_array( $authorID, array_values( $thisGroupsUsers ) ) ){
					continue;
				}

				// OK, so we now have this group's users as an array, add to $specificUsersAbleToSeeThisPost if not already there
				foreach( $thisGroupsUsers as $key => $userID )
				{

					if( !in_array( $userID, $specificUsersAbleToSeeThisPost ) ){
						$specificUsersAbleToSeeThisPost[] = $userID;
					}

				}

			}

			return $specificUsersAbleToSeeThisPost;

		}/* studiorum_lectio_specific_users_who_can_see_private_submissions__addUsersGroups() */


		/**
		 * Handle bulk delete
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return null
		 */

		public function studiorum_user_groups_bulk_delete__handleBulkDelete()
		{

			// Grab an array of groups that we are to bulk delete - already urldecode'd
			$groups = ( isset( $_REQUEST['group'] ) && is_array( $_REQUEST['group'] ) ) ? $_REQUEST['group'] : false;

			if( !$groups ){
				return;
			}

			// Sanitization of these groups
			$sanitizedGroups = array();

			foreach( $groups as $key => $groupTitle ){
				$sanitizedGroups[] = sanitize_text_field( $groupTitle );
			}

			// Now we have an array of sanitized keys which we need to delete
			foreach( $sanitizedGroups as $key => $title ){
				User_Groups_List_Table::deleteGroup( $title );
			}

		}/* studiorum_user_groups_bulk_delete__handleBulkDelete() */


		/**
		 * Register ourself as a studiorum addon, so it's available in the main studiorum page
		 *
		 * @since 0.1
		 *
		 * @param array $modules Currently registered modules
		 * @return array $modules modified list of modules
		 */

		public function studiorum_modules__registerAsModule( $modules )
		{

			if( !$modules || !is_array( $modules ) ){
				$modules = array();
			}

			$modules['studiorum-user-groups'] = array(
				'id' 				=> 'user_groups',
				'plugin_slug'		=> 'studiorum-user-groups',
				'title' 			=> __( 'User Groups', 'studiorum' ),
				'icon' 				=> 'groups', // dashicons-#
				'excerpt' 			=> __( 'Group your users into specific sets and then allow those groups to work together.', 'studiorum' ),
				'image' 			=> 'http://dummyimage.com/310/162',
				'link' 				=> 'http://code.ubc.ca/studiorum/user-groups',
				'content' 			=> __( '<p>Group your users into specific sets - users can be in more than one group or no group. With the User Groups Automatic module you are able to create random groups at the click of a button.</p>', 'studiorum' ),
				'content_sidebar' 	=> 'http://dummyimage.com/300x150',
				'date'				=> '2014-06-01'
			);

			return $modules;

		}/* studiorum_modules__registerAsModule() */


		/**
		 * Update the message shown to authors of submissions to also add that users in their group
		 * can see the notice 
		 *
		 * @author Richard Tape <@richardtape>
		 * @since 1.0
		 * @param string $message - the message shown to authors on the front-end
		 * @return string $message - updated message with group
		 */
		
		public function studiorum_lectio_author_note_above_submission__addGroupToNotice( $message )
		{

			return $message . __( ' Additionally, anyone in the same user group as you can see and comment on this submission.', 'studiorum-user-groups' );

		}/* studiorum_lectio_author_note_above_submission__addGroupToNotice() */
		

		/**
		 * 	AJAX handler for the add groups
		 *
		 * @author Richard Tape <@richardtape>
		 * @since 1.0
		 * @param null
		 * @return null
		 */
		
		public function wp_ajax_studiorum_user_groups_add_group()
		{

			// Nonce check
			if( !wp_verify_nonce( $_REQUEST['nonce'], 'add-user-group' ) )
			{

				$result = array( 'type' => 'failure', 'reason' => 'nonce' );
				$result = json_encode( $result );
				echo $result;
				die();

			}

			// OK nonce check passed
			$result = array( 'type' => 'success' );

			// Depending on what the form action is, we add or edit
			
			$formAction = sanitize_text_field( $_REQUEST['formAction'] );

			switch( $formAction )
			{

				case 'edited-user-group':
					
					do_action( 'studiorum_user_groups_edited_group_submitted' );
					break;
				
				case 'add-user-group':
				default:
					
					// We have a function hooked into here to sanitize and then add if necessary
					do_action( 'studiorum_user_groups_add_new_group_submitted' );

					break;

			}

			// At this point $this->newGroupDataSanitized should be set
			// we want t oconvert the user IDs into the nicenames
			$userData = $this->getUserDataForJSFromUserIDs( $this->newGroupDataSanitized['users'] );

			$result['groupTitle'] = $this->newGroupDataSanitized['title'];
			$result['groupSlug'] = $this->newGroupDataSanitized['slug'];

			$resultUsers = array();

			foreach( $userData as $key => $userData ){
				$resultUsers[] = $userData['login'];
			}

			$result['groupUsers'] = implode( ', ', $resultUsers );

			if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' )
			{

				$result = json_encode($result);
				echo $result;

			}
			else
			{
				header( "Location: " . $_SERVER["HTTP_REFERER"] );
			}

			die();

		}/* wp_ajax_studiorum_user_groups_add_group() */


	}/* class Studiorum_User_Groups() */

	// Instantiate ourselves
	global $Studiorum_User_Groups;
	$Studiorum_User_Groups = new Studiorum_User_Groups();
