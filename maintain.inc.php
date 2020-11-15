<?php
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

if (!defined('PIWIGOPANORAMA_PATH'))
	define('PIWIGOPANORAMA_PATH', PHPWG_PLUGINS_PATH . basename(dirname(__FILE__)).'/');

function plugin_install()
{
	$default_config = array(
		'auto_load' => true,
		'show_zoomcntrl' => false,
		'enable_keyboard_zoom' => true,
		'enable_mouse_zoom' => true,
		'cntrl_location' => 1,
		'show_compass' => false,
		'initial_hfov' => 180,
	);
	/* Add configuration to the config table */
	$conf['piwigopanorama_conf'] = serialize($default_config);
	conf_update_param('piwigopanorama_conf', $conf['piwigopanorama_conf']);

	$q = 'UPDATE '.CONFIG_TABLE.' SET `comment` = "Configuration settings for piwigo-panorama plugin" WHERE `param` = "piwigopanorama_conf";';
	pwg_query( $q );

	$result = pwg_query('SHOW COLUMNS FROM `'.IMAGES_TABLE.'` LIKE "is_panorama";');
	if (!pwg_db_num_rows($result))
	{
		pwg_query('ALTER TABLE `' . IMAGES_TABLE . '` ADD `is_panorama` TINYINT(1) NOT NULL DEFAULT 0;');
	}
}

function plugin_activate()
{
	global $conf;

	if (!isset($conf['piwigopanorama_conf']))
	{
		plugin_install();
	}
}

function plugin_uninstall()
{
	/* Remove configuration from the config table */
	conf_delete_param('piwigopanorama_conf');

	pwg_query('ALTER TABLE `'. IMAGES_TABLE .'` DROP `is_panorama`;');
}
?>
