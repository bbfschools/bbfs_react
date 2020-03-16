=== Warp iMagick - Image Compressor ===
Author: Dragan Đurić
Contributors: ddur
License: GPLv2
Requires PHP: 5.6
Tested up to: 5.3
Stable tag: 1.0.5
Requires at least: 4.7
Tags: webp, image optimization, image optimizer, image compression, image compressor, compress images, optimize images, image, images, optimize, optimizer, compress, compressor, google, pagespeed, insights
Donate link: https://www.etsy.com/shop/ZizouArT?ref=donate-warp-imagick

Warp drive site images with iMagick library. Optimize JPEG/PNG and compress to WebP.

== Description ==
[**Warp** drive](https://en.wikipedia.org/wiki/Warp_drive) your site images with [**iMagick** library](https://imagemagick.org/).

* **Take full control over size and quality of images.**
* **Reduce original images to maximum width (option).**
* **Use only PHP software installed on your server.**
* **No executable binaries installed or required.**
* **No external optimization service required.**
* **No subscription email asked or required.**
* **No images sent to external server.**

= Features =

* **Optimize PNG/JPEG and Generate WebP Images:**
Images will be automatically optimized on upload or on "regenerate thumbnails". Original image is preserved. Compression will always start from original image quality, never "overoptimize". Reoptimize existing images with ["Regenerate Thumbnails"](https://wordpress.org/plugins/regenerate-thumbnails/) plugin batch process.

* **JPEG Compression Quality:**
Select compression quality from 30% to 85%. WordPress default is 82%. Lossy Compression.

* **JPEG Compression Type:**
WordPress Default or Imagick Default. Lossy Compression.

* **JPEG Colorspace Transform:**
WordPress Default, RGB, sRGB*, scRGB, LOG or GRAY.

* **JPEG Color Sampling Factors:**
WordPress Default, 4:1:0, 4:1:1, 4:2:0*, 4:2:1, 4:2:2, 4:4:0, 4:4:1, 4:4:4. Lossy Color Compression.

* **JPEG Interlace Scheme:**
WordPress Default, No Interlace, Imagick Default, Progressive, Automatic Probe for smaller size*. Loosless Compression.

* **PNG Color Reduction:**
Quantize PNG Colors to range between 16 and 1024 colors. Lossy Color Compression.

* **PNG Color Dithering:**
Enable Color Dithering to improve color transition quality (except transparent & less-than-257-colors). Lossy Compensation.

* **PNG Color Palette:**
Images reduced to less than 257 colors are automatically converted to PNG Color Palette. Loosless Compression.

* **PNG Compression:**
WordPress Default and Imagick Default compression strategies are tested and smallest file size is written to disk. Loosless Compression.

* **WebP Compression:**
Enable to automatically generate optimized WebP versions of JPEG and PNG (except transparent) images. See Settings page "Help" how about to configure server.

* **Strip Metadata:**
WordPress Default, Set WP Default Off, Set WP Default On, Strip All Metadata*. Loosless Compression.

* **Maximum Upload Width**
Resize Large Upload/Original Images to maximum width.

* **Clean uninstall:**
By default, nothing is left in your database after uninstall. Feel free to install and activate to make a trial of this plugin functionality. However, you can choose to preserve plugin options after uninstall.

* **Privacy**
This plugin does not collect nor send any personally identifiable data. WordPress builtin cookies are used to store admin-settings page-state.

* **Multisite support**
Not tested yet!

== Installation ==

= Using The WordPress Plugin Repository =
1. Navigate to the 'Plugins' -> 'Add New' .
2. Search for 'Warp iMagick'.
3. Select and click 'Install Now'.
4. Activate the plugin.

== Screenshots ==
1. **JPEG Settings**
2. **PNG Settings**
3. **WebP Settings**
4. **Other Settings**
5. **Regenerate Thumbnails**
6. **WebP Mobile Page Score**

== Frequently Asked Questions ==

= Which PHP extensions are required by this plugin? =
1. PHP-Imagick to compress JPEG/PNG files (required).
2. PHP-GD for WebP files (optional, but usually installed).

In order to modify/resize/crop photos or images in Wordpress, at least PHP-GD to extension is required. Wordpress supports image editing and resizing only with two above listed extensions. When both extensions are installed, WordPress prefers PHP-Imagick over PHP-GD.

= Do I have both required PHP extensions installed? =
1. WordPress 5.2 and above: Administrator: Menu -> Tools -> Site Health -> Info -> Expand "Media Handling" and check if "ImageMagick version string" and "GD version" have values.
2. WordPress 5.1 and below: Install [Health Check & Troubleshooting](https://wordpress.org/plugins/health-check/) plugin. Open "Health Check" plugin page and click on "PHP Information" tab. You will find there all PHP extensions installed and enabled. Search (Ctrl-F) on page for "Imagick" and "GD".
3. WordPress Editor class must be WP_Image_Editor_Imagick (or Warp_Image_Editor_Imagick) but **NOT** WP_Image_Editor_GD.
4. PHP-Imagick extension must be linked with ImageMagick library version **6.3.2** or newer.
5. PHP-GD extension version must be at least 2.0.0 to be accepted by WordPress Image Editor.

= Does my web hosting service provide PHP Imagick and GD extensions? =
1. [WPEngine](https://wpengine.com/support/platform-settings/): Both by default.
2. [EasyWP](https://www.namecheap.com/support/knowledgebase/article.aspx/9697/2219/php-modules-and-extensions-on-shared-hosting-servers): Both by default.
3. [DreamHost](https://help.dreamhost.com/hc/en-us/articles/214893957): By configuration.
4. [SiteGround](https://www.siteground.com/kb/enable-imagick-imagemagick/): Must enable.
5. Ask your host-service provider.

= How to install missing PHP-Imagick extension? =
1. [PHP-Imagick setup](https://www.php.net/manual/en/imagick.setup.php)
2. [CPanel based host](https://documentation.cpanel.net/display/68Docs/PHP+Extensions+and+Applications+Package#PHPExtensionsandApplicationsPackage-PHPExtensionsandApplicationsPackageInstaller)
3. Debian/Ubuntu: "apt-get install php-imagick".
4. Fedora/CentOs: "yum install php-imagick".
5. Ask your host-service provider.

= How to serve WebP images? =
See plugin **HELP** for instructions how to configure server to redirect .jpg/.png to .jpg.webp/.png.webp, if such file exists and browser suports webp image format.

= Why WebP files have two extensions? =
To prevent overwriting duplicate "WebP" files. With single extension, when you upload "image.png" and "image.jpg", second "image.webp" would overwrite previous one.

= Why is WebP (checkbox) disabled? =
Because your server has no PHP-GD graphic editing extension or PHP-GD extension has no WebP support.

= What happens when plugin is disabled or deleted? =
1. If WebP was never enabled, existing images remain optimized. New media thumbnails won't be optimized. If you run ["Regenerate Thumbnails"](https://wordpress.org/plugins/regenerate-thumbnails/) batch process, it will restore original file-size and quality of WordPress thumbnails.
2. If you have WebP images, they won't be deleted. You should delete all WebP images before deactivate/delete this plugin. To delete WebP images, disable Webp and batch-run ["Regenerate Thumbnails"](https://wordpress.org/plugins/regenerate-thumbnails/) for all media images.

= Why plugin fails to activate on my server? =
Because your server has not PHP-Imagick installed or has too old version of PHP-Imagick. Maybe some other plugin creates conflict.

== Changelog ==

= 1.0.5 =
* Fix transparency-check after edit/restore.
* Cover transparency-check exception.
* Hooks refactored.

= 1.0.4 =
* Better transparency detection

= 1.0.3 =
* Do not generate WebP for transparent PNG images

= 1.0.2 =
* Do not dither transparent PNG images

= 1.0.1 =
* Added PNG Reduction & Generate WebP Images

= 1.0.0 =
* Initial WordPress.org Release
