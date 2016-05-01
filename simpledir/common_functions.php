<?php
// Common functions
function simpledir_format_bytes($size) {
  $units = array('B', 'KB', 'MB', 'GB', 'TB');
  for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
  return round($size, 2).$units[$i];
}

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
function simpledir_saveconf($data) {
  global $simpledir_conf;
  $configfile=GSDATAOTHERPATH . 'simpledir.xml';

  $xml_root = new SimpleXMLElement('<settings></settings>');
  $xml_root->addchild('dirpath', $simpledir_conf['dirpath']);
  $xml_root->addchild('urlpath', $simpledir_conf['urlpath']);
  $xml_root->addchild('ignore', implode(',', $simpledir_conf['ignore']));

  if ($xml_root->asXML($configfile) === FALSE) {
	  //exit('Error saving ' . $configfile . ', check folder privlidges.');
  }
}

// Get an array of the files/subdirs in a directory
function return_simpledir_results($params = array()) {
  // Default parameters
  $params = array_merge(array(
    'dirpath' => null,
    'urlpath' => null,
    'ignore'  => array(),
    'order'   => '+name',
  ), $params);

  $dirpath = $params['dirpath'];
  $urlpath = $params['urlpath'];
  $ignore  = $params['ignore'];

  // Copy the global $simpledir_conf
  $simpledir_conf = array_merge(array(), $GLOBALS['simpledir_conf']);

  // Merge defaults
  if (!empty($dirpath)) {
    $simpledir_conf['dirpath'] .= $dirpath;
  }

  if (!empty($urlpath)) {
    $simpledir_conf['urlpath'] .= $urlpath;
  }

  $simpledir_conf['ignore'] = $ignore;

  $simpledir_dir = $simpledir_conf['dirpath'];

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
          'size' => null,
          'type' => 'directory'
        );
      } elseif (!in_array(strtolower(substr(strrchr($filename,'.'),1)), $simpledir_conf['ignore'])) {
        $filesize = filesize($simpledir_dir.$filename);
        $filearray[] = array(
          'name' => $filename,
          'date' => date("Y/m/d H:i:s", filemtime($simpledir_dir.$filename)),
          'size' => $filesize,
          'type' => strtolower(substr(strrchr($filename,'.'),1))
        );
        $filetot += $filesize;
      }
    }
  }

  // Sort the files
  $order = $params['order'];
  $asc   = substr($order, 0, 1);

  if ($asc == '+' || $asc == '-') {
    $order = substr($order, 1);
  } else {
    $asc = '+';
  }

  // Build a callback function to pass to usort
  if ($order == 'size') {
    $callback = 'return $a["size"] - $b["size"];';
  } elseif ($order == 'date') {
    $callback = 'return strtotime($a["date"]) - strtotime($b["date"]);';
  } else {
    $callback = 'return strcmp($a["name"], $b["name"]);';
  }

  $sortcallback = create_function(($asc == '+' ? '$a, $b' : '$b, $a'), $callback);
  usort($filearray, $sortcallback);
  usort($subdirarray, $sortcallback);

  return array(
    'files'   => $filearray,
    'subdirs' => $subdirarray,
    'total'   => $filetot,
  );
}

function simpledir_i18n($hash, $echo = false) {
  return i18n(SIMPLEDIR . '/' . $hash, $echo);
}