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

require './nano_photos_provider2.encoding.php';

class galleryData
{
    public $fullDir = '';
    //public $images;
    //public $URI;
}

class item
{
    public $src         = '';             // image URL
    public $title       = '';             // item title
    public $description = '';             // item description
    public $tags        = '';             // item tags
    public $ID          = '';             // item ID
    public $albumID     = '0';            // parent album ID
    public $kind        = '';             // 'album', 'image'
    public $t_url       = array();        // thumbnails URL
    public $t_width     = array();        // thumbnails width
    public $t_height    = array();        // thumbnails height
    public $dc          = '#888';         // image dominant color
    public $mtime       = '';             // modified time of image or album
    public $ctime       = '';             // creation time of image or album
    public $sort        = '';             // a sort string not displayed
    // public $dcGIF       = '#000';      // image dominant color


}

class galleryJSON
{
    protected $config   = array();
    protected $data;
    protected $albumID;
    protected $album;
    protected $tn_size  = array();
    protected $ctn_urls = array();
    protected $ctn_w    = array();
    protected $ctn_h    = array();
    protected $currentItem;
            
    const CONFIG_FILE    = './nano_photos_provider2.cfg';
    const APP_VERSION    = '1.2';                                 

    public function __construct()
    {
      // retrieve the album ID in the URL
      $this->album   = '/';
      $this->albumID = '';
      if (isset($_GET['albumID'])) {
        $this->albumID = rawurldecode($_GET['albumID']);

      }
      if (!$this->albumID == '0' && $this->albumID != '' && $this->albumID != null) {
        $this->album = '/' . $this->CustomDecode($this->albumID) . '/';
      } else {
        $this->albumID = '0';
      }

      $this->setConfig(self::CONFIG_FILE);
      

      // thumbnail responsive sizes

      // default and server side defined sizes
      // $this->tn_size['wxs'] = $this->config['thumbnails']['width_xs'];
      // $this->tn_size['hxs']   = $this->config['thumbnails']['height_xs'];
      // $this->tn_size['wsm'] = $this->config['thumbnails']['width_sm'];
      // $this->tn_size['hsm']   = $this->config['thumbnails']['height_sm'];
      // $this->tn_size['wme'] = $this->config['thumbnails']['width_me'];
      // $this->tn_size['hme']   = $this->config['thumbnails']['height_me'];
      // $this->tn_size['wla'] = $this->config['thumbnails']['width_la'];
      // $this->tn_size['hla']   = $this->config['thumbnails']['height_la'];
      // $this->tn_size['wxl'] = $this->config['thumbnails']['width_xl'];
      // $this->tn_size['hxl']   = $this->config['thumbnails']['height_xl'];

      // if( $this->config['thumbnails']['serverSizeDefinition'] == false ) {
        // server side size definition is only used for default values
        if (array_key_exists('wxs', $_GET)) {
          $this->tn_size['wxs']   = strtolower($this->CheckThumbnailSize( $_GET['wxs'] ));
        }
        if (array_key_exists('hxs', $_GET)) {
          $this->tn_size['hxs']   = strtolower($this->CheckThumbnailSize( $_GET['hxs'] ));
        }
        
        if (array_key_exists('wsm', $_GET)) {
          $this->tn_size['wsm']   = strtolower($this->CheckThumbnailSize( $_GET['wsm'] ));
        }
        if (array_key_exists('hsm', $_GET)) {
          $this->tn_size['hsm']   = strtolower($this->CheckThumbnailSize( $_GET['hsm'] ));
        }

        if (array_key_exists('wme', $_GET)) {
          $this->tn_size['wme']   = strtolower($this->CheckThumbnailSize( $_GET['wme'] ));
        }
        if (array_key_exists('hme', $_GET)) {
          $this->tn_size['hme']   = strtolower($this->CheckThumbnailSize( $_GET['hme'] ));
        }

        if (array_key_exists('wla', $_GET)) {
          $this->tn_size['wla']   = strtolower($this->CheckThumbnailSize( $_GET['wla'] ));
        }
        if (array_key_exists('hla', $_GET)) {
          $this->tn_size['hla']   = strtolower($this->CheckThumbnailSize( $_GET['hla'] ));
        }

        if (array_key_exists('wxl', $_GET)) {
          $this->tn_size['wxl']   = strtolower($this->CheckThumbnailSize( $_GET['wxl'] ));
        }
        if (array_key_exists('hxl', $_GET)) {
          $this->tn_size['hxl']   = strtolower($this->CheckThumbnailSize( $_GET['hxl'] ));
        }
      // }        
      
      
      


      
      $this->data           = new galleryData();
      $this->data->fullDir  = ($this->config['contentFolder']) . ($this->album);

      $lstImages = array();
      $lstAlbums = array();
      
      $dh = opendir($this->data->fullDir);

      // check if cached JSON is up to date
      $JSON_file = $this->data->fullDir . '_thumbnails/cache.json';
      if (file_exists( $JSON_file )) {
        $JSON_time = filemtime($JSON_file);
        
        // loop to compare files/directories creation time
        if ($dh != false) {
        
          $uptodate = true;
          while (false !== ($filename = readdir($dh))) {
            if (is_file($this->data->fullDir . $filename) ) {
              // it's a file
              if ($filename != '.' &&
                      $filename != '..' &&
                      $filename != '_thumbnails' &&
                      // preg_match - If the i modifier is set, letters in the pattern match both upper and lower case letters 
                      preg_match("/\.(" . $this->config['fileExtensions'] . ")*$/i", $filename) &&
                      strpos($filename, $this->config['ignoreDetector']) == false )
              {
                // check creation and modification time
                if( filemtime( $this->data->fullDir . $filename ) > $JSON_time || filectime( $this->data->fullDir . $filename ) > $JSON_time ) {
                  $uptodate = false;
                  break;
                }
              }
            }
            else {
              // it's a folder
              $files = preg_grep('~\.('.$this->config['fileExtensions'].')$~i', scandir($this->data->fullDir . $filename));     // to check if folder contains images
              if ($filename != '.' &&
                      $filename != '..' &&
                      $filename != '_thumbnails' &&
                      strpos($filename, $this->config['ignoreDetector']) == false && 
                      !empty($files) )
              {
                // check creation and modification time
                if( filemtime( $this->data->fullDir . $filename ) > $JSON_time || filectime( $this->data->fullDir . $filename ) > $JSON_time ) {
                  $uptodate = false;
                  break;
                }
                // $lstAlbums[] = $this->PrepareData($filename, 'ALBUM');
              }
            }
          }
          
          if( $uptodate == true ) {
            // JSON cached file is uptodate -> use it
            $objData = file_get_contents($JSON_file);
            $response = unserialize($objData);           
            if( !empty($response) ){
              closedir($dh);
              $this->SendData($response);
              exit ;
            }
          }

          rewinddir( $dh );          
        }
      }                                           
      // loop the folder to retrieve images and albums
      if ($dh != false) {
        while (false !== ($filename = readdir($dh))) {
          if (is_file($this->data->fullDir . $filename) ) {
            // it's a file
            if ($filename != '.' &&
                    $filename != '..' &&
                    $filename != '_thumbnails' &&
                    preg_match("/\.(" . $this->config['fileExtensions'] . ")*$/i", $filename) &&
                    strpos($filename, $this->config['ignoreDetector']) == false )
            {
              $lstImages[] = $this->PrepareData($filename, 'IMAGE');
            }
          }
          else {
            // it's a folder
            //$files = glob($this->data->fullDir . $filename."/*.{".str_replace("|",",",$this->config['fileExtensions'])."}", GLOB_BRACE);    // to check if folder contains images - warning - glob is not supported by all platforms
            $files = preg_grep('~\.('.$this->config['fileExtensions'].')$~i', scandir($this->data->fullDir . $filename));     // to check if folder contains images
            if ($filename != '.' &&
                    $filename != '..' &&
                    $filename != '_thumbnails' &&
                    strpos($filename, $this->config['ignoreDetector']) == false && 
                    !empty($files) )
            {
              $lstAlbums[] = $this->PrepareData($filename, 'ALBUM');
            }
          }
        }
        closedir($dh);
      }
			else {
				// album not found
				$response = array( 'nano_status' => 'error', 'nano_message' => 'album not found: ' . rawurlencode($this->data->fullDir) );
				$this->SendData($response);
				exit;
			}

      // sort data
      // usort($lstAlbums, array('galleryJSON', 'Compare'));
      // usort($lstImages, array('galleryJSON', 'Compare'));
      usort($lstAlbums, array('galleryJSON', 'CompareAlbum'));
      usort($lstImages, array('galleryJSON', 'CompareFile'));

      $response = array('nano_status' => 'ok', 'nano_message' => '', 'album_content' => array_merge($lstAlbums, $lstImages));

      $this->SendData($response);
       // cache JSON to disk
      if (!file_exists( $this->data->fullDir . '_thumbnails' )) {
        mkdir( $this->data->fullDir . '_thumbnails', 0755, true );
      }
      $fp = fopen($JSON_file, "w");
      fwrite($fp, serialize( $response ));
      fclose($fp);
                         
    }
    
