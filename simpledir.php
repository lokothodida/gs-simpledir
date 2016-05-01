<?php
/*
  Plugin Name: SimpleDir
  Description: a GetSimple CMS plugin to provide directory listings
  Version: 0.4
  Author: Rob Antonishen (updated by Lawrence Okoth-Odida)
  Author URI: http://ffaat.poweredbyclear.com/
*/

// == Constants ==
define('SIMPLEDIR', basename(__FILE__, '.php'));
define('SIMPLEDIR_PLUGINPATH', GSPLUGINPATH . SIMPLEDIR . '/');
define('SIMPLEDIR_CONFIGFILE', GSDATAOTHERPATH . 'simpledir.xml');
define('SIMPLEDIR_PLUGINURL', $SITEURL . 'plugins/' . SIMPLEDIR . '/');
define('SIMPLEDIR_IMGURL', SIMPLEDIR_PLUGINURL . 'images/');

// == Common functions (used throughout plugin) =
require_once(SIMPLEDIR_PLUGINPATH . 'common_functions.php');

// == Languages ==
i18n_merge(SIMPLEDIR) || i18n_merge(SIMPLEDIR, 'en_US');

// == Register plugin ==
register_plugin(
  SIMPLEDIR,
  simpledir_i18n('PLUGIN_TITLE'),
  '0.4',
  'Lawrence Okoth-Odida',
  'https://github.com/lokothodida/',
  simpledir_i18n('PLUGIN_DESC'),
  'plugins',
  'simpledir_config'
);

// == Register actions and filters ==
// Sidebar link
add_action('plugins-sidebar','createSideMenu', array(SIMPLEDIR, 'SimpleDir Settings'));
// Placeholder filter
add_filter('content','simpledir_display');

// == Register styles and scripts==
// DataTables
register_style('jquery-datatables', 'https://cdn.datatables.net/1.10.11/css/jquery.dataTables.min.css', null, 'screen');
register_script('jquery-datatables', 'https://cdn.datatables.net/1.10.11/js/jquery.dataTables.min.js', null, FALSE);

queue_style('jquery-datatables', GSFRONT);
queue_script('jquery', GSFRONT);
queue_script('jquery-datatables', GSFRONT);

// == Functions ==
// Admin Panel
function simpledir_config() {
  // Load admin functions
  include(SIMPLEDIR_PLUGINPATH . 'admin_functions.php');

  // Process POST form
  if (!empty($_POST)) {
    $data = array();

    // Validation
    if (isset($_POST['dirpath'])) {
      $data['dirpath'] = urldecode($_POST['dirpath']);
    }

    if (isset($_POST['urlpath'])) {
      $data['urlpath'] = urldecode($_POST['urlpath']);
    }

    if (isset($_POST['ignore'])) {
      $data['ignore'] = explode(',', urldecode($_POST['ignore']));
    }

    $succ = simpledir_saveconf($data);

    if ($succ) {
      simpledir_admin_message('updated', i18n_r('SETTINGS_UPDATED'));
    } else {
      simpledir_admin_message('error', i18n_r('ER_SETTINGS_UPD'));
    }
  }

  // Load config
  $simpledir_conf = simpledir_loadconf(true);

  // Settings Page
  include(SIMPLEDIR_PLUGINPATH . 'save_config.php');
}

// Frontend Display
function simpledir_display($contents) {
  require_once(SIMPLEDIR_PLUGINPATH . 'display_functions.php');
  return preg_replace_callback('/\(% simpledir(.*?)%\)/i', 'simpledir_display_callback', $contents);
}