<?php
  echo '<b>nanoPhotosProvide2 - installation check</b><br><br><br>';

  echo 'Current PHP version: ' . phpversion() .'<br>';

  if (extension_loaded('gd') && function_exists('gd_info')) {
      echo 'PHP GD library is installed on your web server';
  }
  else {
      echo 'PHP GD library is NOT installed on your web server';
  }
  echo '<br>';
    
  echo 'Free disk space: ' . disk_free_space('.');
  echo '<br><br>';
  
  echo '<b>Content folder:</b><br>' ;
  $config = parse_ini_file('./nano_photos_provider2.cfg', true);
  $content_folder = $config['config']['contentFolder'];
  $fileExtensions = $config['config']['fileExtensions'];

  
  $dh = opendir($content_folder);

  // check the content folder
  if ($dh != false) {
    while (false !== ($filename = readdir($dh))) {
      $k = 'album';
      if (is_file($content_folder . '/' . $filename) ) {
        $k= 'image';
      }
      else {
        // $files = glob($content_folder . '/' . $filename."/*.{".str_replace("|",",",$fileExtensions)."}", GLOB_BRACE);    // to check if folder contains images
        $files = preg_grep('~\.('.$fileExtensions.')$~', scandir($content_folder . '/' . $filename));
        $k = $k . ' -  ' . sizeof($files);
      }
      if ($filename != '.' && $filename != '..' && $filename != '_thumbnails' ) {
        echo '&nbsp;&nbsp;&nbsp;' . $filename . ' ['.$k.']<br>';
      }
    }
    closedir($dh);
  }  
  
  echo '<br><br><br><br><br>';
  phpinfo();
  
?>