    /**
     * CHECK IF THUMBNAIL SIZE IS ALLOWED (if not allowed: send error message and exit)
     * 
     * @param string $size
     * @return boolean
     */
    protected function CheckThumbnailSize( $size )
    {
      if( !array_key_exists("allowedSizeValues",$this->config['thumbnails']) || $this->config['thumbnails']['allowedSizeValues'] == "" ) {
        // no size restriction
        return $size;
      }
      
      $s=explode('|', $this->config['thumbnails']['allowedSizeValues']);
      if( is_array($s) ) {
        foreach($s as $one) {
          $one = trim($one);
          if( $one == $size ) {
            return $size;
          }
        }
      }
      
      $response = array( 'nano_status' => 'error', 'nano_message' => 'requested thumbnail size not allowed: '. $size );
      $this->SendData($response);
      exit;
      
    }
    

    
    /**
     * SEND THE RESPONSE BACK
     * 
     * @param string $response
     */
    protected function SendData( $response )
    {
      // set the Access-Control-Allow-Origin header
      $h=explode('|', $this->config['security']['allowOrigins']);
      $cnt=0;
      if( is_array($h) ) {
        foreach($h as $one) {
          $one = trim($one);
          $overwrite = false;
          if( $cnt == 0 ) {
            $overwrite=true;
          }
          header('Access-Control-Allow-Origin: ' . $one , $overwrite);
          $cnt++;
        }
      }
      
      // set the content-type header
      header('Content-Type: application/json; charset=utf-8');
    
      // add app version
      $response['nanophotosprovider'] = self::APP_VERSION;
      // return the data
      $output = json_encode($response);     // UTF-8 encoding is mandatory
      if (isset($_GET['jsonp'])) {
        // return in JSONP
        echo $_GET['jsonp'] . '(' . $output . ')';
      } else {
        // return in JSON
        echo $output;
      }
    
    }
    
