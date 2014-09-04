<?php 

	/**
	 * User Group Utility functions
	 *
	 * @package     Studiorum User Groups
	 * @subpackage  Studiorum/USer Groups/Utils
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       0.1.0
	 */

	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ){
		exit;
	}

	class Studiorum_User_Groups_Utils
	{

		// The option name stored in the db
		static $optionName = 'studiorum_user_groups';

		// Cached options
		static $currentGroups = false;


		/**
		 * Get the groups for the specified user (defaults to the current user)
		 * This should now allow a user to be in more than one group at a time
		 *
		 * @since 0.1
		 *
		 * @param int $userID - the user ID of the groups to fetch
		 * @return array - an associative array of the groups this user is in containing the user IDs for each group
		 */

		public static function getUsersGroups( $userID = false )
		{

			if( !$userID ){
				$userID = get_current_user_id();
			}

			// Fetch all the existing data
			$existingData = static::fetchCurrentGroups();

			// Bail if we don't have any
			if( !$existingData || !is_array( $existingData ) || empty( $existingData ) ){
				return false;
			}

			// $existingData is now an array of arrays, each sub-array as han ID, a title and an array of 'users'
			// We need to look in each sub-array's users to see if the passed $userID is in
			// Holds the main keys that this user is in
			$userIsInKeys = array();

			foreach( $existingData as $key => $userGroupData )
			{
				
				$thisGroupsUsers = ( isset( $userGroupData['users'] ) ) ? $userGroupData['users'] : false;

				// Empty? Skip
				if( $thisGroupsUsers === false || !is_array( $thisGroupsUsers ) || empty( $thisGroupsUsers ) ){
					continue;
				}

				// Passed user ID not in the list of users for this group? Skip
				if( !in_array( $userID, array_values( $thisGroupsUsers ) ) ){
					continue;
				}

				if( !in_array( $key, array_values( $userIsInKeys ) ) ){
					$userIsInKeys[] = $key;
				}

			}

			// Now, $userIsInKeys should be an array of keys which match the $existingData. We just want the data
			// associated with those keys
			if( empty( $userIsInKeys ) ){
				return false;
			}

			$thisUsersGroupsData = array();

			foreach( $userIsInKeys as $count => $key ){
				$thisUsersGroupsData[$key] = $existingData[$key];
			}

			return $thisUsersGroupsData;

		}/* getUsersGroups() */


		/**
		 * Fetch the currently saved groups
		 *
		 * @since 0.1
		 *
		 * @param null
		 * @return array The currently saved groups
		 */
		public static function fetchCurrentGroups()
		{

			if( static::$currentGroups && is_array( static::$currentGroups ) && !empty( static::$currentGroups ) ){
				return static::$currentGroups;
			}

			$existingData = get_option( static::$optionName );

			static::$currentGroups = $existingData;

			return $existingData;

		}/* fetchCurrentGroups() */

	}/* Studiorum_User_Groups_Utils() */