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

defined( 'ABSPATH' ) || die( -1 );

use \ddur\Warp_iMagick\Base\Plugin\v1\Lib;
use \ddur\Warp_iMagick\Plugin;

if ( ! class_exists( 'Warp_Image_Editor_Imagick' ) ) {

	if ( ! class_exists( '\WP_Image_Editor_Imagick' ) ) {
		require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-gd.php';
		require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';
	}

	require_once __DIR__ . '/class-warp-wp-image-editor-imagick.php';

	/** Derived Image Optimizing Editor Class
	 *
	 * Optimization, Optimization control & Plugin integration.
	 */
	final class Warp_Image_Editor_Imagick extends Warp_WP_Image_Editor_Imagick {

		/** Size name flag.
		 *
		 * Value is false or specific size name.
		 *
		 * @var string|bool $same_size_flag flag, false or size name.
		 */
		protected $same_size_flag = false;

		/** Multi Resize - Generate Thumbnails.
		 *
		 * Allows generating equal geometry thumbnail.
		 * Only for by plugin defined size-identifier.
		 *
		 * @param array $sizes to resize to thumbnails.
		 * @return array
		 */
		public function multi_resize( $sizes ) {

			$plugin = Plugin::instance();
			if ( is_object( $plugin ) ) {

				unset( $sizes [ $plugin->get_over_width_size_name() ] );

				switch ( $this->mime_type ) {
					case 'image/jpeg':
					case 'image/png':
						$this->same_size_flag = $plugin->get_same_width_size_name();
						break;
					default:
						$this->same_size_flag = false;
				}
			}
			return parent::multi_resize( $sizes );
		}

		/** Thumbnail Image - Optimize Thumbnail Image.
		 *
		 * @param int    $dst_w       The destination width.
		 * @param int    $dst_h       The destination height.
		 * @param string $filter_name Optional. The Imagick filter to use when resizing. Default 'FILTER_TRIANGLE'.
		 * @param bool   $strip_meta  Optional. Strip all profiles, excluding color profiles, from the image. Default true.
		 * @return bool|WP_Error
		 */
		protected function thumbnail_image( $dst_w, $dst_h, $filter_name = 'FILTER_TRIANGLE', $strip_meta = true ) {

			$plugin = Plugin::instance();

			if ( is_object( $plugin ) ) {

				try {

					if ( 'image/png' === $this->mime_type ) {

						switch ( $plugin->get_option( 'png-strip-meta', 0 ) ) {
							case 0:
								break;
							case 1:
								$strip_meta = false;
								break;
							case 2:
								$strip_meta = true;
								break;
							case 3:
								if ( is_callable( array( $this->image, 'stripImage' ) ) ) {
									$this->image->stripImage();
									$strip_meta = false;
								} else {
									$strip_meta = true;
								}
								break;
							default:
								break;
						}
					} elseif ( 'image/jpeg' === $this->mime_type ) {

						switch ( $plugin->get_option( 'jpeg-strip-meta', 0 ) ) {
							case 0:
								break;
							case 1:
								$strip_meta = false;
								break;
							case 2:
								$strip_meta = true;
								break;
							case 3:
								if ( is_callable( array( $this->image, 'stripImage' ) ) ) {

									$this->image->stripImage();
									$strip_meta = false;
								} else {
									$strip_meta = true;
								}
								break;
							default:
								break;
						}
					}
				} catch ( Exception $e ) {

					Lib::error( 'Exception: ', $e->getMessage() );
					return new WP_Error( 'warp_optimize_error', $e->getMessage() );

				}
			}

			$thumb_result = parent::thumbnail_image( $dst_w, $dst_h, $filter_name, $strip_meta );
			if ( is_wp_error( $thumb_result ) ) {
				return $thumb_result;
			}

			if ( is_object( $plugin ) ) {

				if ( 'image/png' === $this->mime_type ) {

					try {

						if ( true === $plugin->get_option( 'png-reduce-colors-enable', false ) ) {

							$colors_max = $plugin->get_option( 'png-reduce-max-colors-count', Plugin::png_max_colors_max_val() );

							$colors_max = Plugin::png_max_colors_min_val() <= $colors_max && Plugin::png_max_colors_max_val() >= $colors_max ? $colors_max : Plugin::png_max_colors_max_val();

							$dither_png = $plugin->get_option( 'png-reduce-colors-dither', false );

							$do_reduce_colors = true;
							$colors_reduced   = false;

							$current_colors = $this->image->getImageColors();
							if ( 0 >= $current_colors || $colors_max >= $current_colors ) {
								$do_reduce_colors = false;
								$colors_reduced   = true;
							}

							$current_imgtype = $this->image->getImageType();
							if ( ! array_key_exists( $current_imgtype, Plugin::get_imagick_imgtypes() ) ) {
								$current_imgtype = false;
							} else {

								switch ( $current_imgtype ) {

									case 2:
									case 3:
									case 4:
									case 5:
									case 6:
									case 7:
									case 8:
									case 9:
										break;

									default:
										$current_imgtype  = false;
										$do_reduce_colors = false;
										$colors_reduced   = true;
								}
							}

							if ( $current_imgtype && true === $do_reduce_colors && false === $colors_reduced ) {

								$alphachannel_on   = Plugin::is_transparent( $this->image );
								$saved_restore_img = false;
								if ( $alphachannel_on && $dither_png ) {

									if ( $colors_max <= Plugin::png_max_colors_palette() ) {
										$dither_png = false;
									} else {
										$saved_restore_img = $this->image->getImage();
										switch ( $current_imgtype ) {

											case 4:
											case 5:
												break;
											default:
												if ( 4 < $this->image->getImageChannelDepth( \Imagick::CHANNEL_ALPHA ) ) {
													$this->image->setImageChannelDepth( \Imagick::CHANNEL_ALPHA, 4 );
												}
												break;
										}
									}
								}

								if ( is_callable( array( $this->image, 'quantizeImage' ) ) ) {

									$colors_reduced = $this->image->quantizeImage( $colors_max, $this->image->getImageColorspace(), 0, $dither_png, false );
								}

								if ( $saved_restore_img instanceof \Imagick ) {

									if ( \Imagick::IMGTYPE_PALETTEMATTE === $this->image->getImageType() ) {
										$this->image = $saved_restore_img->getImage();

										$dither_png = false;

										if ( is_callable( array( $this->image, 'quantizeImage' ) ) ) {
											$colors_reduced = $this->image->quantizeImage( $colors_max, $this->image->getImageColorspace(), 0, $dither_png, false );
										}
									}

									$saved_restore_img->clear();
									$saved_restore_img->destroy();
									$saved_restore_img = false;
								}
							}
						}

						$png_sizes = array();
						$png_sizes [ strlen( $this->image->getImageBlob() ) ] = array( 1, 5, 'WordPress Default' );
						$this->image->setOption( 'png:compression-filter', '0' );
						$this->image->setOption( 'png:compression-strategy', '0' );
						$png_sizes [ strlen( $this->image->getImageBlob() ) ] = array( 0, 0, 'Imagick Default' );

						ksort( $png_sizes );
						$this->image->setOption( 'png:compression-strategy', '' . reset( $png_sizes ) [0] );
						$this->image->setOption( 'png:compression-filter', '' . reset( $png_sizes ) [1] );

					} catch ( Exception $e ) {

						Lib::error( 'Exception: ', $e->getMessage() );
						return new WP_Error( 'warp_optimize_error', $e->getMessage() );

					}
				}

				if ( 'image/jpeg' === $this->mime_type ) {

					try {

						if ( is_callable( array( $this->image, 'transformImageColorspace' ) ) ) {

							$jpeg_colorspace = $plugin->get_option( 'jpeg-colorspace', 0 );
							0 === $jpeg_colorspace || $this->image->transformImageColorspace( $jpeg_colorspace );
						}

						$jpeg_compression_type = $plugin->get_option( 'jpeg-compression-type', 0 );
						0 === $jpeg_compression_type || $this->image->setImageCompression( $jpeg_compression_type );

						$jpeg_compression_quality = $plugin->get_option( 'jpeg-compression-quality', 0 );
						0 === $jpeg_compression_quality || $this->image->setImageCompressionQuality( $jpeg_compression_quality );

						if ( is_callable( array( $this->image, 'setImageProperty' ) ) ) {
							$jpeg_sampling_factor = $plugin->get_option( 'jpeg-sampling-factor', '' );
							'' === $jpeg_sampling_factor || $this->image->setImageProperty( 'jpeg:sampling-factor', $jpeg_sampling_factor );

						}

						if ( is_callable( array( $this->image, 'setInterlaceScheme' ) ) ) {
							$jpeg_interlace_scheme = $plugin->get_option( 'jpeg-interlace-scheme', 0 );
							switch ( $jpeg_interlace_scheme ) {
								case 0:
									break;

								case -1:
									if ( defined( '\Imagick::INTERLACE_NO' ) && defined( '\Imagick::INTERLACE_JPEG' ) ) {
										$this->image->setInterlaceScheme( \Imagick::INTERLACE_NO );
										$size_nointerlace = strlen( $this->image->getImageBlob() );
										$this->image->setInterlaceScheme( \Imagick::INTERLACE_JPEG );
										$size_progressive = strlen( $this->image->getImageBlob() );
										if ( $size_nointerlace < $size_progressive ) {
											$this->image->setInterlaceScheme( \Imagick::INTERLACE_NO );
										}
									}
									break;
								default:
									$this->image->setInterlaceScheme( $jpeg_interlace_scheme );
									break;
							}
						}
					} catch ( Exception $e ) {

						Lib::error( 'Exception: ', $e->getMessage() );
						return new WP_Error( 'warp_optimize_error', $e->getMessage() );

					}
				}
			}
		}
		/** Parent method - modified.
		 *
		 * Generate webp for each successfully saved file.
		 *
		 * @param Imagick $image image.
		 * @param string  $filename to save as.
		 * @param string  $mime_type to save as.
		 * @return array|WP_Error
		 */
		protected function _save( $image, $filename = null, $mime_type = null ) { // phpcs:ignore

			$saved = parent::_save( $image, $filename, $mime_type );

			if ( ! is_wp_error( $saved ) && is_array( $saved ) ) {

				$plugin = Plugin::instance();
				if ( is_object( $plugin ) && $plugin->do_generate_webp_clones() ) {
					$saved_file_path = $saved ['path'];
					$saved_mime_type = isset( $saved ['mime_type'] ) && is_string( $saved ['mime_type'] ) && trim( $saved ['mime_type'] ) ? $saved ['mime_type'] : $this->mime_type;
					switch ( $saved_mime_type ) {
						case 'image/jpeg':
							$plugin->webp_clone_image( $saved_file_path, $saved_mime_type );
							break;
						case 'image/png':
							if ( false === Plugin::is_transparent( $this->image ) ) {
								$plugin->webp_clone_image( $saved_file_path, $saved_mime_type, false );
							}
							break;
						default:
							break;
					}
				}
			}
			return $saved;

		}
	}
}