    protected function setConfig($filePath)
    {
      $config = parse_ini_file($filePath, true);
      
      // general settings
      $this->config['contentFolder']          = $config['config']['contentFolder'];
      $this->config['fileExtensions']         = $config['config']['fileExtensions'];
      $this->config['sortOrder']              = strtoupper($config['config']['sortOrder']);
      // Check if a different sort order is specified for albums
      if (array_key_exists('sortOrderAlbum', $config['config'])){
        $this->config['sortOrderAlbum']       = strtoupper($config['config']['sortOrderAlbum']);
      }
      else {
        $this->config['sortOrderAlbum']       = strtoupper($config['config']['sortOrder']);
      }
      $this->config['titleDescSeparator']     = $config['config']['titleDescSeparator'];
      $this->config['sortPrefixSeparator']    = $config['config']['sortPrefixSeparator'];
      $this->config['albumCoverDetector']     = $config['config']['albumCoverDetector'];
      $this->config['ignoreDetector']         = strtoupper($config['config']['ignoreDetector']);

      // memory usage
      if( $config['memory']['unlimited'] == true ) {
        ini_set('memory_limit', '-1');
      }
      
      // images
      $this->config['images']['maxSize'] = 0;
      $ms = $config['images']['maxSize'];
      if( ctype_digit(strval($ms)) ){
        $this->config['images']['maxSize'] = $ms;
      }
      $iq = $config['images']['jpegQuality'];
      $this->config['images']['jpegQuality'] = 85; // default jpeg quality
      if( ctype_digit(strval($iq)) ){
        $this->config['images']['jpegQuality'] = $iq;
      }
      
      // thumbnails
      $tq = $config['thumbnails']['jpegQuality'];
      $this->config['thumbnails']['jpegQuality'] = 85; // default jpeg quality
      if( ctype_digit(strval($tq)) ){
        $this->config['thumbnails']['jpegQuality'] = $tq;
      }

      // if( isset( $config['thumbnails']['serverSizeDefinition'] ) ) {
        // $this->config['thumbnails']['serverSizeDefinition'] = $config['thumbnails']['serverSizeDefinition'];
      // }
      // else {
        // $this->config['thumbnails']['serverSizeDefinition'] = false;
      // }
      // $this->config['thumbnails']['width_xs'] = $config['thumbnails']['width_xs'];
      // $this->config['thumbnails']['height_xs'] = $config['thumbnails']['height_xs'];
      // $this->config['thumbnails']['width_sm'] = $config['thumbnails']['width_sm'];
      // $this->config['thumbnails']['height_sm'] = $config['thumbnails']['height_sm'];
      // $this->config['thumbnails']['width_me'] = $config['thumbnails']['width_me'];
      // $this->config['thumbnails']['height_me'] = $config['thumbnails']['height_me'];
      // $this->config['thumbnails']['width_la'] = $config['thumbnails']['width_la'];
      // $this->config['thumbnails']['height_la'] = $config['thumbnails']['height_la'];
      // $this->config['thumbnails']['width_xl'] = $config['thumbnails']['width_xl'];
      // $this->config['thumbnails']['height_xl'] = $config['thumbnails']['height_xl'];
      
      
      $tbq = $config['thumbnails']['blurredImageQuality'];
      $this->config['thumbnails']['blurredImageQuality'] = 3; // default blurred image quality
      if( ctype_digit(strval($tbq)) ){
        $this->config['thumbnails']['blurredImageQuality'] = $tbq;
      }

      $asv = trim($config['thumbnails']['allowedSizeValues']);
      if( $asv != '' ) {
         $this->config['thumbnails']['allowedSizeValues']=$asv;
      }
      

      
      // security
      $this->config['security']['allowOrigins'] = $config['security']['allowOrigins'];
    }

