<?php
// Chech whether we are indeed included by Piwigo.
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

load_language('plugin.lang', PIWIGOPANORAMA_PATH);

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+
check_status(ACCESS_ADMINISTRATOR);

// Fetch the template.
global $template, $conf;

// Update conf if submitted in admin site
if (isset($_POST['save_config']))
{
	$conf['piwigopanorama_conf'] = array(
		'auto_load' => isset($_POST['auto_load']),
		'show_zoomcntrl' => isset($_POST['show_zoomcntrl']),
		'enable_keyboard_zoom' => isset($_POST['enable_keyboard_zoom']),
		'enable_mouse_zoom' => isset($_POST['enable_mouse_zoom']),
		'cntrl_location' => $_POST['cntrl_location'],
		'show_compass' => isset($_POST['show_compass']),
		'initial_hfov' => $_POST['initial_hfov'],
	);

	// Update config to DB
	conf_update_param('piwigopanorama_conf', serialize($conf['piwigopanorama_conf']));
}

// pass config parameters to template
$template->assign(array(
  'PIWIGOPANORAMA_PATH' => PIWIGOPANORAMA_PATH,
  'piwigopanorama' => $conf['piwigopanorama_conf'],
  'all_control_locations' => array('TOP-LEFT', 'BOTTOM-CENTER'),
));

// Add our template to the global template
$template->set_filenames(
	array(
		'plugin_admin_content' => realpath(PIWIGOPANORAMA_PATH . '/template/admin.tpl')
	)
);
 
// Assign the template contents to ADMIN_CONTENT
$template->assign_var_from_handle('ADMIN_CONTENT', 'plugin_admin_content');
?>
