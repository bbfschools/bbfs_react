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
 * @wordpress-plugin
 * Plugin Name: Warp iMagick - Image Compressor
 * Plugin URI:  https://wordpress.org/plugins/warp-imagick/
 * Description: Warp drive site images with iMagick library. Optimize JPEG/PNG and compress to WebP.
 * Author:      Dragan Đurić
 * Author URI:  https://github.com/ddur
 * License:     GPLv2
 * Version:     1.0.5
 * Text Domain: warp-imagick
 * Domain Path: /languages
 *
 * This copyright notice, source files, licenses and other included
 * materials are protected by U.S. and international copyright laws.
 * You are not allowed to remove or modify this or any other
 * copyright notice contained within this software package.
 */

defined( 'ABSPATH' ) || die( -1 );

if ( version_compare( phpversion(), '5.6', '>=' ) ) {
	require_once __DIR__ . '/plugin.php';
} else {
	add_action( 'init', 'warp_imagick_newer_php_version_required' );
}

/** Requires newer PHP version */
function warp_imagick_newer_php_version_required() {
	if ( current_user_can( 'activate_plugins' ) ) {
		add_action( 'admin_init', 'warp_imagick_newer_php_version_required_deactivate' );
		add_action( 'admin_notices', 'warp_imagick_newer_php_version_required_admin_notice_plugin_deactivated' );
	} else {
		add_action( 'admin_notices', 'warp_imagick_newer_php_version_required_admin_notice_please_deactivate' );
	}
}

/** Deactivate plugin */
function warp_imagick_newer_php_version_required_deactivate() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}

/** Deactivated notice */
function warp_imagick_newer_php_version_required_admin_notice_plugin_deactivated() {
	echo '<div class="notice notice-error is-dismissible"><p><strong>Warp iMagick</strong> plugin has been <strong>deactivated</strong>, please upgrade PHP to version 5.6 or greater!</p></div>';
	unset( $_GET ['activate'] );
}

/** Deactivate notice */
function warp_imagick_newer_php_version_required_admin_notice_please_deactivate() {
	echo '<div class="notice notice-error is-dismissible"><p>Please deactivate <strong>Warp iMagick</strong> plugin or upgrade PHP to version 5.6 or greater!</p></div>';
}
