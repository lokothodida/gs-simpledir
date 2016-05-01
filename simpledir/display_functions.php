<?php
// Display functions
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

  if (isset($args['dirpath'])) {
    $params['dirpath'] = $args['dirpath'];
  }

  if (isset($args['urlpath'])) {
    $params['urlpath'] = $args['urlpath'];
  }

  if (isset($args['ignore'])) {
    $params['ignore'] = explode(',', $args['ignore']);
  }

  if (isset($args['key'])) {
    $params['key'] = $args['key'];
  }

  if (isset($args['order'])) {
    $params['order'] = $args['order'];
  }

  if (isset($args['columns'])) {
    $params['columns'] = explode(',', $args['columns']);
  }

  if (isset($args['showinitial'])) {
    $params['showinitial'] = (int) $args['showinitial'];
  }

  if (isset($args['showfilter'])) {
    $params['showfilter'] = strtolower($args['showfilter']) == 'true';
  }

  if (isset($args['sortable'])) {
    $params['sortable'] = strtolower($args['sortable']) == 'true';
  }

  return return_simpledir_display($params);
}

/***********************************************************************************
*
* Public Functions
*
***********************************************************************************/


// Return the HTML table of files in a directory
function return_simpledir_display($params = array()) {
  global $SITEURL;

  // Default parameters
  $defaultColumns = array('name', 'date', 'type', 'size');
  $params = array_merge(array(
    'dirpath' => null,
    'urlpath' => null,
    'ignore'  => array(),
    'key'     => 'subdir',
    'columns' => array('name', 'date', 'size'),
    'showinitial' => 0,
    'showfilter'  => false,
    'sortable'    => false,
  ), $params);

  $dirpath = $params['dirpath'];
  $urlpath = $params['urlpath'];
  $ignore  = $params['ignore'];
  $key     = $params['key'];

  $simpledir_conf = array_merge(array(), $GLOBALS['simpledir_conf']);
  $simpledir_conf['ignore'] = $ignore;

  $tmp_content = '';
  $currentdir = "";

  if((isset($_GET[$key])) && ($_GET[$key]<>'')) {
    $currentdir = urldecode($_GET[$key]) . '/';
  }

  $pretty_urls = (string) $GLOBALS['PRETTYURLS'] == '1';
  $current_url = explode('?', $_SERVER["REQUEST_URI"]);
  $current_url = $current_url[0];
  $current_url = $pretty_urls ? $current_url : '';

  // Copy the $_GET parameters to a new variable (used for generating full url correctly)
  $query = array();

  foreach ($_GET as $k => $v) {
    $query[$k] = $v;
  }

  // Remove the id parameter is pretty urls are disabled
  if (isset($query['id']) && $pretty_urls) {
    unset($query['id']);
  }

  if ($currentdir == "") {
    $simpledir_dir = $simpledir_conf['dirpath'];
  } else {
    $simpledir_dir = $simpledir_conf['dirpath'] . $currentdir;
  }

  $list = return_simpledir_results(array_merge($params, array(
    'dirpath' => $dirpath . $currentdir,
    'urlpath' => $urlpath,
    'ignore'  => $ignore,
  )));

  //check for directory traversal attempt and scrub to base directory
  if (strpos(realpath($simpledir_dir),$simpledir_conf['dirpath']) !== 0) {
    $simpledir_dir = $simpledir_conf['dirpath'];
  }

  if ($currentdir<>'') {
    $currentdir = rtrim($currentdir, '/') . '/';
  }

  $simpledir_content = '';

  // display list of  files
  $filearray = $list['files'];
  $subdirarray = $list['subdirs'];

  $simpledir_content .= '<table class="sd_table ' . $params['key'] . '">';

  if ($currentdir == "") {
    $simpledir_content .= '<caption>Directory Listing</caption>';
  } else {
    $simpledir_content .= '<caption>Subdirectory Listing for ' . $currentdir . '</caption>';
  }

  // Columns
  $columns = array_intersect($defaultColumns, $params['columns']);
  $simpledir_content .= '<thead><tr>';

  if (in_array('name', $columns)) {
    $simpledir_content .= '<th>Name</th>';
  }

  if (in_array('date', $columns)) {
    $simpledir_content .= '<th>Date</th>';
  }

  if (in_array('size', $columns)) {
    $simpledir_content .= '<th>Size</th>';
  }

  $simpledir_content .= '</tr></thead>';

  // generate listing:
  $simpledir_content .= '<tbody>';

  $rowclass="";

  // up to parent
  if ($currentdir<>'') {
    $parentdir = dirname($currentdir);
    $parentdir = ($parentdir == '.') ? '' : $parentdir;

    $query[$key] = $parentdir;

    $simpledir_content .= '<tr' . $rowclass . '>';

    if (in_array('name', $columns)) {
      $simpledir_content .= '<td><a href="' . $current_url .  '?' . http_build_query($query)
                         . '" title="Parent Directory"><img src="' . $SITEURL . 'plugins/simpledir/images/upfolder.png" width="16" height="16">&nbsp;Parent Directory</a></td>';
    }

    if (in_array('date', $columns)) {
      $simpledir_content .= '<td></td>';
    }

    if (in_array('size', $columns)) {
      $simpledir_content .= '<td></td>';
    }

    $simpledir_content .= '</tr>';
    $rowclass=' class="alt"';
  }


  // subdirectories
  $filecount = count($subdirarray);

  if ($filecount > 0) {
    foreach ($subdirarray as $file) {
      $query[$key] = $currentdir . $file['name'];

      $simpledir_content .= '<tr' . $rowclass . '>';

      if (in_array('name', $columns)) {
        // data-order attribute prefix is so sorting by name always does folders before files
        $simpledir_content .= '<td data-order="f1_' . $file['name'] . '"><a href="' . $current_url .  '?' . http_build_query($query)
                         . '"><img src="' . $SITEURL . 'plugins/simpledir/images/folder.png" width="16" height="16">&nbsp;' . $file['name'] . '</a></td>';
      }

      if (in_array('date', $columns)) {
        $simpledir_content .= '<td data-order="0">' . $file['date'] . '</td>';
      }

      if (in_array('size', $columns)) {
        $simpledir_content .= '<td data-order="0"></td>';
      }

      $simpledir_content .= '</tr>';

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
    foreach ($filearray as $file) {
      $simpledir_content .= '<tr' . $rowclass . '>';

      if (in_array('name', $columns)) {
        $simpledir_content .= '<td data-order="f2_' . $file['name'] .'"><a href="' . $simpledir_conf['urlpath'] . $urlpath . $currentdir . $file['name'] . '">'
	           . '<img src="' . $SITEURL . 'plugins/simpledir/images/' . $file['type'] . '.png" width="16" height="16">&nbsp;' . $file['name']
             . '</a></td>';
      }

      if (in_array('date', $columns)) {
        $simpledir_content .= '<td data-order="' . strtotime($file['date']) . '">' . $file['date'] . '</td>';
      }

      if (in_array('size', $columns)) {
        $simpledir_content .= '<td data-order="' . $file['size'] . '">' . simpledir_format_bytes($file['size']) . '</td>';
      }

      $simpledir_content .= '</tr>';

      if ($rowclass=="") {
        $rowclass=' class="alt"';
      } else {
        $rowclass="";
      }
    }
  }

  $simpledir_content .= '</tbody><tfoot><tr><th colspan="' . count($columns) . '">';

  if ($filecount==1) {
    $simpledir_content .= $filecount . ' file';
  } else {
    $simpledir_content .= $filecount . ' files';
  }

  $simpledir_content .= ' totaling ' . simpledir_format_bytes($list['total']);
  $simpledir_content .= '</th></tr></tfoot></table>';

  // Configuration for DataTable
  $datatable = array();
  $datatable['aaSorting'] = array();
  $datatable['lengthMenu'] = array(5, 10, 25, 50, 75, 100);
  $datatable['bFilter'] = $params['showfilter'];
  $datatable['bSort'] = $params['sortable'];

  if ($params['showinitial']) {
    $datatable['pageLength'] = $params['showinitial'];
    $datatable['bPaginate'] = true;
  } else {
    $datatable['bPaginate'] = false;
  }

  $simpledir_content.= '
    <script>
      $(function() {
        var datatable = $(".sd_table.'. $params['key'] . '").DataTable(' . json_encode($datatable) . ');
      });
    </script>
  ';

  return $simpledir_content;
}

/***********************************************************************************
*
* Display Functions
*
***********************************************************************************/
// Print the HTML table of the files in a directory
function get_simpledir_display($params = array()) {
  echo return_simpledir_display($params);
}