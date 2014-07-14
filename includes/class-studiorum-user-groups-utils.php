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

			return $existingData;

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