<?php
/*
  Plugin Name: SimpleDir
  Description: a GetSimple CMS plugin to provide directory listings
  Version: 0.4
  Author: Rob Antonishen (updated by Lawrence Okoth-Odida)
  Author URI: http://ffaat.poweredbyclear.com/
*/

// Constants
define('SIMPLEDIR', basename(__FILE__, '.php'));
define('SIMPLEDIR_PLUGINPATH', GSPLUGINPATH . SIMPLEDIR . '/');
define('SIMPLEDIR_CONFIGFILE', GSDATAOTHERPATH . 'simpledir.xml');

// get correct id for plugin
$thisfile = basename(__FILE__, '.php');

// == Common =
require_once(SIMPLEDIR_PLUGINPATH . 'common_functions.php');

// == Languages ==
i18n_merge(SIMPLEDIR) || i18n_merge(SIMPLEDIR, 'en_US');

// register plugin
register_plugin(
  SIMPLEDIR,
  simpledir_i18n('PLUGIN_TITLE'),
  '0.4',
  'Rob Antonishen',
  'http://ffaat.poweredbyclear.com/',
  simpledir_i18n('PLUGIN_DESC'),
  'plugins',
  'simpledir_config'
);

# global vars
$simpledir_conf = simpledir_loadconf();

// == Register actions and filters ==
// activate filter
add_filter('content','simpledir_display');
add_action('plugins-sidebar','createSideMenu', array(SIMPLEDIR, 'SimpleDir Settings'));

// == Register styles and scripts==
// CSS/JS
register_style('jquery-datatables', 'https://cdn.datatables.net/1.10.11/css/jquery.dataTables.min.css', null, 'screen');
register_script('jquery-datatables', 'https://cdn.datatables.net/1.10.11/js/jquery.dataTables.min.js', null, FALSE);

queue_style('jquery-datatables', GSFRONT);
queue_script('jquery', GSFRONT);
queue_script('jquery-datatables', GSFRONT);

// == Functions ==
// Admin Panel
function simpledir_config() {
  global $simpledir_conf;

  if (isset($_POST) && sizeof($_POST)>0) {
    /* Save Settings */
    if (isset($_POST['dirpath'])) {
      $simpledir_conf['dirpath'] = urldecode($_POST['dirpath']);
    }
    if (isset($_POST['urlpath'])) {
      $simpledir_conf['urlpath'] = urldecode($_POST['urlpath']);
    }
    if (isset($_POST['ignore'])) {
      $simpledir_conf['ignore'] = explode(',', urldecode($_POST['ignore']));
    }

    simpledir_saveconf();
    $simpledir_conf = simpledir_loadconf();

    echo
    '<script type="text/javascript">
      $(function() {
        var msg = ' . json_encode(i18n_r('SETTINGS_UPDATED')) . ';
        $("div.bodycontent").before(
          "<div class=\"updated\" style=\"display:block;\">" + msg + "</div>");
        $(".updated, .error").fadeOut(500).fadeIn(500);
      });
    </script>';
  }

  echo '<h3>SimpleDir Plugin Settings</h3>';
  echo '<form name="settings" action="load.php?id=simpledir" method="post">';

  echo '<label>Full Server Path to Directory (example <i>/home/user/data/uploads/):</i></label>';
  echo '<p><input class="text" name="dirpath" type="text" size="90" value="' . $simpledir_conf['dirpath'] .'"></p>';

  echo '<label>Base URL for Directory (example <i>/data/uploads/)</i>:</label>';
  echo '<p><input class="text" name="urlpath" type="text" size="90" value="' . $simpledir_conf['urlpath'] .'"></p>';

  echo '<label>Extensions to Ignore (comma separated, no spaces. Example <i>php,txt</i>:</label>';
  echo '<p><input class="text" name="ignore" type="text" size="90" value="' . implode(',',$simpledir_conf['ignore']) .'"></p>';


  echo "<input name='submit_settings' class='submit' type='submit' value='" . i18n_r('BTN_SAVESETTINGS') . "'><br />";
  echo '</form>';
  echo '<br /><p><i>Insert (% simpledir %) as the page content where you wish the directory to appear.  Don\'t forget to modify the CSS using the included CSS file as a guide.</i></p>';
}


/***********************************************************************************
*
* Frontend display
*
***********************************************************************************/
function simpledir_display($contents) {
  require_once(SIMPLEDIR_PLUGINPATH . 'display_functions.php');
  return preg_replace_callback('/\(% simpledir(.*?)%\)/i', 'simpledir_display_callback', $contents);
}