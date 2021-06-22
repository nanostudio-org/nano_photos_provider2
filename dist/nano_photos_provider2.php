<?php
/**
 * nanoPhotosProvider2 add-on for nanogallery2
 *
 * This is an add-on for nanogallery2 (image gallery - https://nanogallery2.nanostudio.org).
 * This PHP application will publish your images and albums from a PHP webserver to nanogallery2.
 * The content is provided on demand, one album at one time.
 * Thumbnails and blurred preview images are generated automatically.
 * Dominant colors are extracted as a base64 GIF.
 * 
 * License: nanoPhotosProvider2 is open source and licensed under GPLv3 license. 
 *
 * PHP 5.3+
 * @version       1.2.2
 * @author        Christophe BRISBOIS - http://www.brisbois.fr/
 * @copyright     Copyright 2015+
 * @license       GPL v3
 * @link          https://github.com/nanostudio-org/nanoPhotosProvider2
 * @Support       https://github.com/nanostudio-org/nanoPhotosProvider2/issues
 *
 *
 * Thanks to:
 * - Ruplahlava  https://github.com/Ruplahlava
 * - EelcoA  - https://github.com/EelcoA
 * - eae710 - https://github.com/eae710
 * - Kevin Robert Keegan - https://github.com/krkeegan
 * - Jesper Cockx - https://github.com/jespercockx
 * - bhartvigsen - https://github.com/bhartvigsen
 */

 require './nano_photos_provider2.json.class.php';

// Available values development, production
// Codeigniter env switch https://github.com/bcit-ci/CodeIgniter/

define('ENVIRONMENT', 'production');




switch (ENVIRONMENT) {
    case 'development':
        error_reporting(-1);
        ini_set('display_errors', 1);
        $t = new galleryJSON();
        break;

        
    case 'production':
        ini_set('display_errors', 0);
        
        if (version_compare(PHP_VERSION, '5.3', '>=')) {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT & ~E_USER_NOTICE & ~E_USER_DEPRECATED);
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_USER_NOTICE);
        }

        // set_error_handler('myErrorHandler');
        function myErrorHandler($code, $message, $file, $line) {
          header("HTTP/1.1 200 OK");      // we catched the error, so we send OK to let nanogallery2 display the error message (and so avoid a browser error)
          header('Content-Type: application/json; charset=utf-8');
          $response = array('nano_status' => 'error', 'nano_message' => $message . '<br>  ('.basename($file).'/'.$line.')');
          $output = json_encode($response);
          echo $output;
          exit;
        }
        
        // called at the end of the script (including abnormal end)
        register_shutdown_function( function(){
            $last_error = error_get_last();
            if ($last_error['type'] === E_ERROR) {
                // fatal error
                myErrorHandler(E_ERROR, $last_error['message'], $last_error['file'], $last_error['line']);
            }
        });

        $t = new galleryJSON();
        break;

    default:
        header('HTTP/1.1 503 Service Unavailable.', TRUE, 503);
        echo 'The application environment is not set correctly.';
        exit(1); // EXIT_ERROR
}


?>
