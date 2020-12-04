<?php
/*
	Plugin Name: Panorama Viewer
	Version: 1.0
	Description: Use pannellum.js to show panoramas. Based on PhotoSphere plugin from Mistic.
	Plugin URI: auto
	Author: Thomas Feuster
	Author URI: http://bilder.feuster.com
*/

// Chech whether we are indeed included by Piwigo.
if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');
 
// Define the path to our plugin.
define('PIWIGOPANORAMA_PATH', PHPWG_PLUGINS_PATH.basename(dirname(__FILE__)).'/');
define('PIWIGOPANORAMA_ADMIN', PIWIGOPANORAMA_PATH . 'admin.php');

add_event_handler('init', 'piwigopanorama_init');
function piwigopanorama_init()
{
	global $conf;

	if (defined('IN_ADMIN'))
	{
		load_language('plugin.lang', PIWIGOPANORAMA_PATH, array(
			'force_fallback' => 'en_UK'
		));
	}
	else
	{
		load_language('plugin.lang', PIWIGOPANORAMA_PATH);
	}

	$conf['piwigopanorama_conf'] = unserialize($conf['piwigopanorama_conf']);
}

/* admin events */

function piwigopanorama_admin_plugin_menu_links($menu)
{
  $menu[] = array(
    'NAME' => 'Panorama Viewer',
    'URL' => get_admin_plugin_menu_link(dirname(__FILE__)).'/admin.php',
    );

  return $menu;
}

function piwigopanorama_photo_page()
{
  global $template;
  
  if (isset($_POST['submit']))
  {
    $row['is_panorama'] = isset($_POST['is_panorama']);
    
    single_update(
      IMAGES_TABLE,
		/* fix for issue #1 */
      array('is_panorama' => ($row['is_panorama'])?1:0),
      array('id' => $_GET['image_id'])
      );
  }
  else
  {
    $query = '
SELECT is_panorama
  FROM '.IMAGES_TABLE.'
  WHERE id = '.$_GET['image_id'].'
;';
    $row = pwg_db_fetch_assoc(pwg_query($query));
  }
  
  $template->assign('is_panorama', $row['is_panorama']);
  $template->set_prefilter('picture_modify', 'piwigopanorama_photo_page_prefilter');
}

function piwigopanorama_photo_page_prefilter($content)
{
  $search = '<strong>{\'Linked albums\'|@translate}</strong>';
  $add = '
    <label style="font-weight:bold"><input type="checkbox" name="is_panorama" {if $is_panorama}checked{/if}> Panorama</label>
  </p>
  <p>';
  
  return str_replace($search, $add.$search, $content);
}

function piwigopanorama_add_prefilter($prefilters)
{
  $prefilters[] = array(
    'ID' => 'is_panorama',
    'NAME' => 'Panorama',
    );
  
  return $prefilters;
}

function piwigopanorama_apply_prefilter($filter_sets, $prefilter)
{
  if ($prefilter == 'is_panorama')
  {
    $query = 'SELECT id FROM '.IMAGES_TABLE.' where is_panorama = 1;';
    $filter_sets[] = query2array($query, null, 'id');
  }
  
  return $filter_sets;
}

function piwigopanorama_loc_end_element_set_global()
{
  global $template;

  $template->append('element_set_global_plugins_actions', array(
    'ID' => 'set_piwigopanorama',
    'NAME' => l10n('Set Panorama')
    ));
  
  $template->append('element_set_global_plugins_actions', array(
    'ID' => 'unset_piwigopanorama',
    'NAME' => l10n('Unset Panorama')
    ));
}

function piwigopanorama_element_set_global_action($action, $collection)
{
  global $redirect;

  if (strpos($action, 'piwigopanorama') !== false)
  {
    $is = $action == 'set_piwigopanorama';
    
    $datas = array();
    foreach ($collection as $image_id)
    {
      $datas[] = array(
        'id' => $image_id,
		/* fix for issue #1 */
        'is_panorama' => ($is?1:0)
        );
    }

    mass_updates(
      IMAGES_TABLE,
      array('primary' => array('id'), 'update' => array('is_panorama')),
      $datas
      );

    $redirect = true;
  }
}

