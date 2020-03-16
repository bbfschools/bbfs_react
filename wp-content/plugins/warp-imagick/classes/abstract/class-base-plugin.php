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
use \ddur\Warp_iMagick\Base\Plugin\v1\Abstract_Plugin;

if ( ! class_exists( __NAMESPACE__ . '\Base_Plugin' ) ) {

	/** Plugin base class. */
	abstract class Base_Plugin extends Abstract_Plugin {

		/** Static singleton object.
		 *
		 * @var object $me contains this class singleton.
		 */
		private static $me = null;

		/** Once constructor.
		 * Static Singleton Class Constructor.
		 *
		 * @param string $file - Magic __FILE__ constant from plugin-entry file.
		 * @return mixed this object singleton or null on error.
		 */
		public static function once( $file = null ) {
			if ( null === self::$me && null !== $file && file_exists( $file ) ) {
				self::$me = new static( $file );
			}
			if ( null === self::$me ) {
				if ( null === $file ) {
					Lib::error( 'Missing $file argument' );
				} else {
					Lib::error( 'Invalid $file argument' );
				}
			} else {
				self::$me->init();
			}
			return self::$me;
		}

		/** Class initialization. */
		protected function init() {}

		/** Static access to Class instance. */
		public static function instance() {
			return self::$me;
		}

		/** Static Plugin Slug.
		 *
		 * Static access to plugin slug.
		 */
		public static function slug() {
			if ( is_object( self::$me ) ) {
				return self::$me->get_slug();
			}
			return 'undefined';
		}

		/** Run-time error-admin-notice handler.
		 *
		 * @param string $message to report.
		 */
		public static function error( $message = '' ) {
			if ( is_string( $message ) && trim( $message ) ) {
				self::admin_notice( $message, 'notice notice-error is-dismissible' );
			}
		}

		/** Run-time admin-notice handler.
		 *
		 * @param string $message to report.
		 * @param string $class css class.
		 */
		public static function admin_notice( $message = '', $class = 'notice notice-info is-dismissible' ) {
			if ( $message && $class ) {
				echo '<div class="' . esc_attr( $class ) . '"><p style="white-space:pre"><strong>' . esc_html( $message ) . '</strong></p></div>';
			}
		}

	}
}