    /**
     * RETRIEVE THE COVER IMAGE (THUMBNAIL) OF ONE ALBUM (FOLDER)
     * 
     * @param string $baseFolder
     * @return string
     */
    protected function GetAlbumCover($baseFolder)
    {

      // look for cover image
      $files = glob($baseFolder . '/' . $this->config['albumCoverDetector'] . '*.*');
      if (count($files) > 0) {
        $i = basename($files[0]);
        if (preg_match("/\.(" . $this->config['fileExtensions'] . ")*$/i", $i)) {
          $this->GetThumbnail2( $baseFolder, $i);
          return $baseFolder . $i;
        }
      }

      // no cover image found --> use the first image for the cover
      $i = $this->GetFirstImageFolder($baseFolder);
      if ($i != '') {
        $this->GetThumbnail2( $baseFolder, $i);
        return $baseFolder . $i;
      }

      return '';
    }

    /**
     * Retrieve the first image of one folder --> ALBUM THUMBNAIL
     * 
     * @param string $folder
     * @return string
     */
    protected function GetFirstImageFolder($folder)
    {
      $image = '';

      $dh       = opendir($folder);
      while (false !== ($filename = readdir($dh))) {
        if (is_file($folder . '/' . $filename) && preg_match("/\.(" . $this->config['fileExtensions'] . ")*$/i", $filename)) {
          $image = $filename;
          break;
        }
      }
      closedir($dh);

      return $image;
    }



     /**
     * FOR FILES
     * @param object $a
     * @param object $b
     * @return int
     */      
    protected function CompareFile($a, $b)
    {
      $order_array = explode("_", $this->config['sortOrder']);
      if (count($order_array) > 1) {
        switch ($order_array[1]){
          case 'PREFIX':
            $al = strtolower($a->sort);
            $bl = strtolower($b->sort);
            break;
          case 'CTIME':
            $al = strtolower($a->ctime);
            $bl = strtolower($b->ctime);
            break;
          case 'MTIME':
          default:
            $al = strtolower($a->mtime);
            $bl = strtolower($b->mtime);
            break;
        }
      }
      else {
        $al = strtolower($a->title);
        $bl = strtolower($b->title);
      }
      if ($al == $bl) {
          return 0;
      }
      $b = false;
      switch ($order_array[0]) {
        case 'DESC' :
          if ($al < $bl) {
            $b = true;
          }
          break;
        case 'ASC':
        default:
          if ($al > $bl) {
            $b = true;
          }
          break;
      }
      return ($b) ? +1 : -1;
    }
    
    /**
     * FOR ALBUMS
     * @param object $a
     * @param object $b
     * @return int
     */
    protected function CompareAlbum($a, $b)
    {
      $order_array = explode("_", $this->config['sortOrderAlbum']);
      if (count($order_array) > 1) {
        switch ($order_array[1]){
          case 'PREFIX':
            $al = strtolower($a->sort);
            $bl = strtolower($b->sort);
            break;
          case 'CTIME':
            $al = strtolower($a->ctime);
            $bl = strtolower($b->ctime);
            break;
          case 'MTIME':
          default:
            $al = strtolower($a->mtime);
            $bl = strtolower($b->mtime);
            break;
        }
      }
      else {
        $al = strtolower($a->title);
        $bl = strtolower($b->title);
      }

      if ($al == $bl) {
          return 0;
      }
      $b = false;
      switch ($order_array[0]) {
        case 'DESC' :
          if ($al < $bl) {
            $b = true;
          }
          break;
        case 'ASC':
        default:
          if ($al > $bl) {
            $b = true;
          }
          break;
      }
      return ($b) ? +1 : -1;
    }

    // Fix image orientation
    function image_fix_orientation(&$image, &$size, $filename) {

      $exif = exif_read_data($filename);
      if ($exif !== false && !empty($exif['Orientation'])) {
        switch ($exif['Orientation']) {
          case 3:
            $image = imagerotate($image, 180, 0);
            break;
          case 6:
            $image = imagerotate($image, -90, 0);
            list($size[0],$size[1]) = array($size[1],$size[0]);
            break;
          case 8:
            $image = imagerotate($image, 90, 0);
            list($size[0],$size[1]) = array($size[1],$size[0]);
            break;
        }
      }
    }


