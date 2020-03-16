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

namespace ddur\Warp_iMagick;

defined( 'ABSPATH' ) || die( -1 );

use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use \ddur\Warp_iMagick\Base\Meta_Settings_Renderer;

if ( ! class_exists( __NAMESPACE__ . '\Settings_Renderer' ) ) {

	/** Admin-Setting page renderer (separated rendering code) */
	final class Settings_Renderer extends Meta_Settings_Renderer {

		/** Render Sidebar (Logo). */
		public function render_settings_page_sidebar() {

			$meta_conf = Lib::safe_key_value( $this->settings->get_settings(), array( 'plugin', 'metabox' ), array() );
			$image_lnk = Lib::safe_key_value( $meta_conf, 'logo', $this->plugin->get_url_path() . '/assets/' . $this->pageslug . '-logo.png' );
			$click_lnk = Lib::safe_key_value( $meta_conf, 'link', 'https://warp.wordspeed.club/plugins/' . $this->pageslug . '/' );
			$box_title = Lib::safe_key_value( $meta_conf, 'name', wp_parse_url( $click_lnk, PHP_URL_HOST ) );
			$this->render_hard_meta_box( $image_lnk, $click_lnk, $box_title, 'logo' );

			$donatebox = Lib::safe_key_value( $this->settings->get_settings(), array( 'plugin', 'donate' ), array() );
			$image_lnk = Lib::safe_key_value( $donatebox, 'logo', $this->plugin->get_url_path() . '/assets/zizou-art-ring.jpg' );
			$click_lnk = Lib::safe_key_value( $donatebox, 'link', 'https://www.etsy.com/shop/ZizouArT?ref=' . $this->pageslug );
			$box_title = Lib::safe_key_value( $donatebox, 'name', 'ZizouArT' );
			$this->render_hard_meta_box( $image_lnk, $click_lnk, $box_title, 'donate', 'donate', 5000 );

		}

		/** Render Settings Page Subtitle.
		 *
		 * Redirected here from "on_render_settings_page" event handler.
		 */
		public function render_page_subtitle() {
			echo '<div>';
			echo esc_html( Lib::safe_key_value( $this->settings->get_settings(), array( 'page', 'subtitle' ), '' ) );
			echo '</div>';
		}

		/** Section Terms renderer.
		 *
		 * @access public
		 */
		public function render_section_terms() {

			$copyright_notice = $this->plugin->get_path();
			if ( property_exists( $this, 'copyright_notice' ) ) {
				$copyright_notice .= $this->copyright_notice;
			} else {
				$copyright_notice .= '/docs/copyright-notice.php';
			}
			if ( file_exists( $copyright_notice ) ) {
				require $copyright_notice;
			}
		}

		/** Section max width renderer.
		 *
		 * @access public
		 */
		public function render_section_max_width() {
			$sizes_max_width      = 0;
			$sizes_max_width_name = '';
			foreach ( get_intermediate_image_sizes() as $size_name ) {
				$current_width = intval( get_option( "{$size_name}_size_w" ) );
				if ( $current_width > $sizes_max_width ) {
					$sizes_max_width      = $current_width;
					$sizes_max_width_name = $size_name;
				}
			}
			foreach ( wp_get_additional_image_sizes() as $size_name => $sizes ) {
				$current_width = $sizes ['width'];
				if ( $current_width > $sizes_max_width ) {
					$sizes_max_width      = $current_width;
					$sizes_max_width_name = $size_name;
				}
			}
			if ( $sizes_max_width ) {
				?>
<span style="white-space:pre-line;color:DarkRed"><b>
Largest image width found is <?php echo esc_html( $sizes_max_width ); ?> px, defined by "<?php echo esc_html( $sizes_max_width_name ); ?>" size.
</b></span>
				<?php
			}
		}

		/** Section Image Extra Sizes.
		 *
		 * @access public
		 */
		public function render_section_image_extra_sizes() {
			$sizes_max_width      = 0;
			$sizes_max_width_name = '';
			$all_sizes            = array();
			foreach ( get_intermediate_image_sizes() as $size_name ) {
				$all_sizes [ $size_name ] = array( intval( get_option( "{$size_name}_size_w" ) ), intval( get_option( "{$size_name}_size_h" ) ), intval( get_option( "{$size_name}_crop" ) ) );
			}
			foreach ( wp_get_additional_image_sizes() as $size_name => $size_data ) {
				$all_sizes [ $size_name ] = array( intval( $size_data['width'] ), intval( $size_data['height'] ), intval( $size_data['crop'] ) );
			}
			$message = '';
			foreach ( $all_sizes as $size_name => $size_data ) {
				$message .= $size_name . ' - width: ' . $size_data[0] . ', height: ' . $size_data[1] . ', crop: ' . $size_data[2] . "\n";
			}
			?>
<span style="white-space:pre-line;color:DarkRed"><b>
			<?php echo esc_html( $message ); ?>
</b></span>
			<?php
		}

		/** Section PNG Thumbs options.
		 *
		 * Warn if Imagick is not capable to quantize (PNG) colors.
		 *
		 * @access public
		 */
		public function render_png_thumb_options() {

			$magic_test = new \Imagick();

			$warning_message = '';

			if ( ! is_callable( array( $magic_test, 'getImageColors' ) ) ) {
				$warning_message .= PHP_EOL . 'Imagick::getImageColors function is not available.';
			}
			if ( ! is_callable( array( $magic_test, 'getImageType' ) ) ) {
				$warning_message .= PHP_EOL . 'Imagick::getImageType function is not available.';
			}
			if ( ! is_callable( array( $magic_test, 'setImageType' ) ) ) {
				$warning_message .= PHP_EOL . 'Imagick::setImageType function is not available.';
			}
			if ( ! is_callable( array( $magic_test, 'quantizeImage' ) ) ) {
				$warning_message = 'Imagick::quantizeImage function is not available.';
			}
			if ( ! is_callable( array( $magic_test, 'posterizeImage' ) ) ) {
				$warning_message = 'Imagick::posterizeImage function is not available.';
			}

			$magic_test->clear();
			$magic_test->destroy();
			$magic_test = null;

			if ( trim( $warning_message ) ) {
				?>
<span style="white-space:pre-line;color:DarkRed"><b>
				<?php echo esc_html( $warning_message ); ?>
</b></span>
				<?php
			}
		}

		/** Section WEBP Thumbs options.
		 *
		 * Warn if GD is not present or not capable to generate webp thumbnails.
		 *
		 * @access public
		 */
		public function render_webp_thumb_options() {
			if ( ! function_exists( '\\imagewebp' ) ) {
				if ( Lib::is_gd_available() ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
Function that generates WebP images is not available in your PHP-GD extension.
</b></span>
					<?php
				} else {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD extension is not available.
</b></span>
					<?php
				}
			}
			if ( Lib::is_gd_available() ) {
				if ( ! function_exists( '\\imagecreatefromjpeg' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD extension cannot read JPEG image files.
</b></span>
					<?php
				}
				if ( ! function_exists( '\\imagecreatefrompng' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD extension cannot read PNG image files.
</b></span>
					<?php
				}
				if ( ! function_exists( '\\imagesavealpha' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD function missing: imagesavealpha.
</b></span>
					<?php
				}
				if ( ! function_exists( '\\imagealphablending' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD function missing: imagealphablending.
</b></span>
					<?php
				}
				if ( ! function_exists( '\\imageistruecolor' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD function missing: imageistruecolor.
</b></span>
					<?php
				}
				if ( ! function_exists( '\\imagepalettetotruecolor' ) ) {
					?>
<span style="white-space:pre-line;color:DarkRed"><b>
PHP-GD function missing: imagepalettetotruecolor.
</b></span>
					<?php
				}
			}
		}

	}
}