/* public events */

function piwigopanorama_element_content($content, $element)
{
	global $template, $conf;

	if ($element['is_panorama'])
	{
		//echo print_r($element).PHP_EOL;

		// find image info in current content
		preg_match('/src="([^"]*)"/', $content, $matches);
		$image_src = $matches[1];
		preg_match('/width="([^"]*)"/', $content, $matches);
		$image_width = $matches[1];
		preg_match('/height="([^"]*)"/', $content, $matches);
		$image_height = $matches[1];

		// remove known attributes from img element - leave stuff e.g. from other plugins
		$search = '/( title="[^"]*")/';
		$content = preg_replace($search, '', $content);

		$search = '/( alt="[^"]*")/';
		$content = preg_replace($search, '', $content);

		$search = '/( width="[^"]*")/';
		$content = preg_replace($search, '', $content);

		$search = '/( height="[^"]*")/';
		$content = preg_replace($search, '', $content);

		$search = '/( src="[^"]*")/';
		$content = preg_replace($search, '', $content);
		
		// convert img to div - doesn't work with image element
		$search = '/<img/';
		$content = preg_replace($search, '<div', $content);
		
		// add pannellum stuff after the div & end it properly
		$replacement = '\\1</div>';
		
		// add js code that contains the parameters to pannellum
		$replacement = $replacement.'<script type="text/javascript">';
		$replacement = $replacement.'var panorama_options = {"type": "equirectangular"';
		// [path] => ./galleries/Toskana_2020/bologna/PXL_20200924_093503210.PHOTOSPHERE.jpg
		$image_path = $element['src_image']->rel_path;
		$replacement = $replacement.', "panorama": "'.$image_path.'"';
		$replacement = $replacement.', "autoLoad": '.($conf['piwigopanorama_conf']['auto_load'] ? 'true' : 'false');
		$replacement = $replacement.', "showZoomCtrl": '.($conf['piwigopanorama_conf']['show_zoomcntrl'] ? 'true' : 'false');
		$replacement = $replacement.', "keyboardZoom": '.($conf['piwigopanorama_conf']['enable_keyboard_zoom'] ? 'true' : 'false');
		$replacement = $replacement.', "mouseZoom": '.($conf['piwigopanorama_conf']['enable_mouse_zoom'] ? 'true' : 'false');
		$replacement = $replacement.', "compass": '.($conf['piwigopanorama_conf']['show_compass'] ? 'true' : 'false');
		$replacement = $replacement.', "hfov": '.$conf['piwigopanorama_conf']['initial_hfov'];
		$replacement = $replacement.'};';
		$replacement = $replacement.'</script>';
		
		// add css code that contains the size of the image
		$replacement = $replacement.'<style type="text/css">';
		$replacement = $replacement.'#theMainImage {';
		$replacement = $replacement.'margin: auto;';
		$replacement = $replacement.'width: '.$image_width.'px;';
		$replacement = $replacement.'height: '.$image_height.'px;';
		$replacement = $replacement.'}';
		// move controls bottom-center
		// https://stackoverflow.com/a/45968877
		if ($conf['piwigopanorama_conf']['cntrl_location'] == 1)
		{
			$replacement = $replacement.'.pnlm-controls-container {';
			$replacement = $replacement.'display: flex;';
			$replacement = $replacement.'flex-direction: row;';
			$replacement = $replacement.'justify-content: center;';
			$replacement = $replacement.'align-items: center;';
			$replacement = $replacement.'position: absolute;';
			$replacement = $replacement.'top: 90%;';
			$replacement = $replacement.'left: 50%;';
			$replacement = $replacement.'-webkit-transform: translateX(-50%);';
			$replacement = $replacement.'transform: translateX(-50%);';
			$replacement = $replacement.'}';
			$replacement = $replacement.'.pnlm-zoom-controls {';
			$replacement = $replacement.'transform: rotate(-90deg);';
			$replacement = $replacement.'margin-right: 16px;';
			$replacement = $replacement.'}';
			$replacement = $replacement.'.pnlm-zoom-out {';
			$replacement = $replacement.'transform: rotate(-90deg);';
			$replacement = $replacement.'}';
		}
		$replacement = $replacement.'</style>';

		$search = '/(<div [\s\S]*?>)/';
		$content = preg_replace($search, $replacement, $content);

		$template->set_filename('panorama_content', realpath(PIWIGOPANORAMA_PATH . 'template/picture_content.tpl'));
		
		$template->assign(array(
			'piwigopanorama' => $conf['piwigopanorama_conf'],
			'PIWIGOPANORAMA_PATH' => PIWIGOPANORAMA_PATH,
			'PIWIGOPANORAMA_CONTENT' => $content,
			));
		  
		return $template->parse('panorama_content', true);
	}

	return $content;
}

