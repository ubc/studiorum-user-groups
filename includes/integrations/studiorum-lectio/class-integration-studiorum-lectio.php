<?php 

	/**
	 * User Group Integrations with Studiorum Lectio
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

	class Studiorum_User_Groups_Integrations_Studiorum_Lectio
	{

			/**
			 * Actions and Filters
			 *
			 * @since 0.1
			 *
			 * @param null
			 * @return null
			 */

			public function __construct()
			{

				// Add the submissions visible to this student to the dashboard widget for discussions
				add_filter( 'studiorum_lectio_viewable_submissions', array( $this, 'studiorum_lectio_viewable_submissions__addStudentGroupSubmissions' ), 10, 2 );

			}/* __construct() */


			/**
			 * Add to the Studiorum Lectio dashboard widget to make the assigment submissions that are from students in the same group
			 * as this user visible. Must come out as
			 *
			 * [array_key i.e. current_user_submissions] => array(
			 *		'title' => 'Title is required key',
			 * 		'data' => array( 22 => array( 0 => 123, 1 => 456 ) ) // An array where the keys are TaxIDs and the values are an array of post IDs
			 * )
			 *
			 * @since 0.1
			 *
			 * @param array $allViewableSubmissions Currently viewable submissions
			 * @param int $userID The userID
			 * @return array $allViewableSubmissions Modified submissions with group's submissions added if appropriate
			 */

			public function studiorum_lectio_viewable_submissions__addStudentGroupSubmissions( $allViewableSubmissions, $userID )
			{

				// Fetch groups for this user
				$thisUsersGroups = Studiorum_User_Groups_Utils::getUsersGroups( $userID );

				// No groups? Lets just return what we already ad
				if( !$thisUsersGroups || !is_array( $thisUsersGroups ) || empty( $thisUsersGroups ) ){
					return $allViewableSubmissions;
				}

				// OK, we have groups. Lets get the submissions for each of the users in these groups
				$groupsSubmissions = array(
					'group_submissions' => array(
						'title' => 'Group Submissions',
						'data' => array()
					)
				);

				foreach( $thisUsersGroups as $key => $userGroupData )
				{
					
					$thisGroupsUsers = ( isset( $userGroupData['users'] ) ) ? $userGroupData['users'] : false;

					// Empty? Skip
					if( $thisGroupsUsers === false || !is_array( $thisGroupsUsers ) || empty( $thisGroupsUsers ) ){
						continue;
					}

					foreach( $thisGroupsUsers as $iKey => $groupUserID )
					{ 

						// Fetch each submission for users in these groups
						$thisUsersSubmissions = Studiorum_Lectio_Utils::fetchUsersSubmissions( $groupUserID );

						if( !$thisUsersSubmissions || !is_array( $thisUsersSubmissions ) || empty( $thisUsersSubmissions ) ){
							continue;
						}

						// Only add if this is not the current user as we already have those
						if( $userID != $groupUserID ){
							$groupsSubmissions['group_submissions']['data'][$groupUserID] = $thisUsersSubmissions[$groupUserID];
						}

					}

				}

				$merged = array_merge( $allViewableSubmissions, $groupsSubmissions );

				// echo '<pre>' . print_r( $groupsSubmissions, true ) . '</pre>';
				// echo '<pre>' . print_r( $allViewableSubmissions, true ) . '</pre>';
				// echo '<pre>' . print_r( $merged, true ) . '</pre>';

				return $merged;

			}/* studiorum_lectio_viewable_submissions__addStudentGroupSubmissions() */

	}/* class Studiorum_User_Groups_Integrations_Studiorum_Lectio */

	$Studiorum_User_Groups_Integrations_Studiorum_Lectio = new Studiorum_User_Groups_Integrations_Studiorum_Lectio();