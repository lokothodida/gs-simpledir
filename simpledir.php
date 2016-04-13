<?php
/*
  Plugin Name: SimpleDir
  Description: a GetSimple CMS plugin to provide directory listings
  Version: 0.4
  Author: Rob Antonishen (updated by Lawrence Okoth-Odida)
  Author URI: http://ffaat.poweredbyclear.com/
*/

// get correct id for plugin
$thisfile = basename(__FILE__, '.php');

// register plugin
register_plugin(
  $thisfile,
  'SimpleDir',
  '0.4',
  'Rob Antonishen',
  'http://ffaat.poweredbyclear.com/',
  'Provides a simple directory listing',
  'plugins',
  'simpledir_config'  
);

# global vars
$simpledir_conf = simpledir_loadconf();

// activate filter
add_filter('content','simpledir_display');
add_action('plugins-sidebar','createSideMenu',array($thisfile,'SimpleDir Settings'));

/***********************************************************************************
*
* Helper functions
*
***********************************************************************************/

function simpledir_format_bytes($size) {
  $units = array('B', 'KB', 'MB', 'GB', 'TB');
  for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
  return round($size, 2).$units[$i];
}

/***********************************************************************************
*
* Configuration functions
*
***********************************************************************************/

/* get config settings from file */
function simpledir_loadconf() {
  $vals=array();
  $configfile=GSDATAOTHERPATH . 'simpledir.xml';
  if (!file_exists($configfile)) {
    //default settings
    $xml_root = new SimpleXMLElement('<settings><dirpath>' . GSDATAUPLOADPATH . '</dirpath><urlpath>' . str_replace(GSROOTPATH, '', '/' . GSDATAUPLOADPATH) . '</urlpath><ignore>php</ignore></settings>');
    if ($xml_root->asXML($configfile) === FALSE) {
	  exit('Error saving ' . $configfile . ', check folder privlidges.');
    }
    if (defined('GSCHMOD')) {
	  chmod($configfile, GSCHMOD);
    } else {
      chmod($configfile, 0755);
    }
  }

  $xml_root = simplexml_load_file($configfile);
  
  if ($xml_root !== FALSE) {
    $node = $xml_root->children();
  
    $vals['dirpath'] = (string)$node->dirpath;
    $vals['urlpath'] = (string)$node->urlpath;
    $vals['ignore'] =  explode(',', (string)$node->ignore);

    if (empty($vals['dirpath'])) {
      $vals['dirpath'] = GSDATAUPLOADPATH;
    }
    
    if (empty($vals['urlpath'])) {
      $vals['urlpath'] = '/' . str_replace(GSROOTPATH, '', GSDATAUPLOADPATH);
    }
  }
  return($vals);
}

/* save config settings to file*/
function simpledir_saveconf() {
  global $simpledir_conf;
  $configfile=GSDATAOTHERPATH . 'simpledir.xml';

  $xml_root = new SimpleXMLElement('<settings></settings>');
  $xml_root->addchild('dirpath', $simpledir_conf['dirpath']);
  $xml_root->addchild('urlpath', $simpledir_conf['urlpath']);
  $xml_root->addchild('ignore', implode(',', $simpledir_conf['ignore']));
  
  if ($xml_root->asXML($configfile) === FALSE) {
	exit('Error saving ' . $configfile . ', check folder privlidges.');
  }
}

/***********************************************************************************
*
* Config Page
*
***********************************************************************************/
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
  return preg_replace_callback('/\(% simpledir(.*?)%\)/', 'simpledir_display_callback', $contents);
}