function piwigopanorama_admintools()
{
  global $picture, $template;
  
  if (defined('ADMINTOOLS_PATH'))
  {
    if (script_basename() == 'picture')
    {
      $template->assign('ato_QUICK_EDIT_is_panorama', $picture['current']['is_panorama']);
    }
    
    $template->set_prefilter('ato_public_controller', 'piwigopanorama_admintools_prefilter');
  }
}

function piwigopanorama_save_admintools()
{
  global $page, $MultiView, $user;
  
  if (defined('ADMINTOOLS_PATH'))
  {
    if (!isset($_POST['action']) || @$_POST['action'] != 'quick_edit')
    {
      return;
    }
    
    $query = 'SELECT added_by FROM '. IMAGES_TABLE .' WHERE id = '. $page['image_id'] .';';
    list($added_by) = pwg_db_fetch_row(pwg_query($query));

    if (!$MultiView->is_admin() and $user['id'] != $added_by)
    {
      return;
    }
  
    single_update(
      IMAGES_TABLE,
		/* fix for issue #1 */
      array('is_panorama' => (isset($_POST['is_panorama'])?1:0)),
      array('id' => $page['image_id'])
      );
  }
}

function piwigopanorama_admintools_prefilter($content)
{
	$search = '<label for="quick_edit_tags">';
	$add = '<label><input type="checkbox" style="width:auto;" name="is_panorama" {if $ato_QUICK_EDIT_is_panorama}checked{/if}> Photo Sphere</label>';

	return str_replace($search, $add.$search, $content);
}

/* setup callback */

if (defined('IN_ADMIN'))
{
	add_event_handler('get_admin_plugin_menu_links', 'piwigopanorama_admin_plugin_menu_links',
		EVENT_HANDLER_PRIORITY_NEUTRAL);

	add_event_handler('loc_end_picture_modify', 'piwigopanorama_photo_page',
		EVENT_HANDLER_PRIORITY_NEUTRAL);

	add_event_handler('get_batch_manager_prefilters', 'piwigopanorama_add_prefilter',
		EVENT_HANDLER_PRIORITY_NEUTRAL);

	add_event_handler('perform_batch_manager_prefilters', 'piwigopanorama_apply_prefilter',
		EVENT_HANDLER_PRIORITY_NEUTRAL);

	add_event_handler('loc_end_element_set_global', 'piwigopanorama_loc_end_element_set_global',
		EVENT_HANDLER_PRIORITY_NEUTRAL);

	add_event_handler('element_set_global_action', 'piwigopanorama_element_set_global_action',
		EVENT_HANDLER_PRIORITY_NEUTRAL);
}
else
{
	add_event_handler('render_element_content', 'piwigopanorama_element_content',
		EVENT_HANDLER_PRIORITY_NEUTRAL+10);

	add_event_handler('loc_after_page_header', 'piwigopanorama_admintools',
		EVENT_HANDLER_PRIORITY_NEUTRAL-10);

	add_event_handler('loc_begin_picture', 'piwigopanorama_save_admintools',
		EVENT_HANDLER_PRIORITY_NEUTRAL);
}

?>
