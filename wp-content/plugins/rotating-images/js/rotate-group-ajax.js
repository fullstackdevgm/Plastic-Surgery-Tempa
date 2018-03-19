jQuery(document).on( 'click', '.edit_rotate_group_settings', function(ev) {
	ev.preventDefault();
	var it_group_id = jQuery(this).attr("id").match(/[\d]+$/);
	//alert(it_group_id);
	jQuery.ajax({
		url: it_rotate_ajax_location,
		type: 'POST',
		dataType: 'json',
		data: { 
		encoded_data : it_group_id,
		action: 'it_edit_rotate_group'
		},
		success: function( data ) {
			console.log(data);
			jQuery('.add-new-h2').text('Edit Group Settings');
			jQuery('#add_new_rotating_images_group').slideDown();
			jQuery('#name').val(data.name);
			jQuery('#width').val(data.width);
			jQuery('#height').val(data.height);
			if ( '1' == data.fade ) { 
				jQuery('.enable_fade').attr('checked', true);
			} else { 
				jQuery('.enable_fade').attr('checked', false);
			}

			if ( '1' == data.responsive_op ) { 
				jQuery('.enable_responsive').attr('checked', true);
			} else { 
				jQuery('.enable_responsive').attr('checked', false);
			}

			if ( '1' == data.enable_overlay ) { 
				jQuery('.enable_overlay').attr('checked', true);
			} else { 
				jQuery('.enable_overlay').attr('checked', false);
			}

			jQuery('#add_group').val('Save Settings')
			jQuery('#add_group').attr("name", "edit_group");
			jQuery('#edit_group_id').val(data.id);
		}
	});
});