    /**
     * RETRIEVE ONE IMAGE'S DISPLAY URL
     * 
     * @param type $baseFolder
     * @param type $filename
     */
    protected function GetImageDisplayURL( $baseFolder, $filename )
    {
    
      if( $this->config['images']['maxSize'] < 100 ) {
        return '';
      }

      if (!file_exists( $baseFolder . '_thumbnails' )) {
        mkdir( $baseFolder . '_thumbnails', 0755, true );
      }

      
      $lowresFilename = $baseFolder . '_thumbnails/' . $filename;
      
      if (file_exists($lowresFilename)) {
        if( filemtime($lowresFilename) > filemtime($baseFolder . $filename) ) {
          // original image file is older as the image use for display
          $size = getimagesize($lowresFilename);
          $this->currentItem->imgWidth  = $size[0];
          $this->currentItem->imgHeight = $size[1];
          return rawurlencode($this->CustomEncode($lowresFilename));
        }
      }

      $size = getimagesize($baseFolder . $filename);

      switch ($size['mime']) {
        case 'image/jpeg':
          $orgImage = imagecreatefromjpeg($baseFolder . $filename);
          break;
        case 'image/gif':
          $orgImage = imagecreatefromgif($baseFolder . $filename);
          break;
        case 'image/png':
          $orgImage = imagecreatefrompng($baseFolder . $filename);
          break;
        default:
          return false;
          break;
      }

      $this->image_fix_orientation($orgImage, $size, $baseFolder . $filename);

      $width  = $size[0];
      $height = $size[1];

      if( $width <= $this->config['images']['maxSize'] && $height <= $this->config['images']['maxSize'] ) {
        // original image is smaller than max size -> return original file
        $this->currentItem->imgWidth  = $width;
        $this->currentItem->imgHeight = $height;
        return rawurlencode($this->CustomEncode($baseFolder . $filename));
      }
      
      $newWidth = $width;
      $newHeight = $height;
      if( $width > $height ) {
        if( $width > $this->config['images']['maxSize'] ) {
          $newWidth = $this->config['images']['maxSize'];
          $newHeight = $this->config['images']['maxSize'] / $width * $height;
        }
      }
      else {
        if( $height > $this->config['images']['maxSize'] ) {
          $newHeight = $this->config['images']['maxSize'];
          $newWidth = $this->config['images']['maxSize'] / $height * $width;
        }
      }
      
      $display_image = imagecreatetruecolor($newWidth, $newHeight);

      // Resize
      imagecopyresampled($display_image, $orgImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

      // save to disk
      switch ($size['mime']) {
        case 'image/jpeg':
          imagejpeg($display_image, $lowresFilename, $this->config['images']['jpegQuality'] );
          break;
        case 'image/gif':
          imagegif($display_image, $lowresFilename);
          break;
        case 'image/png':
          imagepng($display_image, $lowresFilename, 1);
          break;
      }

      $this->currentItem->imgWidth  = $newWidth;
      $this->currentItem->imgHeight = $newHeight;
      return rawurlencode($this->CustomEncode($lowresFilename));

    }

    
    /**
     * RETRIEVE ONE IMAGE'S THUMBNAILS
     * 
     * @param type $baseFolder
     * @param type $filename
     * @return type
     */
    protected function GetThumbnail2( $baseFolder, $filename )
    {

      $s  = array( 'xs',   'sm',   'me',   'la',   'xl'  );
      $sw = array( 'wxs',  'wsm',  'wme',  'wla',  'wxl' );
      $sh = array( 'hxs',  'hsm',  'hme',  'hla',  'hxl' );
      for( $i = 0; $i < count($s) ; $i++ ) {

        $pi=pathinfo($filename);
        $tn= $pi['filename'] . '_' . $this->tn_size[$sw[$i]] . '_' . $this->tn_size[$sh[$i]] . '.' . $pi['extension'];
        if ( $this->GenerateThumbnail2($baseFolder, $filename, $tn, $this->tn_size[$sw[$i]], $this->tn_size[$sh[$i]], $i ) == true ) {
          $this->currentItem->t_url[$i]= $this->CustomEncode($baseFolder . '_thumbnails/' . $tn);
        }
        else {
          // fallback: original image (no thumbnail)
          $this->currentItem->t_url[$i]= $this->CustomEncode($baseFolder . $filename);
        }
      }
    }
    
    /**
     * GENERATE A SMALL BASE64 GIF WITH ONE IMAGE'S DOMINANT COLORS
     * 
     * @param type $baseFolder
     * @param type $filename
     * @return gif
     */
    protected function GetDominantColorsGIF( $img )
    {
      $size = getimagesize($img);
      switch ($size['mime']) {
        case 'image/jpeg':
          $orgImage = imagecreatefromjpeg($img);
          break;
        case 'image/gif':
          $orgImage = imagecreatefromgif($img);
          break;
        case 'image/png':
          $orgImage = imagecreatefrompng($img);
          break;
        default:
          return '';
          break;
      }
      
      $this->image_fix_orientation($orgImage, $size, $img);
      
      $width  = $size[0];
      $height = $size[1];
      $thumb = imagecreate(3, 3);

      imagecopyresampled($thumb, $orgImage, 0, 0, 0, 0, 3, 3, $width, $height);

      ob_start(); 
      imagegif( $thumb );
      $image_data = ob_get_contents(); 
      ob_end_clean();         
     
      return base64_encode( $image_data );
    }

    /**
     * RETRIVE ONE IMAGE'S DOMINANT COLOR
     * 
     * @param type $baseFolder
     * @param type $filename
     * @return gif
     */
    protected function GetDominantColor( $img )
    {
      $size = getimagesize($img);
      switch ($size['mime']) {
        case 'image/jpeg':
          $orgImage = imagecreatefromjpeg($img);
          break;
        case 'image/gif':
          $orgImage = imagecreatefromgif($img);
          break;
        case 'image/png':
          $orgImage = imagecreatefrompng($img);
          break;
        default:
          return '#000000';
          break;
      }
      $this->image_fix_orientation($orgImage, $size, $img);
      $width  = $size[0];
      $height = $size[1];
      
      $pixel = imagecreatetruecolor(1, 1);

      imagecopyresampled($pixel, $orgImage, 0, 0, 0, 0, 1, 1, $width, $height);

      $rgb = imagecolorat($pixel, 0, 0);
      $color = imagecolorsforindex($pixel, $rgb);
      $hex=sprintf('#%02x%02x%02x', $color[red], $color[green], $color[blue]);
      
      return $hex;
    }

    /**
     * GENERATE ONE THUMBNAIL
     * 
     * @param type $baseFolder
     * @param type $imagefilename
     * @param type $thumbnailFilename
     * @param type $thumbWidth
     * @param type $thumbHeight
     * @param type $s (reponsive size)
     * @return string
     */
    protected function GenerateThumbnail2($baseFolder, $imagefilename, $thumbnailFilename, $thumbWidth, $thumbHeight, $s)
    {
      if (!file_exists( $baseFolder . '_thumbnails' )) {
        mkdir( $baseFolder . '_thumbnails', 0755, true );
      }
        
      $generateThumbnail = true;
      if (file_exists($baseFolder . '_thumbnails/' . $thumbnailFilename)) {
        if( filemtime($baseFolder . '_thumbnails/' . $thumbnailFilename) > filemtime($baseFolder.$imagefilename) ) {
          // image file is older as the thumbnail file
          $generateThumbnail=false;
        }
      }
      
      $generateDominantColors = true;
      if( $s != 0 ) {
        $generateDominantColors=false;
      }
      else {
        $generateDominantColors= ! $this->GetDominantColors($baseFolder . $imagefilename, $baseFolder . '_thumbnails/' . $thumbnailFilename . '.data');
      }
     
      $size = getimagesize($baseFolder . $imagefilename);
      
      if( $generateThumbnail == true || $generateDominantColors == true ) {
        switch ($size['mime']) {
          case 'image/jpeg':
            $orgImage = imagecreatefromjpeg($baseFolder . $imagefilename);
            break;
          case 'image/gif':
            $orgImage = imagecreatefromgif($baseFolder . $imagefilename);
            break;
          case 'image/png':
            $orgImage = imagecreatefrompng($baseFolder . $imagefilename);
            break;
          default:
            return false;
            break;
        }
      }
      
      $this->image_fix_orientation($orgImage, $size, $baseFolder . $imagefilename);
      
      $width  = $size[0];
      $height = $size[1];

      $originalAspect = $width / $height;
      // $thumbAspect    = $thumbWidth / $thumbHeight;

      if ( $thumbWidth != 'auto' && $thumbHeight != 'auto' ) {
        // IMAGE CROP
        // some inspiration found in donkeyGallery (from Gix075) https://github.com/Gix075/donkeyGallery 
        $thumbAspect    = $thumbWidth / $thumbHeight;
        if ($originalAspect >= $thumbAspect) {
          // If image is wider than thumbnail (in aspect ratio sense)
          $newHeight = $thumbHeight;
          $newWidth  = $width / ($height / $thumbHeight);
        } else {
          // If the thumbnail is wider than the image
          $newWidth  = $thumbWidth;
          $newHeight = $height / ($width / $thumbWidth);
        }

        // thumbnail image size
        // $this->currentItem->t_width[$s]=$newWidth;
        // $this->currentItem->t_height[$s]=$newHeight;
        $this->currentItem->t_width[$s]=$thumbWidth;
        $this->currentItem->t_height[$s]=$thumbHeight;

        if( $generateThumbnail == true ) {
          $thumb = imagecreatetruecolor($thumbWidth, $thumbHeight);
          // Resize and crop
          imagecopyresampled($thumb, $orgImage,
                0 - ($newWidth - $thumbWidth) / 2,    // dest_x: Center the image horizontally
                0 - ($newHeight - $thumbHeight) / 2,  // dest-y: Center the image vertically
                0, 0, // src_x, src_y
                $newWidth, $newHeight, $width, $height);
        }
          
      } else {
        // NO IMAGE CROP
        if( $thumbWidth == 'auto' ) {
          $newWidth  = $width / $height * $thumbHeight;
          $newHeight = $thumbHeight;
        }
        else {
          $newHeight = $height / $width * $thumbWidth;
          $newWidth  = $thumbWidth;
        }
        
        // thumbnail image size
        $this->currentItem->t_width[$s]=$newWidth;
        $this->currentItem->t_height[$s]=$newHeight;
        
        if( $generateThumbnail == true ) {
          $thumb = imagecreatetruecolor($newWidth, $newHeight);

          // Resize
          imagecopyresampled($thumb, $orgImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        }
      }

      if( $generateThumbnail == true ) {
        switch ($size['mime']) {
          case 'image/jpeg':
            imagejpeg($thumb, $baseFolder . '/_thumbnails/' . $thumbnailFilename, $this->config['thumbnails']['jpegQuality'] );
            break;
          case 'image/gif':
            imagegif($thumb, $baseFolder . '/_thumbnails/' . $thumbnailFilename);
            break;
          case 'image/png':
            imagepng($thumb, $baseFolder . '/_thumbnails/' . $thumbnailFilename, 1);
            break;
        }
      }
      
      if( $generateDominantColors == true ) {
        // Dominant colorS -> GIF
        $dc3 = imagecreate($this->config['thumbnails']['blurredImageQuality'], $this->config['thumbnails']['blurredImageQuality']);
        imagecopyresampled($dc3, $orgImage, 0, 0, 0, 0, 3, 3, $width, $height);
        ob_start(); 
        imagegif( $dc3 );
        $image_data = ob_get_contents(); 
        ob_end_clean();         
        $this->currentItem->dcGIF= base64_encode( $image_data );
        
        // Dominant color -> HEX RGB
        $pixel = imagecreatetruecolor(1, 1);
        imagecopyresampled($pixel, $orgImage, 0, 0, 0, 0, 1, 1, $width, $height);
        $rgb = imagecolorat($pixel, 0, 0);
        $color = imagecolorsforindex($pixel, $rgb);
        $hex=sprintf('#%02x%02x%02x', $color['red'], $color['green'], $color['blue']);
        $this->currentItem->dc= $hex;

        // save to cache
        $fdc = fopen($baseFolder . '_thumbnails/' . $thumbnailFilename . '.data', 'w');
        if( $fdc ) { 
          fwrite($fdc, 'dc=' . $hex . "\n");
          fwrite($fdc, 'dcGIF=' . base64_encode( $image_data ));
          fclose($fdc);
        }
        else {
          // exit without dominant color
          return false;
        }
      }

      return true;
    }

    
    protected function GetDominantColors($fileImage, $fileDominantColors)
    {
    
      if (file_exists($fileDominantColors)) {
        if( filemtime($fileDominantColors) < filemtime($fileImage) ) {
          // image file is older as the dominant colors file
          return false;
        }

        // read cached data
        $cnt=0;
        $myfile = fopen($fileDominantColors, "r");
        if( $myfile ) { 
          while(!feof($myfile)) {
            $l=fgets($myfile);
            $s=explode('=', $l);
            if( is_array($s) ) {
              $property=trim($s[0]);
              $value=trim($s[1]);
              if( $property != '' &&  $value != '' ) {
                $this->currentItem->$property=$value;
                $cnt++;
              }
            }
          }
          fclose($myfile);
        }
        
        if( $cnt == 2 ) {
          // ok, 2 values found
          return true;
        }
      }
      
      return false;
      
    }

    /**
     * Extract title and description from filename
     * 
     * @param string $filename
     * @param boolean $isImage
     * @return \item
     */
    protected function GetMetaData($filename, $isImage)
    {
      $oneItem = new item();

      // the filename is the ID
      $oneItem->ID = $filename;

      $f = $filename;
  
      if ($isImage) {
        // remove file extension
        $filename = $this->file_ext_strip($filename);
      }


      //$oneItem->title = str_replace($this->config['albumCoverDetector'], '', $oneItem->title);   // filter cover detector string
      $filename2 = str_replace($this->config['albumCoverDetector'], '', $filename);   // filter cover detector string

      
      if (strpos($filename2, $this->config['titleDescSeparator']) > 0) {
        // title and description
        $s              = explode($this->config['titleDescSeparator'], $filename2);
        $oneItem->title = $this->CustomEncode($s[0]);
        $oneItem->description = $this->CustomEncode($s[1]);
      } else {
        // only title
        $oneItem->title = $this->CustomEncode($filename2);
        $oneItem->description = '';
      }


      # Sort Using a Prefix from sortPrefixSeparator if present
      if (strpos($oneItem->title, $this->config['sortPrefixSeparator']) > 0) {
        // split sort from title
        $s              = explode($this->config['sortPrefixSeparator'], $oneItem->title);
        $oneItem->sort  = $this->CustomEncode($s[0]);
        $oneItem->title = $this->CustomEncode($s[1]);
      }
      else{
        # Set sort to title otherwise to allow blended sort prefix and not
        $oneItem->sort  = $oneItem->title;
      }
      
      

      //$oneItem->title = str_replace($this->config['albumCoverDetector'], '', $oneItem->title);   // filter cover detector string
        
      // the title (=filename) is the ID
      // $oneItem->ID = $oneItem->title;
        
      // read meta data from external file (images and albums)
      // if ($isImage) {
        if( file_exists( $this->data->fullDir . '/' . $filename . '.txt' ) ) {
          $myfile = fopen($this->data->fullDir . '/' . $filename . '.txt', "r") or die("Unable to open file!");
          while(!feof($myfile)) {
            $l=fgets($myfile);
            $s=explode('=', $l);
            if( is_array($s) ) {
              $property=trim($s[0]);
              $value=trim($s[1]);
              if( $property != '' &&  $value != '' ) {
                $oneItem->$property=$value;
              }
            }
          }
          fclose($myfile);
        }
        
      //}
      return $oneItem;
    }

    /**
     * Returns only the file extension (without the period).
     * 
     * @param string $filename
     * @return string
     */
    protected function file_ext($filename)
    {
      if (!preg_match('/./', $filename)) {
        return '';
      }
      return preg_replace('/^.*./', '', $filename);
    }

    /**
     * Returns the file name, less the extension.
     * 
     * @param string $filename
     * @return string
     */
    protected function file_ext_strip($filename)
    {
      return preg_replace('/.[^.]*$/', '', $filename);
    }

    
    
    /**
     * 
     * @param string $s
     * @return string
     */
    protected function CustomEncode($s)
    {
      return \ForceUTF8\Encoding::toUTF8(($s));
      //return \ForceUTF8\Encoding::fixUTF8(($s));
    }

    /**
     * 
     * @param type $s
     * @return type
     */
    protected function CustomDecode($s)
    {
      return utf8_decode($s);
      // return $s;
    }


    /**
     * Returns the number of items in one disk folder.
     * 
     * @param type $d
     * @return integer
     */
    protected function AlbumCountItems( $d )
    {
      $cnt = 0;
      $dh = opendir($d);
      
      // loop the folder to retrieve images and albums
      if ($dh != false) {
        while (false !== ($filename = readdir($dh))) {
          
          if (is_file($this->data->fullDir . $filename) ) {
            // it's a file
            if ($filename != '.' &&
                    $filename != '..' &&
                    $filename != '_thumbnails' &&
                    preg_match("/\.(" . $this->config['fileExtensions'] . ")*$/i", $filename) &&
                    strpos($filename, $this->config['ignoreDetector']) == false &&
                    strpos($filename, $this->config['albumCoverDetector']) == false )
            {
              $cnt++;
            }
          }
          else {
            // it's a folder
            if ($filename != '.' &&
                    $filename != '..' &&
                    $filename != '_thumbnails' &&
                    preg_match("/\.(" . $this->config['fileExtensions'] . ")*$/i", $filename) &&
                    strpos($filename, $this->config['ignoreDetector']) == false && 
                    !empty($filename) )
            {
              $cnt++;
            }
          }
        }
      }
      else {
        closedir($dh);
      }
      
      return $cnt;

   }

    
    
    protected function PrepareData($filename, $kind)
    {
      // $oneItem = new item();
      $this->currentItem = new item();
      // if (is_file($this->data->fullDir . $filename) && preg_match("/\.(" . $this->config['fileExtensions'] . ")*$/i", $filename)) {
      if ( $kind == 'IMAGE' ) {
        // ONE IMAGE
        $this->currentItem->kind            = 'image';
        $e = $this->GetMetaData($filename, true);
        $this->currentItem->title           = $e->title;
        $this->currentItem->description     = $e->description;
        $this->currentItem->tags            = $e->tags;
        $this->currentItem->sort            = $e->sort;
        // $this->currentItem->src             = rawurlencode($this->CustomEncode($this->config['contentFolder'] . $this->album . '/' . $filename));
        $this->currentItem->originalURL     = rawurlencode($this->CustomEncode($this->config['contentFolder'] . $this->album . '/' . $filename));
        $this->currentItem->src             = $this->GetImageDisplayURL($this->data->fullDir, $filename);
        $this->currentItem->ctime           = filectime($this->data->fullDir . $filename);
        $this->currentItem->mtime           = filemtime($this->data->fullDir . $filename);

        if( $this->currentItem->src == '' ) {
          $this->currentItem->src = $this->currentItem->originalURL;
          $imgSize = getimagesize($this->data->fullDir . '/' . $filename);
          $this->currentItem->imgWidth        = $imgSize[0];
          $this->currentItem->imgHeight       = $imgSize[1];
        }

        $this->GetThumbnail2($this->data->fullDir, $filename);
        $this->currentItem->albumID         = rawurlencode($this->albumID);
        if ($this->albumID == '0' || $this->albumID == '') {
            $this->currentItem->ID          = rawurlencode($this->CustomEncode($e->ID));
        } else {
            $this->currentItem->ID          = rawurlencode($this->albumID . $this->CustomEncode('/' . $e->ID));
        }
        return $this->currentItem;
      }
      else {
        // ONE ALBUM
        $this->currentItem->kind            = 'album';

        $e = $this->GetMetaData($filename, false);
        $this->currentItem->title           = $e->title;
        $this->currentItem->description     = $e->description;
        $this->currentItem->sort            = $e->sort;
        $this->currentItem->ctime           = filectime($this->data->fullDir . $filename);
        $this->currentItem->mtime           = filemtime($this->data->fullDir . $filename);

        $this->currentItem->albumID         = rawurlencode($this->albumID);
        if ($this->albumID == '0' || $this->albumID == '') {
          $this->currentItem->ID            = rawurlencode($this->CustomEncode($filename));
        } else {
          $this->currentItem->ID            = rawurlencode($this->albumID . $this->CustomEncode('/' . $filename));
        }
        $ac=$this->GetAlbumCover($this->data->fullDir . $filename . '/');
        if ( $ac != '' ) {
          // $path = '';
          // if ($this->albumID == '0') {
            // $path = $filename;
          // } else {
            // $path = $this->album . '/' . $filename;
          // }
          $this->currentItem->cnt           = $this->AlbumCountItems( $this->data->fullDir . $filename . '/');
          return $this->currentItem;
        }
      }
    }

}
?>