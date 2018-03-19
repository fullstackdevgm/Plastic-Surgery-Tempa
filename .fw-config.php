<?php
/*
Plugin Name: Flywheel
Plugin URI: http://getFlywheel.com
Description: Plugins and configuration required by Flywheel.
Version: 0.1.0
Author: Flywheel
Author URI: http://getFlywheel.com
License: BSD
*/
if(file_exists(FLYWHEEL_PLUGIN_DIR.'/flywheel')) {
	require FLYWHEEL_PLUGIN_DIR.'/flywheel/flywheel-core.php';
	require FLYWHEEL_PLUGIN_DIR.'/flywheel/flywheel-wordpress-admin/flywheel-wordpress-admin.php';
	require FLYWHEEL_PLUGIN_DIR.'/flywheel/flywheel-security/flywheel-security.php';
	require FLYWHEEL_PLUGIN_DIR.'/flywheel/disable-wordpress-core-update/disable-core-update.php';
	require FLYWHEEL_PLUGIN_DIR.'/flywheel/cdn.php';
	require FLYWHEEL_PLUGIN_DIR.'/flywheel/upgrade.php';
	require FLYWHEEL_PLUGIN_DIR.'/flywheel/limit-login-attempts/limit-login-attempts.php';
	require FLYWHEEL_PLUGIN_DIR.'/flywheel/xmlrpc_methods_disable.php';
}
