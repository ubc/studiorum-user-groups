<?php

	if( !defined( 'ABSPATH' ) ){
		die( '-1' );
	}

	class User_Groups_List_Table extends WP_List_Table
	{

		/** ************************************************************************
		 * Normally we would be querying data from a database and manipulating that
		 * for use in your list table. For this example, we're going to simplify it
		 * slightly and create a pre-built array. Think of this as the data that might
		 * be returned by $wpdb->query().
		 * 
		 * @var array 
		 **************************************************************************/

		var $userGroupData = array();

		/** ************************************************************************
		 * REQUIRED. Set up a constructor that references the parent constructor. We 
		 * use the parent reference to set some default configs.
		 ***************************************************************************/
		function __construct()
		{

			global $status, $page;


					
			//Set parent defaults
			parent::__construct( array(
				'singular'  => 'group',     //singular name of the listed records
				'plural'    => 'groups',    //plural name of the listed records
				'ajax'      => true        //does this table support ajax?
			) );
			
		}


		/** ************************************************************************
		 * Recommended. This method is called when the parent class can't find a method
		 * specifically build for a given column. Generally, it's recommended to include
		 * one method for each column you want to render, keeping your package class
		 * neat and organized. For example, if the class needs to process a column
		 * named 'title', it would first see if a method named $this->column_title() 
		 * exists - if it does, that method will be used. If it doesn't, this one will
		 * be used. Generally, you should try to use custom column methods as much as 
		 * possible. 
		 * 
		 * Since we have defined a column_title() method later on, this method doesn't
		 * need to concern itself with any column with a name of 'title'. Instead, it
		 * needs to handle everything else.
		 * 
		 * For more detailed insight into how columns are handled, take a look at 
		 * WP_List_Table::single_row_columns()
		 * 
		 * @param array $item A singular item (one full row's worth of data)
		 * @param array $column_name The name/slug of the column to be processed
		 * @return string Text or HTML to be placed inside the column <td>
		 **************************************************************************/
		function column_default($item, $column_name){
			switch($column_name){
				case 'users':
					return $item[$column_name];
				default:
					return print_r($item,true); //Show the whole array for troubleshooting purposes
			}
		}


		/** ************************************************************************
		 * Recommended. This is a custom column method and is responsible for what
		 * is rendered in any column with a name/slug of 'title'. Every time the class
		 * needs to render a column, it first looks for a method named 
		 * column_{$column_title} - if it exists, that method is run. If it doesn't
		 * exist, column_default() is called instead.
		 * 
		 * This example also illustrates how to implement rollover actions. Actions
		 * should be an associative array formatted as 'slug'=>'link html' - and you
		 * will need to generate the URLs yourself. You could even ensure the links
		 * 
		 * 
		 * @see WP_List_Table::::single_row_columns()
		 * @param array $item A singular item (one full row's worth of data)
		 * @return string Text to be placed inside the column <td> (movie title only)
		 **************************************************************************/
		function column_title($item){
			
			$editNonce = wp_create_nonce( 'edit-user-group-nonce' );
			$deleteNonce = wp_create_nonce( 'delete-user-group-nonce' );

			//Build row actions
			$actions = array(
				'edit'      => sprintf( '<a href="?page=%s&action=%s&user-group=%s&edit-nonce=%s">Edit</a>',$_REQUEST['page'],'edit-user-group',$item['ID'], $editNonce ),
				'delete'    => sprintf( '<a href="?page=%s&action=%s&user-group=%s&delete-nonce=%s">Delete</a>',$_REQUEST['page'],'delete-user-group',$item['ID'], $deleteNonce ),
			);
			
			//Return the title contents
			return sprintf('%1$s %2$s',
				/*$1%s*/ $item['title'],
				/*$2%s*/ $this->row_actions($actions)
			);
		}

		function column_users( $item ){

			// This is what we're going to return
			$users = array();

			$thisGroupsUsers = ( isset( $item['users'] ) ) ? $item['users'] : array();

			if( empty( $thisGroupsUsers ) ){
				return __( 'No users', 'studiorum-user-groups' );
			}

			foreach( $thisGroupsUsers as $key => $userID )
			{

				$userObject = get_user_by( 'id', $userID );

				$name = ( isset( $userObject->user_nicename ) && !empty( $userObject->user_nicename ) ) ? $userObject->user_nicename : $userObject->user_login;



				$users[] = $name;

			}

			return implode( ', ', $users );

		}


		/** ************************************************************************
		 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
		 * is given special treatment when columns are processed. It ALWAYS needs to
		 * have it's own method.
		 * 
		 * @see WP_List_Table::::single_row_columns()
		 * @param array $item A singular item (one full row's worth of data)
		 * @return string Text to be placed inside the column <td> (movie title only)
		 **************************************************************************/
		function column_cb($item){
			return sprintf(
				'<input type="checkbox" name="%1$s[]" value="%2$s" />',
				/*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
				/*$2%s*/ $item['ID']                //The value of the checkbox should be the record's id
			);
		}


		/** ************************************************************************
		 * REQUIRED! This method dictates the table's columns and titles. This should
		 * return an array where the key is the column slug (and class) and the value 
		 * is the column's title text. If you need a checkbox for bulk actions, refer
		 * to the $columns array below.
		 * 
		 * The 'cb' column is treated differently than the rest. If including a checkbox
		 * column in your table you must create a column_cb() method. If you don't need
		 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
		 * 
		 * @see WP_List_Table::::single_row_columns()
		 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
		 **************************************************************************/
		function get_columns(){
			$columns = array(
				'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
				'title'     => 'Title',
				'users'    => 'Users'
			);
			return $columns;
		}


		/** ************************************************************************
		 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
		 * you will need to register it here. This should return an array where the 
		 * key is the column that needs to be sortable, and the value is db column to 
		 * sort by. Often, the key and value will be the same, but this is not always
		 * the case (as the value is a column name from the database, not the list table).
		 * 
		 * This method merely defines which columns should be sortable and makes them
		 * clickable - it does not handle the actual sorting. You still need to detect
		 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
		 * your data accordingly (usually by modifying your query).
		 * 
		 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
		 **************************************************************************/
		function get_sortable_columns() {
			$sortable_columns = array(
				'title'     => array('title',false),     //true means it's already sorted
				'users'    => array('users',false)
			);
			return $sortable_columns;
		}


		/** ************************************************************************
		 * Optional. If you need to include bulk actions in your list table, this is
		 * the place to define them. Bulk actions are an associative array in the format
		 * 'slug'=>'Visible Title'
		 * 
		 * If this method returns an empty value, no bulk action will be rendered. If
		 * you specify any bulk actions, the bulk actions box will be rendered with
		 * the table automatically on display().
		 * 
		 * Also note that list tables are not automatically wrapped in <form> elements,
		 * so you will need to create those manually in order for bulk actions to function.
		 * 
		 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
		 **************************************************************************/
		function get_bulk_actions() {
			$actions = array(
				'delete'    => 'Delete'
			);
			return $actions;
		}


		/** ************************************************************************
		 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
		 * For this example package, we will handle it in the class to keep things
		 * clean and organized.
		 * 
		 * @see $this->prepare_items()
		 **************************************************************************/
		function process_bulk_action() {
			
			$action = $this->current_action();

			switch( $action )
			{

				case 'delete-user-group':
					$this->processDeleteGroup();
					break;

				case 'edit-user-group':
					$this->processEditGroup();
					break;
				
				default:
					# code...
					break;

			}
			
		}

		function processEditGroup()
		{

			$nonce = ( isset( $_REQUEST['edit-nonce'] ) ) ? $_REQUEST['edit-nonce'] : '';

			if( !wp_verify_nonce( $nonce, 'edit-user-group-nonce' ) ){
				wp_die( '1', __( 'Nonce failure', 'studiorum-user-groups' ) );
			}

			if( !isset( $_REQUEST['user-group'] ) ){
				wp_die( '2', __( 'Must provide User Group to Edit', 'studiorum-user-groups' ) );
			}

			$groupIDToEdit = $_REQUEST['user-group'];

			// OK, we have a group to delete and we have passed the nonce check
			do_action( 'studiorum_user_groups_edit_group_submitted', $groupIDToEdit );

		}/* processEditGroup() */



		function processDeleteGroup()
		{

			$nonce = ( isset( $_REQUEST['delete-nonce'] ) ) ? $_REQUEST['delete-nonce'] : '';

			if( !wp_verify_nonce( $nonce, 'delete-user-group-nonce' ) ){
				wp_die( '1', __( 'Nonce failure', 'studiorum-user-groups' ) );
			}

			if( !isset( $_REQUEST['user-group'] ) ){
				wp_die( '2', __( 'Must provide User Group to Edit', 'studiorum-user-groups' ) );
			}

			$groupIDToDelete = $_REQUEST['user-group'];

			// OK, we have a group to delete and we have passed the nonce check
			$deleted = $this->deleteGroup( $groupIDToDelete );

			// if( $deleted ){
			// 	wp_redirect( admin_url( 'users.php?page=studiorum-user-groups&action=deleted' ) );
			// 	exit;
			// }

		}/* processDeleteGroup() */


		/**
		 * Delete a group
		 *
		 * @since 0.1
		 *
		 * @param int $groupID The ID to edit
		 * @return bool
		 */

		public static function deleteGroup( $groupID = false )
		{

			if( !$groupID ){
				return false;
			}

			// Fetch all the groups
			$groups = get_option( Studiorum_User_Groups_Utils::$optionName );

			if( isset( $groups[ $groupID ] ) ){

				unset( $groups[$groupID] );

				update_option( Studiorum_User_Groups_Utils::$optionName, $groups );

				return true;

			}

			return false;

		}/* deleteGroup() */



		/** ************************************************************************
		 * REQUIRED! This is where you prepare your data for display. This method will
		 * usually be used to query the database, sort and filter the data, and generally
		 * get it ready to be displayed. At a minimum, we should set $this->items and
		 * $this->set_pagination_args(), although the following properties and methods
		 * are frequently interacted with here...
		 * 
		 * @global WPDB $wpdb
		 * @uses $this->_column_headers
		 * @uses $this->items
		 * @uses $this->get_columns()
		 * @uses $this->get_sortable_columns()
		 * @uses $this->get_pagenum()
		 * @uses $this->set_pagination_args()
		 **************************************************************************/
		function prepare_items() {
			global $wpdb; //This is used only if making any database queries

			/**
			 * First, lets decide how many records per page to show
			 */
			$per_page = 5;

			// update_option(
			// 	$this->optionName,
			// 	array(
			// 		1 => array(
			// 			'ID'        => 1,
			// 			'title'     => 'Group A',
			// 			'users'  	=> array( '1', '12', '13' )
			// 		),
			// 		2 => array(
			// 			'ID'        => 2,
			// 			'title'     => 'Group B',
			// 			'users'  	=> array( '1', '15', '16' )
			// 		),
			// 		3 => array(
			// 			'ID'        => 3,
			// 			'title'     => 'Group C',
			// 			'users'  	=> array( '1', '12', '18', '19' )
			// 		),
			// 		4 => array(
			// 			'ID'        => 4,
			// 			'title'     => 'Group D',
			// 			'users'  	=> array( '17' )
			// 		)
			// 	)
			// );
			
			
			/**
			 * REQUIRED. Now we need to define our column headers. This includes a complete
			 * array of columns to be displayed (slugs & titles), a list of columns
			 * to keep hidden, and a list of columns that are sortable. Each of these
			 * can be defined in another method (as we've done here) before being
			 * used to build the value for our _column_headers property.
			 */
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
			
			
			/**
			 * REQUIRED. Finally, we build an array to be used by the class for column 
			 * headers. The $this->_column_headers property takes an array which contains
			 * 3 other arrays. One for all columns, one for hidden columns, and one
			 * for sortable columns.
			 */
			$this->_column_headers = array($columns, $hidden, $sortable);
			
			
			/**
			 * Optional. You can handle your bulk actions however you see fit. In this
			 * case, we'll handle them within our package just to keep things clean.
			 */
			$this->process_bulk_action();
			
			
			/**
			 * Instead of querying a database, we're going to fetch the example data
			 * property we created for use in this plugin. This makes this example 
			 * package slightly different than one you might build on your own. In 
			 * this example, we'll be using array manipulation to sort and paginate 
			 * our data. In a real-world implementation, you will probably want to 
			 * use sort and pagination data to build a custom query instead, as you'll
			 * be able to use your precisely-queried data immediately.
			 */

			$data = get_option( Studiorum_User_Groups_Utils::$optionName, array() );
					
			
			/**
			 * This checks for sorting input and sorts the data in our array accordingly.
			 * 
			 * In a real-world situation involving a database, you would probably want 
			 * to handle sorting by passing the 'orderby' and 'order' values directly 
			 * to a custom query. The returned data will be pre-sorted, and this array
			 * sorting technique would be unnecessary.
			 */
			function usort_reorder($a,$b){
				$orderby = (!empty($_REQUEST['orderby'])) ? $_REQUEST['orderby'] : 'title'; //If no sort, default to title
				$order = (!empty($_REQUEST['order'])) ? $_REQUEST['order'] : 'asc'; //If no order, default to asc
				$result = strcmp($a[$orderby], $b[$orderby]); //Determine sort order
				return ($order==='asc') ? $result : -$result; //Send final sort direction to usort
			}
			usort($data, 'usort_reorder');
			
					
			/**
			 * REQUIRED for pagination. Let's figure out what page the user is currently 
			 * looking at. We'll need this later, so you should always include it in 
			 * your own package classes.
			 */
			$current_page = $this->get_pagenum();
			
			/**
			 * REQUIRED for pagination. Let's check how many items are in our data array. 
			 * In real-world use, this would be the total number of items in your database, 
			 * without filtering. We'll need this later, so you should always include it 
			 * in your own package classes.
			 */
			$total_items = count($data);
			
			
			/**
			 * The WP_List_Table class does not handle pagination for us, so we need
			 * to ensure that the data is trimmed to only the current page. We can use
			 * array_slice() to 
			 */
			$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
			
			
			
			/**
			 * REQUIRED. Now we can add our *sorted* data to the items property, where 
			 * it can be used by the rest of the class.
			 */
			$this->items = $data;
			
			
			/**
			 * REQUIRED. We also have to register our pagination options & calculations.
			 */
			$this->set_pagination_args( array(
				'total_items' => $total_items,                  //WE have to calculate the total number of items
				'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
				'total_pages' => ceil($total_items/$per_page)   //WE have to calculate the total number of pages
			) );
		}

	}/* class User_Groups_List_Table */


?>