jQuery( document ).ready( function( $ ){

	$( '#add-user-group' ).on( 'submit', function(e){ submit__processAddUserGroup(e); } );

	function submit__processAddUserGroup( event ){

		// We don't want a normal submission
		event.preventDefault();

		// Grab the nonce
		var addNonce 		= $( '#_wpnonce_add-user-group' ).val();

		// What has been submitted, we sanitize this in PHP
		var groupTitle 		= $( '#group-title' ).val();
		var groupUserIDs 	= $( '#group-users' ).val();

		var formAction  	= $( '#add-user-group input[name="action"]' ).val();

		// The table where we're inputting data
		var insertInto 		= $( '#the-list' );

		// Determine if there is currently no data in the table
		var tableIsEmpty 	= $( '#the-list tr:first-of-type' ).hasClass( 'no-items' );

		// When adding the currently existing first row is always a class of 'alternate'
		// so if we're currently empty, we add that class, otherwise we don't
		var trClass 		= ( tableIsEmpty ) ? 'alternate' : '';

		$.ajax( {

			type: 'post',
			dataType: 'json',
			url: ajaxurl,

			data: {
				'action': 'studiorum_user_groups_add_group',
				'nonce': addNonce,
				'group-title': groupTitle,
				'group-users': groupUserIDs,
				'tableIsEmpty': tableIsEmpty,
				'formAction': formAction
			},

			success: function( response ){

				if( response.type == 'success' ){

					var resultGroupTitle = response.groupTitle
					var resultGroupSlug = response.groupSlug
					var resultGroupUsers = response.groupUsers

					var trMarkup = '<tr><th scope="row" class="check-column"><input name="group[]" value="' + resultGroupSlug + '" type="checkbox"></th><td class="title column-title">' + resultGroupTitle + '<div class="row-actions">Please refresh to edit or delete</div<</td><td class="users column-users">' + resultGroupUsers + '</td></tr>';

					// If we're editing, we remove the original
					if( formAction == 'edited-user-group' ){

						// Find the 'achor' with a data attribute of data-edit == resultGroupSlug
						var anchorEditLink = $( '.row-actions .edit' ).find( 'a[data-edit="' + resultGroupSlug + '"]' );
						var trToRemove = anchorEditLink.parents( 'tr' );
						trToRemove.slideUp( 100 );

					}

					// OK, add it to the table
					insertInto.prepend( trMarkup );

					$( '#group-title' ).val('');
					var groupSelectizedObj = userGroupsSelectize[0].selectize
					groupSelectizedObj.clear();

				}else{
					console.log( 'failure' );
					console.log( response );
				}

			}

		} );

	}/* submit__processAddUserGroup() */


} );