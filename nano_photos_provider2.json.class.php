<?php
/**
 * nanoPhotosProvider2 add-on for nanogallery2
 *
 * This is an add-on for nanogallery2 (image gallery - http://nanogallery2.nanostudio.org).
 * This PHP application will publish your images and albums from a PHP webserver to nanogallery2.
 * The content is provided on demand, one album at one time.
 * Responsive thumbnails are generated automatically.
 * Dominant colors are extracted as a base64 GIF.
 * 
 * License: For personal, non-profit organizations, or open source projects (without any kind of fee), you may use nanogallery2 for free. 
 * -------- ALL OTHER USES REQUIRE THE PURCHASE OF A COMMERCIAL LICENSE.
 *
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
    public $src         = '';
    public $title       = '';
    public $description = '';
    public $ID          = '';
    public $albumID     = '0';
    public $kind        = '';       // 'album', 'image'
    public $t_url       = array();       // thumbnails URL
    public $t_width     = array();       // thumbnails width
    public $t_height    = array();       // thumbnails height
    public $dc          = '#000';   // image dominant color
    // public $dcGIF       = '#000';   // image dominant color


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

      // thumbnail responsive sizes
      $this->tn_size[wxs]   = $_GET['wxs'];
      $this->tn_size[hxs]   = $_GET['hxs'];
      $this->tn_size[wsm]   = $_GET['wsm'];
      $this->tn_size[hsm]   = $_GET['hsm'];
      $this->tn_size[wme]   = $_GET['wme'];
      $this->tn_size[hme]   = $_GET['hme'];
      $this->tn_size[wla]   = $_GET['wla'];
      $this->tn_size[hla]   = $_GET['hla'];
      $this->tn_size[wxl]   = $_GET['wxl'];
      $this->tn_size[hxl]   = $_GET['hxl'];
      
      
      $this->data           = new galleryData();
      $this->setConfig(self::CONFIG_FILE);
      $this->data->fullDir  = ($this->config['contentFolder']) . ($this->album);

      $lstImages = array();
      $lstAlbums = array();
      
      $dh = opendir($this->data->fullDir);

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
            $files = glob($this->data->fullDir . $filename."/*.{".str_replace("|",",",$this->config['fileExtensions'])."}", GLOB_BRACE);    // to check if folder contains images
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

      // sort data
      usort($lstAlbums, array('galleryJSON','Compare'));
      usort($lstImages, array('galleryJSON','Compare'));

      $response = array('nano_status' => 'ok', 'nano_message' => '', 'album_content' => array_merge($lstAlbums, $lstImages));

      // return the data
      header('Content-Type: application/json; charset=utf-8');
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
      $this->config['titleDescSeparator']     = $config['config']['titleDescSeparator'];
      $this->config['albumCoverDetector']     = $config['config']['albumCoverDetector'];
      $this->config['ignoreDetector']         = strtoupper($config['config']['ignoreDetector']);
      
      // thumbnails
      $this->config['thumbnail']['JpegQuality']         = $config['thumbnail']['JpegQuality'];
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
     * 
     * @param object $a
     * @param object $b
     * @return int
     */
    protected function Compare($a, $b)
    {
      $al = strtolower($a->title);
      $bl = strtolower($b->title);
      if ($al == $bl) {
          return 0;
      }
      $b = false;
      switch ($this->config['sortOrder']) {
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
        mkdir( $baseFolder . '_thumbnails', 0777, true );
      }
        
      $generateThumbnail = true;
      if (file_exists($baseFolder . '_thumbnails/' . $thumbnailFilename)) {
        if( filemtime($baseFolder . '_thumbnails/' . $thumbnailFilename) > filemtime($baseFolder.$imagefilename) ) {
          // image file is older as the thumbnail file
          $generateThumbnail=false;
        }
      }
      
      $size = getimagesize($baseFolder . $imagefilename);
      
      if( $generateThumbnail == true || $s == 0 ) {
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
        
      $width  = $size[0];
      $height = $size[1];

      $originalAspect = $width / $height;
      $thumbAspect    = $thumbWidth / $thumbHeight;

      if ( $thumbWidth != 'auto' && $thumbHeight != 'auto' ) {
        // IMAGE CROP
        // some inspiration found in donkeyGallery (from Gix075) https://github.com/Gix075/donkeyGallery 
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
        $this->currentItem->t_width[$s]=$newWidth;
        $this->currentItem->t_height[$s]=$newHeight;

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
            $q=$this->config['thumbnail']['JpegQuality'];
            if( ! ctype_digit(strval($q)) ){
              $q=90;    // default jpeg quality
            }
            imagejpeg($thumb, $baseFolder . '/_thumbnails/' . $thumbnailFilename, $q);
            break;
          case 'image/gif':
            imagegif($thumb, $baseFolder . '/_thumbnails/' . $thumbnailFilename);
            break;
          case 'image/png':
            imagepng($thumb, $baseFolder . '/_thumbnails/' . $thumbnailFilename, 1);
            break;
        }
      }
      
      if( $s == 0 ) {
        // Dominant colorS -> GIF
        $dc3 = imagecreate(3, 3);
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
        $hex=sprintf('#%02x%02x%02x', $color[red], $color[green], $color[blue]);
        $this->currentItem->dc= $hex;
        
      }

      return true;
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
      $f=$filename;
  
      if ($isImage) {
        $filename = $this->file_ext_strip($filename);
      }

      $oneItem = new item();
      if (strpos($filename, $this->config['titleDescSeparator']) > 0) {
        // title and description
        $s              = explode($this->config['titleDescSeparator'], $filename);
        $oneItem->title = $this->CustomEncode($s[0]);
        if ($isImage) {
          $oneItem->description = $this->CustomEncode(preg_replace('/.[^.]*$/', '', $s[1]));
        } else {
          $oneItem->description = $this->CustomEncode($s[1]);
        }
      } else {
        // only title
        if ($isImage) {
          $oneItem->title = $this->CustomEncode($filename);  //(preg_replace('/.[^.]*$/', '', $filename));
        } else {
          $oneItem->title = $this->CustomEncode($filename);
        }
        $oneItem->description = '';
      }

      $oneItem->title = str_replace($this->config['albumCoverDetector'], '', $oneItem->title);   // filter cover detector string
        
      // the title (=filename) is the ID
      $oneItem->ID= $oneItem->title;
        
        // read meta data from external file
      if ($isImage) {
        if( file_exists( $this->data->fullDir . '/' . $filename . '.txt' ) ) {
          $myfile = fopen($this->data->fullDir . '/' . $filename . '.txt', "r") or die("Unable to open file!");
          while(!feof($myfile)) {
            $l=fgets($myfile);
            $s=explode(':', $l);
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
        
      }
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
        
        $e                        = $this->GetMetaData($filename, true);
        $this->currentItem->title           = $e->title;
        $this->currentItem->description     = $e->description;
        $this->currentItem->src             = rawurlencode($this->CustomEncode($this->config['contentFolder'] . $this->album . '/' . $filename));

        $imgSize                  = getimagesize($this->data->fullDir . '/' . $filename);
        $this->currentItem->imgWidth        = $imgSize[0];
        $this->currentItem->imgHeight       = $imgSize[1];
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

        $e                        = $this->GetMetaData($filename, false);
        $this->currentItem->title           = $e->title;
        $this->currentItem->description     = $e->description;

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