jQuery(document).ready(function() {
	jQuery('.pluginbuddy_tip').tooltip({ 
		track: true, 
		delay: 0, 
		showURL: false, 
		showBody: " - ", 
		fade: 250 
	});
});

function toggle_add_new_rotating_images_group() {
	jQuery('#add_new_rotating_images_group').slideToggle();
}

