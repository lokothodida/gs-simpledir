<?php
// Display functions
function simpledir_display_callback($matches) {
  $_params = $matches[1];
  $_params = explode(' ', $_params);
  $params = array();
  $args = array();

  foreach ($_params as $param) {
    $param = explode('=', $param);
    $key = trim($param[0], '" ');

    if (isset($param[1])) {
      $value = trim($param[1], '" ');
      $params[$key] = $value;
    }
  }

  if (isset($params['dirpath'])) {
    $params['dirpath'] = $params['dirpath'];
  }

  if (isset($params['urlpath'])) {
    $params['urlpath'] = $params['urlpath'];
  }

  if (isset($params['ignore'])) {
    $params['ignore'] = explode(',', $params['ignore']);
  }

  if (isset($params['key'])) {
    $params['key'] = $params['key'];
  }

  if (isset($params['order'])) {
    $params['order'] = $params['order'];
  }

  if (isset($params['columns'])) {
    $params['columns'] = explode(',', $params['columns']);
  }

  if (isset($params['showinitial'])) {
    $params['showinitial'] = (int) $params['showinitial'];
  }

  if (isset($params['showfilter'])) {
    $params['showfilter'] = strtolower($params['showfilter']) == 'true';
  }

  if (isset($params['sortable'])) {
    $params['sortable'] = strtolower($params['sortable']) == 'true';
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
    'LABEL_NAME'  => simpledir_i18n('LABEL_NAME'),
    'LABEL_SIZE'  => simpledir_i18n('LABEL_SIZE'),
    'LABEL_DATE'  => simpledir_i18n('LABEL_DATE'),
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
    $simpledir_content .= '<caption>' . simpledir_i18n('DIR_LIST') . '</caption>';
  } else {
    $simpledir_content .= '<caption>' . (str_replace('%s', $currentdir, simpledir_i18n('SUBDIR_LIST'))) . '</caption>';
  }

  // Columns
  $columns = array_intersect($defaultColumns, $params['columns']);
  $simpledir_content .= '<thead><tr>';

  if (in_array('name', $columns)) {
    $simpledir_content .= '<th>' . $params['LABEL_NAME'] . '</th>';
  }

  if (in_array('date', $columns)) {
    $simpledir_content .= '<th>' . $params['LABEL_DATE'] . '</th>';
  }

  if (in_array('size', $columns)) {
    $simpledir_content .= '<th>' . $params['LABEL_SIZE'] . '</th>';
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
                         . '" title="' . simpledir_i18n('PARENT_DIR') . '"><img src="' . SIMPLEDIR_IMGURL . '/upfolder.png" width="16" height="16">&nbsp;' . simpledir_i18n('PARENT_DIR') . '</a></td>';
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
                         . '"><img src="' . SIMPLEDIR_IMGURL . 'folder.png" width="16" height="16">&nbsp;' . $file['name'] . '</a></td>';
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
	           . '<img src="' . SIMPLEDIR_IMGURL . '/' . $file['type'] . '.png" width="16" height="16">&nbsp;' . $file['name']
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