# nanoPhotosProvider2 - v1.0.0
### :white_circle: add-on for nanogallery2
    
  
Publish your self-hosted photos simply and automatically to nanogallery2.  
Content is provided on demand when browsing albums in the gallery.  
Main features:
- generates responsive thumbnails automatically  
- compatible with all layouts: grid, justified and cascading
- extraction of dominant colors (single color and gradient/blurred image)  
- supports photo albums  
- easy to install and maintain - only flat files / no database

To be used as an add-on for nanogallery2 (http://nanogallery2.nanostudio.org).

### :white_circle: Usage

##### :one: Step 1: installation

On your webserver:
- create a folder named `nano_photos_provider2` where you want to store your photos
- in this folder:
  - copy the files:
    - `nano_photos_provider2.php`,
    - `nano_photos_provider2.json.class.php`,
    - `nano_photos_provider2.cfg` and
    - `nano_photos_provider2.encoding.php`
  - create a folder named `nano_photos_content`  
    - copy your photos there  
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
      jQuery("#my_nanogallery").nanogallery2({
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
Add files and folders, or rename them.  
Please note that the generated thumbnails are never purged, so you may delete the `_thumbnails` folders to force a new generation.
  
  
### :white_circle: IDs
Filenames and folder names are used as IDs. So if you rename an image or folder, previous used links to the renamed element will no longer work.

  
### :white_circle: Title, description, tags

There are 2 ways to define the thumbnails title and description  
- in the filename or foldername  
The foldername or filename (without extension) are used as title.  
A description can be added by using the `$$` separator.  
`_` will replaced by `space`
  
- in an external file  
With the same name as the image, with the extension '.txt' 
Format:  
```
  title= this is my title
  description= this is my descritption
  tags=tag1 tag2 tag3
```
  
- Tags are only supported in external files
    
  
### :white_circle: Album covers  
By default, the first image found in a folder will be used for the album cover image.  
The cover image can be specified by adding a leading `@@@@@` to the filename of the image to be used  

Note that the filenames and foldernames are used as IDs. If you rename them, URLs pointing to them will no longer work.
  
### :white_circle: Custom configuration
Custom settings are defined in `nano_photos_provider2.cfg`

Section | Option | default value | Description
------------ | ------------- | ------------ | -------------
config  | | |   
.  | fileExtensions | "jpg\|jpeg\|png\|gif" | Supported file extensions (separtor is \|)
.  | contentFolder | "nano_photos_content" | Folder where albums and images are stored
.  | sortOrder | "asc" | Filename sort order (asc or desc)
.  | titleDescSeparator | "$$" | Separator between title and description in the filename or foldername
.  | albumCoverDetector | "@@@@@" | Leading sequence in the filename of the image to be used as an album cover  
.  | ignoreDetector | "_hidden" | Ignore photos/albums (folders) containing this sequence in their name
images | | |   
.  | maxSize* | 1900 | max. width/height of the displayed images
.  | jpegQuality* | 85 | JPEG quality of the images
thumbnails | | |   
.  | jpegQuality* | 85 | JPEG quality for the thumbnails
.  | blurredImageQuality* | 3 | quality of the blurred images (higher is better but slower)
.  | allowedSizeValues | "" | list of allowed values for thumbnail image sizes  (separtor is \|)
.  | | | Values should be the same as in your nanogallery2 settings
.  | | | Example: allowedSizeValues = "100&#124;150&#124;300&#124;auto"
security | | |   
.  | allowOrigins | "*" | list of allowed domain (CORS)
.  | | | Example: allowOrigins = "http://nanogallery2.nanostudio.org|https://nano.gallery"
  
*: after changing any of these values, please delete all `_thumbnails` folders to refresh the cached data.  
  
  
<br />

### :white_circle: Supported image formats
JPEG, GIF and PNG.

<br />

### :warning: Usage on LOCAL WEB SERVER
Due to browser security features, `dataProvider` can not point to `localhost`, `127.0.0.1` or similar.  
Possible workaround:  
- configure your server to allow CORS (see https://enable-cors.org/server.html),      
- or install a browser extension to disable CORS checking
  
<br />

### :warning: Perfomances
- Lowres images, thumbnails and blurred images are generated on first request and then cached on disk.
- On first use, or after adding a large amount of new images, you may en encounter timeouts -> reload the page until you don't get any error. Generated data will then be cached, for faster access.


### :warning: SECURITY
Generation of thumbnails could be missused to satured the server disk space.  
It's highly recommanded to limit accepted values for thumbnail sizes in the `nano_photos_provider2.cfg` configuration file.  
  
  
### :warning: Limitations
- The nanogallery2 option `locationHash` should NOT be enabled if albums have more than 2 levels.  
  

### :copyright: License
nanoPhotosProvider2 is licensed under [CC BY-NC 3.0](http://creativecommons.org/licenses/by-nc/3.0/).  
Only for personal, non-profit organizations, or open source projects (without any kind of fee), you may use nanoPhotosProvider2 for free.


### :white_circle: Requirements
* nanogallery2 >= v1.4 (http://nanogallery2.nanostudio.org)
* Webserver
* PHP >= v5.2 with GD-Library
