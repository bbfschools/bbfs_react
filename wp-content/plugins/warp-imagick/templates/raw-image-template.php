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

/**
 * Custom Template for Media Attachment Images
 *
 * @package Warp iMagick
 * @version 1.0
 */
namespace ddur\Warp_iMagick;

use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;

/** Get array of media attachment images
 *
 * @param int $id of attachment.
 * @return array|false [$size-name => $file-path]
 */
function get_attachment_image_files( $id ) {

	$img_meta = \wp_get_attachment_metadata( $id );
	if ( ! is_array( $img_meta ) ) {
		return false;
	}
	if ( ! isset( $img_meta ['file'] ) ) {
		return false;
	}
	if ( ! is_string( $img_meta ['file'] ) ) {
		return false;
	}
	if ( ! isset( $img_meta ['sizes'] ) ) {
		return false;
	}
	if ( ! is_array( $img_meta ['sizes'] ) ) {
		return false;
	}

	$my_wp_query = $GLOBALS['wp_the_query'];
	$query_value = strtolower( $my_wp_query->query_vars[ Plugin::slug() ] );

	$main_img_path = \trailingslashit( \wp_upload_dir() ['basedir'] ) . $img_meta ['file'];
	$base_img_path = \trailingslashit( dirname( $main_img_path ) );

	$server      = wp_unslash( $_SERVER );
	$webp_accept = strpos( $server['HTTP_ACCEPT'], 'image/webp' ) !== false;

	$distinct_files = array();

	if ( in_array( $query_value, array( 'raw', 'all', 'webp' ), true ) ) {
		foreach ( $img_meta ['sizes'] as $img_meta_size => $img_meta_data ) {
			if ( in_array( $query_value, array( 'raw', 'all' ), true ) ) {
				$distinct_files [ $base_img_path . $img_meta_data ['file'] ] = $img_meta_size;
			}
			if ( in_array( $query_value, array( 'all', 'webp' ), true ) ) {
				if ( $webp_accept ) {
					$distinct_files [ $base_img_path . Plugin::append_file_name_extension( $img_meta_data ['file'], 'webp' ) ] = $img_meta_size . '-webp';
				}
			}
		}
	}
	if ( in_array( $query_value, array( 'full', 'all' ), true ) ) {
		$distinct_files[ $main_img_path ] = 'full';
	}
	if ( in_array( $query_value, array( 'full', 'all', 'webp' ), true ) ) {
		if ( $webp_accept ) {
			$distinct_files [ Plugin::append_file_name_extension( $main_img_path, 'webp' ) ] = 'full-webp';
		}
	}

	$files = array();
	foreach ( $distinct_files as $path => $size ) {
		if ( file_exists( $path ) && Lib::starts_with( mime_content_type( $path ), 'image/' ) ) {
			$files [ $size ] = $path;
		}
	}
	return $files;
}

/** Return <img> elements */
function get_img_html_elements() {

	if ( ! is_callable( '\\getimagesize' ) ) {
		Lib::error( 'Function not callable: \getimagesize' );
		return '';
	}

	$my_wp_query = $GLOBALS['wp_the_query'];
	$img_files   = get_attachment_image_files( $my_wp_query->post->ID );

	if ( ! is_array( $img_files ) || count( $img_files ) === 0 ) {
		return '';
	}

	$html      = '';
	$root_path = wp_normalize_path( untrailingslashit( ABSPATH ) );
	foreach ( $img_files as $img_file_size => $image_path ) {
		$img_file_data = \getimagesize( $image_path );
		if ( is_array( $img_file_data ) && count( $img_file_data ) > 1 ) {
			$image_path = wp_normalize_path( $image_path );
			if ( Lib::starts_with( $image_path, $root_path ) ) {
				$url    = substr( $image_path, strlen( $root_path ) );
				$width  = $img_file_data [0];
				$height = $img_file_data [1];
				$html  .= "<img data-size='$img_file_size' src='$url' width='$width' height='$height'>" . PHP_EOL;
			}
		}
	}
	return $html;
}
?><!doctype html><html><body><?php Lib::echo_html( get_img_html_elements() ); ?></body></html>
