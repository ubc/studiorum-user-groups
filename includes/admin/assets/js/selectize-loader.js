jQuery( document ).ready( function( $ ){

	var userData = sugData.userData;
	var editingUsers = false;

	if( sugData.editingUsers ){
		editingUsers = sugData.editingUsers;
	}

	var usersGroupsField = $( '#group-users' );

	var userGroupsSelectize = usersGroupsField.selectize({

		maxItems: null,
		valueField: 'userID',
		labelField: 'nice_name',
		searchField: ['nice_name', 'email', 'userID'],
		options: userData,

		render: {
			item: function(item, escape) {
				return '<div>' +
					//(item.nice_name ? '<span class="name">' + escape(item.nice_name) + '</span>' : '') +
					(item.email ? '<span class="email">' + escape(item.email) + '</span>' : '') +
				'</div>';
			},
			option: function(item, escape) {
				var label = item.nice_name || item.email;
				var caption = item.nice_name ? item.email : null;
				var userID = item.userID ? item.userID : null;
				return '<div class="selectize-row-result">' +
					'<span class="label">' + escape(label) + ' <span class="userID">(User ID: ' + escape(userID) + ')</span></span>' +
					(caption ? '<span class="caption">' + escape(caption) + '</span>' : '') +
				'</div>';
			}
		},

	});

	// if( editingUsers ){

	// 	var selectizeElement = userGroupsSelectize[0].selectize;

	// 	selectizeElement.setValue( 'Test' );
	// }


} );