function simpledir_display_callback($matches) {
  global $simpledir_conf;
  $params = $matches[1];
  $params = explode(' ', $params);
  $args = array();

  foreach ($params as $param) {
    $param = explode('=', $param);
    $key = trim($param[0]);
    $value = isset($param[1]) ? trim(strtolower($param[1]), '"') : null;
    $args[$key] = $value;
  }


  $dirpath = isset($args['dirpath']) ? $args['dirpath'] : $simpledir_conf['dirpath'];
  $urlpath = isset($args['urlpath']) ? $args['urlpath'] : $simpledir_conf['urlpath'];
  $ignore  = isset($args['ignore']) ? $args['ignore'] : $simpledir_conf['ignore'];

  if (is_string($ignore)) {
    $ignore = explode(',', $ignore);
  }
  
  $key     = isset($args['key']) ? $args['key']: null;

  return return_simpledir_display($dirpath, $urlpath, $ignore, $key);
}

function return_simpledir_results($dirpath, $urlpath, $ignore) {
  global $SITEURL; 

  $simpledir_conf = array(
    'dirpath' => $dirpath,
    'urlpath' => $urlpath,
    'ignore'  => $ignore,
  );

  $currentdir = "";

  if((isset($_GET["subdir"])) && ($_GET["subdir"]<>'')) {
    $currentdir = urldecode($_GET["subdir"]) . '/';
  }

  $current_url = explode('?', $_SERVER["REQUEST_URI"]);
  $current_url = $current_url[0];

  if ($currentdir == "") {
    $simpledir_dir = $simpledir_conf['dirpath'];	
  } else {
    $simpledir_dir = $simpledir_conf['dirpath'] . $currentdir;	
  }
  
  // check for directory traversal attempt and scrub to base directory
  if (strpos(realpath($simpledir_dir),$simpledir_conf['dirpath']) !== 0) {
    $simpledir_dir = $simpledir_conf['dirpath'];
  }

  //rebuild clean param for links
  $currentdir = substr(realpath($simpledir_dir),strlen($simpledir_conf['dirpath']));
  if ($currentdir<>'') {
    $currentdir = $currentdir . '/';
  }

  // display list of  files
  $dir_handle  = @opendir($simpledir_dir) or exit('Unable to open the folder ' . $simpledir_dir . ', check the folder privileges.');
  $filearray   = array();
  $subdirarray = array();

  // get files
  $filetot = 0;

  while ($filename = readdir($dir_handle)) {
    // ignore dot files.
    if (substr($filename,0,1) <> '.') {
      // if directory
      if (is_dir($simpledir_dir.$filename)) {
        $subdirarray[] = array(
          'name' => $filename,
          'date' => date("Y/m/d H:i:s", filemtime($simpledir_dir.$filename)),
        );
      } elseif (!in_array(strtolower(substr(strrchr($filename,'.'),1)), $simpledir_conf['ignore'])) {
        $filesize = filesize($simpledir_dir.$filename);
        $filearray[] = array(
          'name' => $filename,
          'date' => date("Y/m/d H:i:s", filemtime($simpledir_dir.$filename)),
          'size' => simpledir_format_bytes($filesize),
          'type' => strtolower(substr(strrchr($filename,'.'),1))
        );
        $filetot += $filesize;
      }
    }
  }

  return array(
    'files'   => $filearray,
    'subdirs' => $subdirarray,
    'total'   => simpledir_format_bytes($filetot),
  );
}

