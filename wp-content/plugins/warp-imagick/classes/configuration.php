<?php
/**
 * Copyright © 2017-2019 Dragan Đurić. All rights reserved.
 *
 * @package warp-imagick
 * @license GNU General Public License Version 2.
 * @copyright © 2017-2019 Dragan Đurić. All rights reserved.
 * @author Dragan Đurić <dragan dot djuritj at gmail dot com>
 * @link https://wordpress.org/plugins/warp-imagick/
 *
 * This copyright notice, source files, licenses and other included
 * materials are protected by U.S. and international copyright laws.
 * You are not allowed to remove or modify this or any other
 * copyright notice contained within this software package.
 */

use \ddur\Warp_iMagick\Plugin;

defined( 'ABSPATH' ) || die( -1 );

return array(

	'plugin'          => array(

		'requires' => array(

			'wp'         => '4.7',
			'php'        => '5.6',

			'extensions' => array(
				'imagick' => 'PHP Imagick',
			),

			'classes'    => array(
				'\\Imagick' => 'PHP Imagick extension',
			),

			'functions'  => array(
				'\\getimagesize' => 'PHP GD extension',
			),

			'constants'  => array(
				'\\Imagick::COMPRESSION_JPEG' => 'PHP Imagick extension',
				'\\Imagick::COLORSPACE_SRGB'  => 'PHP Imagick extension',
				'\\Imagick::INTERLACE_PLANE'  => 'PHP Imagick extension',
				'\\Imagick::INTERLACE_JPEG'   => 'PHP Imagick extension',
			),
		),

		'metabox'  => array(
			'link' => 'https://warp.wordspeed.club/plugins/' . Plugin::slug() . '/',
			'name' => 'Coming soon!',
		),

		'donate'   => array(
			'link' => 'https://www.etsy.com/shop/ZizouArT?ref=' . Plugin::slug(),
			'name' => 'ZizouArT',
		),
	),
	'menu'            => array(
		'title'         => 'Warp iMagick',
		'menu-icon'     => 'dashicons-hammer',
		'parent-slug'   => 'upload.php',
		'position'      => 99,


		'settings-name' => __( 'Settings', 'warp-imagick' ),
		'settings-icon' => '☢',
	),
	'page'            => array(
		'title'     => 'Warp iMagick - Image Compressor',
		'subtitle'  => __( 'Optimize JPEG, PNG and generate WebP images', 'warp-imagick' ),
		'help-tabs' => array(
			array(
				'id'      => 'overview',
				'title'   => __( 'Overview', 'warp-imagick' ),
				'content' => '
<p><b>Optimize JPEG PNG and generate WebP images.</b></p>
<p>Reduce file size of WP generated JPEG and PNG thumbnails and sizes. Generate optimized WebP version of JPEG and PNG images.</p>
<p>When optimization or other setting are changed, use <a target=_blank rel="noopener noreferrer" href=https://wordpress.org/plugins/regenerate-thumbnails>"Regenerate Thumbnails" plugin</a> to regenerate or batch-regenerate images with new settings.</p>
',
			),
			array(
				'id'      => 'jpeg-reduction',
				'title'   => __( 'JPEG Reduction', 'warp-imagick' ),
				'content' => '
<p><b>Reduce file size of JPEG images.</b></p>
<p>Plugin default values are marked with \'*\'.</p>
<p>WordPress default JPEG compression quality is 82%.</p>
',
			),
			array(
				'id'      => 'png-reduction',
				'title'   => __( 'PNG Reduction', 'warp-imagick' ),
				'content' => '
<p><b>Reduce file size of PNG images.</b></p>
<p>Lossy compress by reducing number of colors.</p>
<p>Enable <a target=_blank rel="noopener noreferrer" href=https://en.wikipedia.org/wiki/Dither >Dither</a> to improve transition between colors. Disabled for transparent+palette images.</p>
<p>Configure maximum number of colors in the image. Images with number of colors less than maximum will not be quantized again.</p>
<p>Lossless compression is set to maximum (9). Every PNG image is tested with two default filter/strategy settings (WordPress/Imagick) and smaller file size is saved.</p>
',
			),
			array(
				'id'      => 'webp-images',
				'title'   => __( 'WebP Images', 'warp-imagick' ),
				'content' => '
<p><b>Serving <a target=_blank rel="noopener noreferrer" href=https://developers.google.com/speed/webp>WebP</a> Images.</b></p>
<p>Enable to generate optimized <a target=_blank rel="noopener noreferrer" href=https://en.wikipedia.org/wiki/Webp>WebP</a> versions for all media-attached JPEG/PNG images.</p>
<p>Transparent PNG images are exception. Current PHP software cannot generate transparent WebP images.
<p>This settings may be disabled if your server\'s PHP software is not capable to generate WebP images.
<p>When enabled, each image thumbnail/size (including download) will have optimized WebP version. Named with ".webp" extension appended at the end of original image file name. In example: "image-768x300.jpg.webp" will be found along with "image-768x300.jpg".</p>
<p>To serve WebP images, configure server to transparently serve WebP images to browsers supporting "image/webp" mime-type.</p>
<p>Configuring server for serving WebP images is out of the scope of this plugin. Automatic configuration change could potentially break your site. You will have to DIY (Do It Yourself).
<p>Below is Apache .htaccess configuration snippet that should work on most configurations. <b>Backup/save your original .htaccess file before applying changes!</b> Copy snippet and add it at the top of .htaccess file in site root.</p>
<p style="background:#eaeaea;padding:5px"><code style="white-space:pre;color:DarkRed;background:none;padding:0"># warp-imagick - First line of .htaccess file.
&lt;ifModule mod_rewrite.c&gt;
  RewriteEngine On
  RewriteBase /
  RewriteCond %{HTTP_ACCEPT} image/webp
  RewriteCond %{DOCUMENT_ROOT}$1.$2.webp -f
  RewriteRule ^(.*)\.(jpe?g|png)$ $1.$2.webp [T=image/webp,E=accept:1]
&lt;/IfModule&gt;

&lt;IfModule mod_headers.c&gt;
  Header append Vary Accept env=REDIRECT_accept
&lt;/IfModule&gt;

AddType image/webp .webp

</code></p>
<p>This Apache configuration snippet may work as separate .htaccess file in "/wp-content/" or in "/wp-content/uploads/". If allowed by your host Apache configuration.</p>
<p>To check if WebP configuration is working after modifications:
<ol>
<li>Use updated Chrome browser!</li>
<li>In plugin settings enable WebP images.</li>
<li>Upload JPEG/PNG image while WebP images still enabled :).</li>
<li>Attach media to some page and open that page, or view/open attachment page if your site/theme supports it.</li>
<li>Open Chrome Development Tools [Ctrl+Shift+I].</li>
<li>Select Network Tab.</li>
<li>Press [F5] or [Ctrl-R] to reload page.</li>
<li>Find image request and click on it to expand Request/Response headers.</li>
<li>Check Response headers for image. "Content-Type:" should be "image/webp", and "Content-Length:" should be smaller than original JPEG/PNG image size.</li>
</ol>
</p>
<p>Instructions "How to configure server to serve WebP images" for other http-servers, is easy to find on internet.</p>
<p>Good <b>Nginx</b> instructions: <a target=_blank rel="noopener noreferrer" href=https://github.com/uhop/grunt-tight-sprite/wiki/Recipe:-serve-WebP-with-nginx-conditionally>Recipe: serve WebP with nginx conditionally</a>.</p>
',
			),
			array(
				'id'      => 'max-width',
				'title'   => __( 'Maximum Width', 'warp-imagick' ),
				'content' =>
				'
<p>Enable to limit maximum width of upload/original image.</p>
<p>When enabled, JPEG/PNG image will be automatically reduced/downsized on upload or on (batch) regenerate thumbnails.</p>
<p>Recommended maximum width value is 1600 px or at least as wide as widest registered image size.</p>
<p>Reducing is proportional, reduced image will have same aspect ratio as original.</p>
<p><b>Reducing is irreversible, still, you can disable limit and upload full size image again.</b></p>
',
			),
			array(
				'id'      => 'plugin',
				'title'   => __( 'Plugin Options', 'warp-imagick' ),
				'content' =>
				'
<p>Set checkbox "on" to <b>remove</b> plugin settings when plugin is deleted.</p>
<p>Set checkbox "off" to <b>keep</b> plugin settings after plugin is deleted.</p>
<p><b>Defaults to "on" when plugin activated and no previous settings stored.</b></p>
',
			),
		),
	),

	'capability'      => 'manage_options',

	'fields-extended' => array(
		'plugin-link'       => '',
		'plugin-link-title' => 'Warp WordSpeed Club',
	),

	'sections'        => array(

		'jpeg-thumb-options' => array(
			'title'  => __( 'JPEG Settings', 'warp-imagick' ),
			'fields' => array(

				'jpeg-compression-quality' => array(
					'label'   => __( 'Compression Quality', 'warp-imagick' ) . ' (' . Plugin::jpeg_quality_default() . '% *)',
					'type'    => 'range',
					'style'   => 'width:200px',
					'title'   => __( 'Compression Quality in percentage. WordPress Default is 82%. WordPress Default value may change in the future.', 'warp-imagick' ),
					'default' => Plugin::jpeg_quality_default(),
					'options' => array(
						'min'   => Plugin::jpeg_quality_min_val(),
						'max'   => Plugin::jpeg_quality_max_val(),
						'units' => __( '%', 'warp-imagick' ),
					),
				),

				'jpeg-compression-type'    => array(
					'label'   => __( 'Compression Type', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'title'   => __( 'Use WordPress default or Imagick default compression. Both may be the same. WordPress Default value may change in the future. Default value is "Imagick".', 'warp-imagick' ),
					'default' => ( defined( '\\Imagick::COMPRESSION_JPEG' ) ? \Imagick::COMPRESSION_JPEG : 0 ),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_jpeg_commpression_types',
					),
				),

				'jpeg-colorspace'          => array(
					'label'   => __( 'Color space', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'title'   => __( 'Convert Colors to Color Space. WordPress Default value may change in the future. Default value is "sRGB".', 'warp-imagick' ),
					'default' => ( defined( '\\Imagick::COLORSPACE_SRGB' ) ? \Imagick::COLORSPACE_SRGB : 0 ),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_jpeg_colorspaces',
					),
				),

				'jpeg-sampling-factor'     => array(
					'label'   => __( 'Color Sampling factor', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'title'   => __( 'Reduce image file size by reducing color sampling factor. WordPress Default value may change in the future. Default value is "4:2:0".', 'warp-imagick' ),
					'default' => '4:2:0',
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_jpeg_sampling_factors',
					),
				),

				'jpeg-strip-meta'          => array(
					'label'   => __( 'Strip meta data', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'default' => 3,
					'title'   => __( 'WordPress by default strips most of metadata except protected profiles. WordPress Default value may change in the future. Default value is "Strip All".', 'warp-imagick' ),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_strip_metadata',
					),
				),

				'jpeg-interlace-scheme'    => array(
					'label'   => __( 'Interlace scheme', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'default' => -1,
					'title'   => __( 'Interlace scheme WP/ON/OFF or AUTO to try both and select smaller file size. WordPress Default value may change in the future. Default value is "AUTO".', 'warp-imagick' ),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_jpeg_interlace_types',
					),
				),
			),
		),

		'png-image-options'  => array(
			'title'  => __( 'PNG Settings', 'warp-imagick' ),
			'render' => 'render_png_thumb_options',
			'fields' => array(

				'png-reduce-colors-enable'    => array(
					'label'   => __( 'Reduce Colors', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => 'on',
					'title'   => __( 'Lossy Compression. When enabled, colors will be reduced to maximum number of colors (see below). Default value is on (true)', 'warp-imagick' ),
					'options' => array(
						'disabled' => ( defined( '\\Imagick::IMGTYPE_PALETTE' ) ? false : true ),
					),
				),

				'png-reduce-colors-dither'    => array(
					'label'   => __( 'Dither Colors', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => 'on',
					'title'   => __( 'Color Compensation. When enabled, dither to improve color transients (see https://en.wikipedia.org/wiki/Dither). File size will increase when enabled. Disabled on transparent images when max colors is 256 or less. Default value is on (true).', 'warp-imagick' ),
				),

				'png-reduce-max-colors-count' => array(
					'label'   => __( 'Maximum Colors', 'warp-imagick' ) . ' (' . Plugin::png_max_colors_default() . ' *)',
					'type'    => 'range',
					'style'   => 'width:200px',
					'default' => Plugin::png_max_colors_default(),
					'title'   => __( 'Lossy Compression. If image has more colors than Maximum Colors, number of colors will be reduced down. If number of colors is less than or equal to 256, image colors will be converted to palette. File size and color quality will increase with more colors', 'warp-imagick' ),
					'options' => array(
						'min'   => Plugin::png_max_colors_min_val(),
						'max'   => Plugin::png_max_colors_max_val(),
						'step'  => Plugin::png_max_colors_min_val(),
						'units' => __( 'colors', 'warp-imagick' ),
					),
				),

				'png-strip-meta'              => array(
					'label'   => __( 'Strip meta data', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'default' => 3,
					'title'   => __( 'WordPress by default strips most of metadata except protected profiles. WordPress Default value may change in the future. Default value is "Strip All".', 'warp-imagick' ),
					'options' => array(
						'source'   => 'callback',
						'callback' => 'get_form_strip_metadata',
					),
				),
			),
		),

		'webp-image-options' => array(
			'title'  => __( 'WebP Settings', 'warp-imagick' ),
			'render' => 'render_webp_thumb_options',
			'fields' => array(
				'webp-images-create' => array(
					'label'   => __( 'Generate WebP Images', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => 'off',
					'title'   => __( 'If enabled, for every media image (except transparent), one WebP image will be added. Automatic, on media download or when thumbnails are regenerated. See Help Tab at the right-top of the page, section "WebP Images" for "how to serve instructions".', 'warp-imagick' ),
					'options' => array(
						'disabled' => ! Plugin::instance()->can_generate_webp_clones(),
					),
				),
			),
		),

		'image-max-width'    => array(
			'title'  => __( 'Reduce upload/original JPEG/PNG to maximum width', 'warp-imagick' ),
			'render' => 'render_section_max_width',
			'fields' => array(

				'image-max-width-enabled' => array(
					'label'   => __( 'Enable downsizing', 'warp-imagick' ),
					'type'    => 'checkbox',
					'default' => 'off',
					'title'   => __( 'If enabled, images wider than maximum image width limit will be proportionally downsized to maximum width given (below). Downsizing is automatic, on media download or when thumbnails are regenerated.', 'warp-imagick' ),
				),



				'image-max-width-pixels'  => array(
					'label'   => __( 'Maximum image width', 'warp-imagick' ),
					'type'    => 'range',
					'style'   => 'width:200px',
					'default' => Plugin::max_width_default(),
					'title'   => __( 'Maximum image width limit in pixels. Images wider than maximal width limit will be proportionally downsized to maximal width given here. Downsizing is automatic, on media download or when thumbnails are regenerated.', 'warp-imagick' ),
					'options' => array(
						'min'   => Plugin::max_width_min_val(),
						'max'   => Plugin::max_width_max_val(),
						'step'  => 8,
						'units' => __( 'pixels', 'warp-imagick' ),
					),
				),
			),
		),



		'plugin-options'     => array(
			'title'  => __( 'Plugin Settings', 'warp-imagick' ),
			'fields' => array(

				'remove-settings'  => array(
					'label'   => __( 'Remove settings on uninstall', 'warp-imagick' ),
					'type'    => 'checkbox',
					'style'   => 'width:200px',
					'default' => 'on',
					'title'   => __( 'Remove plugin settings along with plugin uninstall and delete', 'warp-imagick' ),
				),

				'menu-parent-slug' => array(
					'label'   => __( 'Select parent menu', 'warp-imagick' ),
					'type'    => 'select',
					'style'   => 'width:200px',
					'default' => '',
					'title'   => __( 'Select parent menu', 'warp-imagick' ),
					'options' => array(
						'source' => 'values',
						'values' => array(
							''                    => 'Default',
							0                     => 'Top',
							'index.php'           => 'Dashboard',
							'upload.php'          => 'Media',
							'tools.php'           => 'Tools',
							'options-general.php' => 'Settings',
							99                    => 'Bottom',
						),
					),
				),
			),
		),

		'terms-of-use'       => array(
			'title'  => __( 'Copyright, License, Privacy and Disclaimer', 'warp-imagick' ),
			'render' => 'render_section_terms',
			'submit' => false,
			'fields' => array(),
		),
	),

	'tabs'            => array(
		'main-options' => array(
			'title'    => 'Compress Settings',
			'sections' => 3,
		),
		'conf-options' => array(
			'title'    => 'General Settings',
			'sections' => 2,
		),
		'terms-of-use' => array(
			'title'    => 'Terms of Use',
			'sections' => 1,
			'submit'   => false,
		),
	),
);
