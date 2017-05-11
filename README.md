# nanoPhotosProvider2 [beta]
### :white_circle: add-on for nanogallery2
    
  
Publish your self-hosted photos simply and automatically to nanogallery2.  
Content is provided on demand when browsing albums in the gallery.  
Main features:
- generates responsive thumbnails automatically  
- compatible with all layouts: grid, justified and cascading
- extraction of dominant colors (single color and gradient)  
- supports photo albums  


To be used as an add-on for nanogallery2 (http://nanogallery2.nanostudio.org).

### :white_circle: Usage

##### :one: Step 1: installation

On your webserver:
- create a folder named `nano_photos_provider2` where you want to store your photos
- in this folder:
  - copy the files:
    - `nano_photos_provider2.php`,
    - `nano_photos_provider2.class.php`,
    - `nano_photos_provider2.cfg` and
    - `nano_photos_provider2.Encoding.php`
  - create a folder named `nano_photos_content`  
    - copy your photos here  
    - you can organize your photos in folders (= albums)  
  - edit the `nano_photos_provider2.cfg` file for custom settings  

<br />  
  
##### :two: Step 2: configure your HTML page

- The page can be located anywhere on your webserver.
- Install and configure nanogallery2 (see http://nanogallery2.nanostudio.org)
- Configure the call to the plugin:
  - Use the specific parameters: `kind` and `dataProvider`
    - `kind`: set value to `'nano_photos_provider2'`
    - `dataProvider`: URL to the `nano_photos_provider2.php` file installed in step 1

Example:

```js
    jQuery(document).ready(function () {
      jQuery("#nanoGallery1").nanogallery2({
        thumbnailWidth:   'auto',
        thumbnailHeight:  150,
        kind:             'nano_photos_provider2',
        dataProvider:     'http://mywebsever.com/mypath/nano_photos_provider2/nano_photos_provider2.php',
        locationHash:     false
      });
    });
```
<br />
<br />
  
##### :three: Step 3: test your page to see the result ;-)

<br />

  ##### :four: Step 4: add/change content
Add files and folders, or renaname them.
Please note that the generated thumbnails are never purged, so you may delete the `_thumbnails` folders to force a new generation.
  
  
  
### :white_circle: Title, description and ID

There are 2 ways to define the thumbnails title and description  
- in the filename or foldername  
The foldername or filename (without extension) are used as title.  
A description can be added by using the `$$` separator.  
  
- in an external file  
With the same name as the image, with the extension '.txt'  
Format:  
```
title: this is my title
description: this is my descritption
```
  
### :white_circle: Album covers  
By default, the first image found in a folder will be used for the album cover image.  
The cover image can be specified by adding a leading `@@@@@` to the filename of the image to be used  

Note that the filenames and foldernames are used as IDs. If you rename them, URLs pointing to them will no longer work.
  
### :white_circle: Custom configuration
Custom settings are defined in `nano_photos_provider2.cfg`

Section | Option | default value | Description
------------ | ------------- | ------------ | -------------
config  | | |   
.  | fileExtensions | "jpg\|jpeg\|png\|gif" | Supported file extensions
.  | contentFolder | "nano_photos_content" | Folder where albums and images are stored
.  | sortOrder | "asc" | Filename sort order (asc or desc)
.  | titleDescSeparator | "$$" | Separator between title and description in the filename or foldername
.  | albumCoverDetector | "@@@@@" | Leading sequence in the filename of the image to be used as an album cover  
.  | ignoreDetector | "_hidden" | Ignore photos/albums (folders) containing this sequence in their name
thumbnail | | |   
.  | JpegQuality | 85 | JPEG quality for the thumbnails


<br />
### :white_circle: Supported image formats
JPEG, GIF and PNG.

<br />

### :warning: Perfomances
- Thumbnails are generated on first request and then cached on disk.
- On first use, or after adding a large amount of new images, you may en encounter timeouts -> reload the page until you don't get any error. Generated data will then be cached.


### :warning: SECURITY
Generation of thumbnails could be missused for a DoS (Denial-of-service) attack.  
It's highly recommanded to limit the use of nanoPhotosProvider2 to specific webservers.  
This can, for example, be achivied with a `.htaccess` file:  
```
<Files "admin.php">
  Order deny,allow
  Deny from all
  Allow from .*domain1\.com.*
  Allow from .*domain2\.com.*
</Files>
```  

### :warning: Limitations
- The nanogallery2 option `locationHash` should NOT be enabled if albums have more than 2 levels.  
  

### :copyright: License
nanoPhotosProvider2 is licensed under [CC BY-NC 3.0](http://creativecommons.org/licenses/by-nc/3.0/).  
Only for personal, non-profit organizations, or open source projects (without any kind of fee), you may use nanoPhotosProvider2 for free.


### :white_circle: Requirements
* nanogallery2 >= v1.3 (http://nanogallery2.nanostudio.org)
* Webserver
* PHP >= v5.2 with GD-Library