function return_simpledir_display($dirpath, $urlpath, $ignore, $key = 'subdir') {
  global $SITEURL;
  
  $simpledir_conf = array(
    'dirpath' => $dirpath,
    'urlpath' => $urlpath,
    'ignore'  => $ignore,
  );
  
  $tmp_content = '';
  $currentdir = "";

  if((isset($_GET[$key])) && ($_GET[$key]<>'')) {
    $currentdir = urldecode($_GET[$key]) . '/';
  }

  $current_url = explode('?', $_SERVER["REQUEST_URI"]);
  $current_url = $current_url[0];
  $query = array_merge(array(), $_GET);

  if (isset($query['id'])) {
    unset($query['id']);
  }

  if ($currentdir == "") {
    $simpledir_dir = $simpledir_conf['dirpath'];	
  } else {
    $simpledir_dir = $simpledir_conf['dirpath'] . $currentdir;	
  }
  
  $list = return_simpledir_results($simpledir_dir, $urlpath, $ignore);

  //check for directory traversal attempt and scrub to base directory
  if (strpos(realpath($simpledir_dir),$simpledir_conf['dirpath']) !== 0) {
    $simpledir_dir = $simpledir_conf['dirpath'];
  }

  //rebuild clean param for links
  $currentdir = substr(realpath($simpledir_dir),strlen($simpledir_conf['dirpath']));
  if ($currentdir<>'')
    $currentdir = $currentdir . '/';
    
  $simpledir_content = '';

  // display list of  files
  $filearray = $list['files'];
  $subdirarray = $list['subdirs'];

  $simpledir_content .= '<table id="sd_table">';

  if ($currentdir == "") {
    $simpledir_content .= '<caption>Directory Listing</caption>';
  } else {
    $simpledir_content .= '<caption>Subdirectory Listing for ' . $currentdir . '</caption>';
  }

  $simpledir_content .= '<thead><tr><th>Name</th><th>Date</th><th>Size</th></tr></head>';
  
  // generate listing:
  $simpledir_content .= '<tbody>';

  $rowclass="";

  // up to parent
  if ($currentdir<>'') {
    $parentdir = substr($currentdir, 0, strrpos($currentdir,'/',-2));
    $query[$key] = $parentdir;
    $simpledir_content .= '<tr' . $rowclass . '><td><a href="' . $current_url .  '?' . http_build_query($query)
                         . '" title="Parent Directory"><img src="' . $SITEURL . 'plugins/simpledir/images/upfolder.png" width="16" height="16">&nbsp;Parent Directory</a></td><td colspan="3"></td></tr>';
    $rowclass=' class="alt"';
  }
    

  // subdirectories
  $filecount = count($subdirarray);

  if ($filecount > 0) {
    sort($subdirarray);
    foreach ($subdirarray as $file) {
      $query[$key] = $currentdir . $file['name'];
      $simpledir_content .= '<tr' . $rowclass . '><td><a href="' . $current_url .  '?' . http_build_query($query)
                         . '"><img src="' . $SITEURL . 'plugins/simpledir/images/folder.png" width="16" height="16">&nbsp;' . $file['name'] . '</a></td><td colspan="2">' . $file['date'] . '</td></tr>';
      if ($rowclass=="") {
        $rowclass=' class="alt"';
      } else {
        $rowclass="";
      }
    }
  }      

  // files
  $filecount = count($filearray);

  if ($filecount > 0) {
    sort($filearray);
    foreach ($filearray as $file) {
      $simpledir_content .= '<tr' . $rowclass . '><td><a href="' . $simpledir_conf['urlpath'] . $currentdir . $file['name'] . '">'
	           . '<img src="' . $SITEURL . 'plugins/simpledir/images/' . $file['type'] . '.png" width="16" height="16">&nbsp;' . $file['name']
                         . '</a></td><td>' . $file['date'] . '</td><td>' . $file['size'] . '</td></tr>';
      if ($rowclass=="") {
        $rowclass=' class="alt"';
      } else {
        $rowclass="";
      }
    }
  }      

  $simpledir_content .= '<tr' . $rowclass . '><td colspan="3">';

  if ($filecount==1) {
    $simpledir_content .= $filecount . ' file';
  } else {
    $simpledir_content .= $filecount . ' files';
  }

  $simpledir_content .= ' totaling ' . $list['total'] . ' </td></tr>';
  $simpledir_content .= '</tbody></table><br />';  

  return $simpledir_content;
}

function get_simpledir_display($dirpath, $urlpath, $ignore, $key = 'subdir') {
  echo return_simpledir_display($dirpath, $urlpath, $ignore, $key);
}