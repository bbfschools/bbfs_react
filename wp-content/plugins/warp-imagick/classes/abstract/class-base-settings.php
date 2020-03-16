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

namespace ddur\Warp_iMagick\Base;

defined( 'ABSPATH' ) || die( -1 );

use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use \ddur\Warp_iMagick\Base\Plugin\v1\Abstract_Settings;
use \ddur\Warp_iMagick\Base\Plugin\v1\Abstract_Plugin;

if ( ! class_exists( __NAMESPACE__ . '\Base_Settings' ) ) {

	/** Settings base class. */
	abstract class Base_Settings extends Abstract_Settings {

		// phpcs:ignore
	# region Construction and Instance

		/** Static singleton object.
		 *
		 * @var object $me contains this class singleton.
		 */
		private static $me = null;

		/** Once constructor.
		 * Static Singleton Class Constructor.
		 *
		 * @param object $plugin instance.
		 * @return mixed this object singleton or null on error.
		 */
		public static function once( $plugin = null ) {
			if ( null === self::$me && null !== $plugin && $plugin instanceof Abstract_Plugin ) {
				self::$me = new static( $plugin );
			}
			if ( null === self::$me ) {
				if ( null === $plugin ) {
					Lib::error( 'Missing $plugin argument' );
				} else {
					Lib::error( 'Invalid $plugin argument' );
				}
			} else {
				self::$me->init();
			}
			return self::$me;
		}

		/** Class initialization. */
		protected function init() {}

		/** Class instance.
		 *
		 * Static access to Class instance.
		 */
		public static function instance() {
			return self::$me;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Helper functions.

		/** Temporary options field.
		 *
		 * @var array $temp_options
		 */
		private $temp_options = null;

		/** Initialize plugin options if not initialized (do not yet exists in database).
		 *
		 * @param array $setup Custom fields to initialize.
		 */
		public function init_options( $setup = array() ) {
			$this->temp_options = get_option( $this->plugin->get_option_id(), null );
			if ( null === $this->temp_options ) {

				$this->temp_options = $this->get_all_fields_defaults();

				if ( is_array( $setup ) ) {
					foreach ( $setup as $option => $value ) {
						$this->temp_options [ $option ] = $value;
					}
				}
				$this->temp_options = $this->validate_form_input( $this->temp_options );
				update_option( $this->plugin->get_option_id(), $this->temp_options, true );
				$this->temp_options = true;
			}
		}

		/** Initialize plugin (multisite) options.
		 *
		 * Use in 'on_activate_plugin' method
		 *
		 * @access protected
		 * @param bool  $networkwide flag.
		 * @param array $setup - extended/custom fields to initialize.
		 * @return void
		 */
		protected function init_all_options( $networkwide, $setup = array() ) {
			if ( is_multisite() && $networkwide ) {
				$sites = \get_sites();
				foreach ( $sites as $site ) {
					\switch_to_blog( $site->blog_id );
					$this->init_options( $setup );
				}
				\restore_current_blog();
			} else {
				$this->init_options( $setup );
			}
		}

		/** Remove all (&multisite) options.
		 *
		 * Use in 'on_uninstall_plugin' method
		 *
		 * @access protected
		 * @param string $option_id - Option API ID.
		 * @return void
		 */
		protected static function remove_all_options( $option_id ) {
			if ( is_multisite() ) {
				delete_site_option( $option_id );
				$sites = get_sites();
				foreach ( $sites as $site ) {
					switch_to_blog( $site->blog_id );
					delete_option( $option_id );
				}
				restore_current_blog();
			} else {
				delete_option( $option_id );
			}

		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Plugin Dynamic Menu or Submenu Position

		/** Initialize plugin dynamic configuration
		 *
		 * @param array $values - reference to options values.
		 */
		protected function set_dynamic_configuration( &$values ) {

			if ( ! array_key_exists( 'configuration', $values ) ) {
				$values ['configuration'] = array();
			}
			if ( ! array_key_exists( 'menu', $values ['configuration'] ) ) {
				$values ['configuration']['menu'] = array();
			}
			if ( ! array_key_exists( 'parent-slug', $values ['configuration']['menu'] ) ) {
				$values ['configuration']['menu']['parent-slug'] = null;
				$values ['configuration']['menu']['position']    = null;
			}

		}

		/** Plugin dynamic menu position
		 *
		 * @param array $values - reference to options values.
		 */
		protected function set_dynamic_menu_position( &$values ) {

			$this->set_dynamic_configuration( $values );

			$config_menu_parent = Lib::safe_key_value( $this->settings, array( 'menu', 'parent-slug' ), '' );

			if ( ! self::is_valid_menu_parent_slug( $config_menu_parent ) ) {

				$config_menu_parent = abs( Lib::safe_key_value( $this->settings, array( 'menu', 'position' ), 0 ) );
			}

			$menu_input = $values ['menu-parent-slug'];

			if ( '' === $menu_input
			|| ( ! self::is_valid_menu_parent_slug( $menu_input )
			&& ! is_numeric( $menu_input ) ) ) {

				$menu_input = $config_menu_parent;
			}

			if ( is_numeric( $menu_input ) ) {

				$menu_input = abs( intval( $menu_input ) );

				$values ['configuration']['menu']['parent-slug'] = '';
				$values ['configuration']['menu']['position']    = $menu_input;

				$admin_page = '';

			} else {
				$values ['configuration']['menu']['parent-slug'] = $menu_input;
				$values ['configuration']['menu']['position']    = 0;

				$admin_page = $menu_input;
			}

			$_REQUEST ['_wp_http_referer'] = add_query_arg(
				'page',
				$this->pageslug,
				admin_url() . ( trim( $admin_page ) ? $admin_page : 'admin.php' )
			);

			if ( $menu_input === $config_menu_parent ) {
				$menu_input = '';
			}

			$values ['menu-parent-slug'] = $menu_input;

			count( get_settings_errors() ) || $this->add_settings_update_notice();

		}

		// phpcs:ignore
	# endregion

	}

}

