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
use \ddur\Warp_iMagick\Base\Meta_Settings;

if ( ! class_exists( __NAMESPACE__ . '\Settings' ) ) {

	require_once __DIR__ . '/class-settings-renderer.php';

	/** Final implementation of Plugin Settings. */
	final class Settings extends Meta_Settings {

		// phpcs:ignore
	# region Configuration and Construction

		/** Get Plugin Settings/Configuration, abstract method, must implement.
		 *
		 * @return array Plugin Configuration Settings compliant array.
		 */
		public function read_configuration() {
			return require __DIR__ . '/configuration.php';
		}

		/** Instantiate custom Settings_Renderer. */
		protected function init() {
			$this->renderer = new Settings_Renderer( $this );
		}

		/** Enqueue admin page scripts. Overridden. */
		public function enqueue_page_scripts() {

			Lib::enqueue_style( 'abstract-settings-admin-styled' );
			Lib::enqueue_script( 'abstract-settings-admin-styled' );

			$relative_path = Lib::relative_path( $this->plugin->get_path() );
			Lib::enqueue_style( $this->pageslug, $relative_path . '/assets/style.css', array(), $style_version   = false, 'screen' );
			Lib::enqueue_script( $this->pageslug, $relative_path . '/assets/script.js', array(), $script_version = false, 'screen' );
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Form Display and Validation

		/** Get form/display field value. Overridden.
		 *
		 * From Options API to Settings UI (page&form).
		 * Validate (&transform) options.
		 *
		 * @param string $name of the field.
		 * @param mixed  $value of the field.
		 * @return mixed $value for display.
		 */
		protected function get_form_field_value( $name, $value ) {
			return $value;
		}

		/** Validate form submitted checkbox by key (field name).
		 *
		 * @param string $key - name of the checkbox field.
		 * @param array  $values - all submitted values.
		 */
		public static function validate_checkbox_key( $key, &$values ) {
			if ( ! array_key_exists( $key, $values ) ) {
				$values [ $key ] = false;
			} else {
				$values [ $key ] = self::validate_checkbox_value( $values [ $key ] );
			}
		}

		/** Validate form checkbox value.
		 *
		 * @param mixed $value of submitted checkbox field.
		 * @return bool $value validated to bool.
		 */
		public static function validate_checkbox_value( $value ) {
			switch ( $value ) {
				case true:
				case 'on':
					$value = true;
					break;
				default:
					if ( ! is_bool( $value ) ) {
						$value = false;
					}
			}
			return $value;
		}

		/** Validate (&transform) input values. Overridden.
		 *
		 * From Settings UI (form&submit) to Options API.
		 *
		 * @param array $values to validate.
		 * @return array $values validated.
		 */
		public function validate_form_input( $values ) {

			if ( ! is_array( $values ) ) {
				$values = array();
				$this->add_settings_update_error( "Invalid input type ({$get_type ($values)})" );
			}

			self::validate_checkbox_key( 'png-reduce-colors-enable', $values );

			self::validate_checkbox_key( 'png-reduce-colors-dither', $values );

			self::validate_checkbox_key( 'webp-images-create', $values );

			self::validate_checkbox_key( 'image-max-width-enabled', $values );

			self::validate_checkbox_key( 'image-max-width-backup', $values );

			self::validate_checkbox_key( 'extra-size-same-width', $values );

			if ( ! array_key_exists( 'image-max-width-pixels', $values ) ) {
				$values ['image-max-width-pixels'] = Plugin::max_width_default();
			}
			if ( $values ['image-max-width-pixels'] < Plugin::max_width_min_val() || $values ['image-max-width-pixels'] > Plugin::max_width_max_val() ) {
				$values ['image-max-width-pixels'] = Plugin::max_width_default();
			}

			if ( ! array_key_exists( 'jpeg-compression-quality', $values ) ) {
				$values ['jpeg-compression-quality'] = Plugin::jpeg_quality_default();
			} else {
				$values ['jpeg-compression-quality'] = intval( $values ['jpeg-compression-quality'] );
				if ( $values ['jpeg-compression-quality'] < Plugin::jpeg_quality_min_val() ) {
					$values ['jpeg-compression-quality'] = Plugin::jpeg_quality_default();
				}
				if ( $values ['jpeg-compression-quality'] > Plugin::jpeg_quality_max_val() ) {
					$values ['jpeg-compression-quality'] = Plugin::jpeg_quality_default();
				}
			}

			$jpeg_compression_type_default = defined( '\\Imagick::COMPRESSION_JPEG' ) ? \Imagick::COMPRESSION_JPEG : 0;
			if ( ! array_key_exists( 'jpeg-compression-type', $values ) ) {
				$values ['jpeg-compression-type'] = $jpeg_compression_type_default;
			} else {
				$valid_compression_types = $this->get_form_jpeg_commpression_types();
				if ( array_key_exists( intval( $values ['jpeg-compression-type'] ), $valid_compression_types ) ) {
					$values ['jpeg-compression-type'] = intval( $values ['jpeg-compression-type'] );
				} else {
					$values ['jpeg-compression-type'] = $jpeg_compression_type_default;
				}
			}

			$jpeg_colorspace_default = defined( '\\Imagick::COLORSPACE_SRGB' ) ? \Imagick::COLORSPACE_SRGB : 0;
			if ( ! array_key_exists( 'jpeg-colorspace', $values ) ) {
				$values ['jpeg-colorspace'] = $jpeg_colorspace_default;
			} else {
				$valid_colorspaces = $this->get_form_jpeg_colorspaces();
				if ( array_key_exists( intval( $values ['jpeg-colorspace'] ), $valid_colorspaces ) ) {
					$values ['jpeg-colorspace'] = intval( $values ['jpeg-colorspace'] );
				} else {
					$values ['jpeg-colorspace'] = $jpeg_colorspace_default;
				}
			}

			$jpeg_interlace_scheme_default = defined( '\\Imagick::INTERLACE_JPEG' ) ? \Imagick::INTERLACE_JPEG : 0;
			if ( ! array_key_exists( 'jpeg-interlace-scheme', $values ) ) {
				$values ['jpeg-interlace-scheme'] = $jpeg_interlace_scheme_default;
			} else {
				$valid_interlace_types = $this->get_form_jpeg_interlace_types();
				if ( array_key_exists( intval( $values ['jpeg-interlace-scheme'] ), $valid_interlace_types ) ) {
					$values ['jpeg-interlace-scheme'] = intval( $values ['jpeg-interlace-scheme'] );
				} else {
					$values ['jpeg-interlace-scheme'] = $jpeg_interlace_scheme_default;
				}
			}

			if ( ! array_key_exists( 'jpeg-sampling-factor', $values ) ) {
				$values ['jpeg-sampling-factor'] = '4:2:0';
			} else {
				$valid_sampling_factors = $this->get_form_jpeg_sampling_factors();
				if ( ! array_key_exists( $values ['jpeg-sampling-factor'], $valid_sampling_factors ) ) {
					$values ['jpeg-sampling-factor'] = '4:2:0';
				}
			}

			if ( ! array_key_exists( 'jpeg-strip-meta', $values ) ) {
				$values ['jpeg-strip-meta'] = 0;
			} else {
				$valid_strip_metadata = $this->get_form_strip_metadata();
				if ( array_key_exists( ( intval( $values ['jpeg-strip-meta'] ) ), $valid_strip_metadata ) ) {
					$values ['jpeg-strip-meta'] = ( intval( $values ['jpeg-strip-meta'] ) );
				} else {
					$values ['jpeg-strip-meta'] = 0;
				}
			}

			if ( ! array_key_exists( 'png-reduce-max-colors-count', $values ) ) {
				$values ['png-reduce-max-colors-count'] = Plugin::png_max_colors_default();
			} else {
				$values ['png-reduce-max-colors-count'] = intval( $values ['png-reduce-max-colors-count'] );
				if ( $values ['png-reduce-max-colors-count'] < Plugin::png_max_colors_min_val() ) {
					$values ['png-reduce-max-colors-count'] = Plugin::png_max_colors_default();
				}
				if ( $values ['png-reduce-max-colors-count'] > Plugin::png_max_colors_max_val() ) {
					$values ['png-reduce-max-colors-count'] = Plugin::png_max_colors_default();
				}
			}

			if ( ! array_key_exists( 'png-strip-meta', $values ) ) {
				$values ['png-strip-meta'] = 0;
			} else {
				$valid_strip_metadata = $this->get_form_strip_metadata();
				if ( array_key_exists( ( intval( $values ['png-strip-meta'] ) ), $valid_strip_metadata ) ) {
					$values ['png-strip-meta'] = ( intval( $values ['png-strip-meta'] ) );
				} else {
					$values ['png-strip-meta'] = 0;
				}
			}

			$values ['image-max-width-backup'] = false;
			$values ['extra-size-same-width']  = false;

			$this->set_dynamic_menu_position( $values );

			delete_transient( $this->pageslug . '-update-settings' );

			return $values;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Generate Form Inputs / Validation Arrays

		/** Get possible JPEG compression types for the form */
		public function get_form_jpeg_commpression_types() {

			$values = array();

			$values [0] = __( 'WordPress Default', 'warp-imagick' );

			if ( defined( '\\Imagick::COMPRESSION_JPEG' ) ) {
				$values [ \Imagick::COMPRESSION_JPEG ] = __( 'Imagick Default *', 'warp-imagick' );
			}

			return $values;
		}

		/** Get some available JPEG colorspaces for the form */
		public function get_form_jpeg_colorspaces() {

			$values = array();

			$values [0] = __( 'WordPress Default', 'warp-imagick' );

			if ( defined( '\Imagick::COLORSPACE_RGB' ) ) {
				$values [ \Imagick::COLORSPACE_RGB ] = __( 'RGB', 'warp-imagick' );
			}
			if ( defined( '\Imagick::COLORSPACE_SRGB' ) ) {
				$values [ \Imagick::COLORSPACE_SRGB ] = __( 'sRGB *', 'warp-imagick' );
			}
			if ( defined( '\Imagick::COLORSPACE_SCRGB' ) ) {
				$values [ \Imagick::COLORSPACE_SCRGB ] = __( 'scRGB', 'warp-imagick' );
			}
			if ( defined( '\Imagick::COLORSPACE_LOG' ) ) {
				$values [ \Imagick::COLORSPACE_LOG ] = __( 'LOG', 'warp-imagick' );
			}
			if ( defined( '\Imagick::COLORSPACE_GRAY' ) ) {
				$values [ \Imagick::COLORSPACE_GRAY ] = __( 'GRAY', 'warp-imagick' );
			}

			return $values;

		}

		/** Get sampling factor choices for the form */
		public function get_form_jpeg_sampling_factors() {

			$magic_test = new \Imagick();

			$is_callable_set_image_property = false;
			if ( is_callable( array( $magic_test, 'setImageProperty' ) ) ) {
				$is_callable_set_image_property = true;
			}

			$magic_test->clear();
			$magic_test->destroy();
			$magic_test = null;

			if ( $is_callable_set_image_property ) {
				return array(
					''      => 'WordPress Default',
					'4:1:0' => '4:1:0 - Best file size',
					'4:1:1' => '4:1:1',
					'4:2:0' => '4:2:0 *',
					'4:2:1' => '4:2:1',
					'4:2:2' => '4:2:2',
					'4:4:0' => '4:4:0',
					'4:4:1' => '4:4:1',
					'4:4:4' => '4:4:4 - Best resolution',
				);

			} else {
				return array(
					'' => 'WordPress Default',
				);
			}
		}

		/** Get strip metadata options for the form */
		public function get_form_strip_metadata() {

			$values = array();

			$values [0] = __( 'WordPress Default', 'warp-imagick' );
			$values [1] = __( 'Do Not Strip At All', 'warp-imagick' );
			$values [2] = __( 'Preserve Important', 'warp-imagick' );

			$magic_test = new \Imagick();
			if ( is_callable( array( $magic_test, 'stripImage' ) ) ) {
				$values [3] = __( 'Strip All Metadata *', 'warp-imagick' );
			}
			$magic_test->clear();
			$magic_test->destroy();
			$magic_test = null;

			return $values;
		}

		/** Get available JPEG interlace types for the form */
		public function get_form_jpeg_interlace_types() {

			$values = array();

			$values [0] = __( 'WordPress Default', 'warp-imagick' );

			if ( defined( '\Imagick::INTERLACE_JPEG' ) ) {
				$values [ \Imagick::INTERLACE_JPEG ] = __( 'Imagick Default', 'warp-imagick' );
			}
			if ( defined( '\Imagick::INTERLACE_NO' ) ) {
				$values [ \Imagick::INTERLACE_NO ] = __( 'No Interlace', 'warp-imagick' );
			}
			if ( defined( '\Imagick::INTERLACE_PLANE' ) ) {
				$values [ \Imagick::INTERLACE_PLANE ] = __( 'Progressive (PLANE)', 'warp-imagick' );
			}

			$values [-1] = __( 'Auto Select *', 'warp-imagick' );

			return $values;

		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Settings Extended

		/** Fields Extended. With default values */
		protected function get_all_fields_extended() {
			return array(
				'configuration'  => array(),
				'plugin-version' => null,
			);
		}

		/** Redirect event to renderer class instance method */
		public function on_render_settings_page() {
			$this->renderer->render_page_subtitle();
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Plugin Manager

		/** Overridden to implement custom code on plugin activation requirements
		 *
		 * At this point, plugin PHP/WP requirements are already checked, maybe invalid (if configured).
		 *
		 * @access protected
		 * @param bool  $networkwide flag.
		 * @param array $settings - Plugin Settings (configured activate requirements).
		 * @return array empty or containing (exit) [$message/s] to abort activation
		 */
		protected function on_check_activate_requirements( $networkwide, $settings ) {
			$fail = array();

			if ( class_exists( '\Imagick' ) ) {
				$imagick_required = new \Imagick();
			}
			if ( ! isset( $imagick_required ) ) {
				$fail[] = 'PHP module Imagick (class) does not exists.';
			} elseif ( ! $imagick_required instanceof \Imagick ) {
				$fail[] = 'PHP module Imagick (class) is not available.';
			}
			if ( empty( $fail ) ) {
				$current_version = $imagick_required->getVersion();
				if ( is_array( $current_version ) ) {
					$module_version  = $current_version ['versionNumber'];
					$library_version = $current_version ['versionString'];

					$compare_version = '0.0.0';
					$partial_version = preg_split( '~[\s]+~', $library_version, 0, PREG_SPLIT_NO_EMPTY );
					foreach ( $partial_version as $part_version ) {
						if ( preg_match( '~^\d+\.\d+\.\d+~', $part_version ) ) {
							$compare_version = $part_version;
							break;
						}
					}
					if ( version_compare( $compare_version, '6.3.2', '<' ) ) {
						$fail[] = 'PHP Imagick module version is : ' . $module_version . '.';
						$fail[] = 'ImageMagick library version is : ' . $library_version . '.';
						$fail[] = 'Please upgrade module to ImageMagick library version 6.3.2 or newer.';
					}
				} elseif ( is_string( $current_version ) ) {

					$fail[] = 'ImageMagick library version is unknown.';
				} else {
					$fail[] = 'ImageMagick library version is not available.';
				}

				if ( empty( $fail ) && ! is_callable( array( $imagick_required, 'setImageProperty' ) ) ) {
					$fail[] = 'Imagick method "setImageProperty" is not available. Please update Imagick PHP module.';
				}

				$imagick_required->clear();
				$imagick_required->destroy();
				$imagick_required = null;

				if ( empty( $fail ) && ! in_array( _wp_image_editor_choose(), array( 'WP_Image_Editor_Imagick', 'Warp_Image_Editor_Imagick' ), true ) ) {
					$fail[] = 'Default image editor is not Imagick Editor but: "' . _wp_image_editor_choose() . '".';
					$fail[] = 'Please check if other plugin conflicts with WordPress Imagick Editor.';
				}
			}

			return $fail;
		}

		/** On plugin activation.
		 *
		 * Initialize (multi)site options.
		 *
		 * @access protected
		 * @param bool $networkwide flag.
		 * @return void
		 */
		protected function on_activate_plugin( $networkwide ) {

			$show_warning = false;
			$test_version = $this->get_option( 'plugin-version', null );
			$this_version = get_file_data( $this->get_plugin()->get_file(), array( 'version' => 'Version' ) )['version'];
			if ( $test_version !== $this_version ) {

				if ( null !== get_option( $this->get_plugin()->get_option_id(), null ) && null === $test_version ) {

					delete_option( $this->get_plugin()->get_option_id() );

					$show_warning = 'options cleared';
				}
			}

			$this->init_all_options(
				$networkwide, array( 'plugin-version' => $this_version )
			);

			if ( 'options cleared' === $show_warning ) {
				$transient_set = set_transient(
					$this->pageslug . '-update-settings',
					array(
						'notice' => 'Warp iMagick: New plugin version activated. Plugin settings is cleared to default values.',
						'error'  => 'Warp iMagick: Please configure and update settings to disable this warning message.',
					)
				);
			}

			parent::on_activate_plugin( $networkwide );
		}

		/** On Plugin uninstall, action handler */
		protected static function on_uninstall_plugin() {

			$instance = self::instance();
			if ( $instance->get_plugin()->get_option( 'remove-settings', true ) ) {
				self::remove_all_options( $instance->optionid );
			}
			parent::on_uninstall_plugin();

		}

		// phpcs:ignore
	# endregion

	}
}

