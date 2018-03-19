<?php
/**
 * Header Template
 *
 * Here we setup all logic and XHTML that is required for the header section of all screens.
 *
 * @package WooFramework
 * @subpackage Template
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>" />
<title><?php wp_title(''); ?></title>
<?php woo_meta(); ?>
<link rel="pingback" href="<?php echo esc_url( get_bloginfo( 'pingback_url' ) ); ?>" />
<?php wp_head(); ?>
<?php woo_head(); ?>

<?php if (is_page( '1993') ) { ?>
<!--page custom JS-->
  <script type="text/javascript">
		jQuery(document).bind('gform_post_render', function(){
    // destroy default Gravity Form datepicker
    jQuery("#input_4_8").datepicker('destroy');
    // create new custom datepicker
    var oneWorkingDays = new Date();
    var adjustments = [0, 1, 1, 1, 1, 1, 0]; // Offsets by day of the week
    oneWorkingDays.setDate(oneWorkingDays.getDate() + 1 + adjustments[oneWorkingDays.getDay()]);
    jQuery("#input_4_8").datepicker({ beforeShowDay: jQuery.datepicker.noWeekends, minDate: '+1d', gotoCurrent: true, prevText: '', showOn: 'both', buttonImage: '/wp-content/plugins/gravityforms/images/calendar.png', buttonImageOnly: true });
});

<?php } ?>

</head>
<body <?php body_class(); ?>>
<?php woo_top(); ?>
<div id="wrapper">

	<div id="inner-wrapper">

	<?php woo_header_before(); ?>

	<header id="header" class="col-full">

		<?php woo_header_inside(); ?>

	</header>
	<?php woo_header_after(); ?>