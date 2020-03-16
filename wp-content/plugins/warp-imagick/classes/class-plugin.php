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
use \ddur\Warp_iMagick\Base\Meta_Plugin;

if ( ! class_exists( __NAMESPACE__ . '\Plugin' ) ) {

	/** Plugin class */
	final class Plugin extends Meta_Plugin {

		// phpcs:ignore
	# region Properties

		/** File Path.
		 *
		 * Attachment file path.
		 *
		 * @var string $file_path stored.
		 */
		private $file_path = '';

		/** Files Dir.
		 *
		 * Stores files directory.
		 *
		 * @var string $sizes_dir stored.
		 */
		private $sizes_dir = '';

		/** Mime Type.
		 *
		 * Stores mime type.
		 *
		 * @var string $mime_type stored.
		 */
		private $mime_type = '';

		/** Transparency.
		 *
		 * Stores transparency.
		 *
		 * @var null|bool $transparency stored.
		 */
		private $transparency = null;

		/** Can Generate WebP.
		 *
		 * @var bool $can_generate_webp property.
		 */
		private $can_generate_webp = null;

		/** Sizes stored
		 *
		 * Sizes removed and stored here at
		 * intermediate_image_sizes_advanced and
		 * Sizes restored from here at
		 * wp_generate_attachment_metadata.
		 *
		 * Value is false or array of stored sizes.
		 *
		 * @var bool|array $sizes stored.
		 */
		private $sizes = false;

		/** Protected Files.
		 *
		 * Value shared with a 'wp_delete_file' filter.
		 *
		 * @var array $protected_files names or empty.
		 */
		private $protected_files = false;

		/** Delete Special files post/flag.
		 *
		 * @var int|bool contains post id or false.
		 */
		private $on_deleted_post_id = false;

		/** Delete Special files array.
		 *
		 * @var array contains set of Special files.
		 */
		private $on_deleted_post_files = array();

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Plugin Class Initialization.

		/** Plugin init. Called immediately after plugin class is constructed. */
		protected function init() {
			\add_action( 'init', array( $this, 'handle_wordpress_init' ) );
			if ( is_admin() ) {
				require __DIR__ . '/class-settings.php';
				$this->load_textdomain();
				Settings::once( $this );
			}
		}

		/** WordPress Init */
		public function handle_wordpress_init() {

			$this->add_template_endpoint();
			\add_action( 'wp', array( $this, 'handle_template_endpoint' ) );

			\add_action( 'admin_notices', array( $this, 'handle_admin_notices' ) );

			/** User has access to Upload/Media menu? */
			if ( function_exists( '\get_current_user' )
			&& function_exists( '\current_user_can' )
			&& \current_user_can( 'upload_files' ) ) {
				Lib::auto_hook( $this );
			}
		}

		/** On admin notices action */
		public function handle_admin_notices() {
			$update_settings = \get_transient( $this->get_slug() . '-update-settings' );
			if ( is_array( $update_settings ) ) {
				foreach ( $update_settings as $type => $message ) {
					switch ( $type ) {
						case 'error':
							self::error( $message );
							break;
						default:
							self::admin_notice( $message );
							break;
					}
				}
			}
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Regenerate Hooks

		/** On 'wp_image_editors' filter.
		 *
		 * Prepend derived editor to editors list.
		 *
		 * @param array $editors - image editors.
		 */
		public function on_wp_image_editors_filter( $editors ) {
			require_once __DIR__ . '/class-warp-image-editor-imagick.php';

			return array_merge( array( 'Warp_Image_Editor_Imagick' ), $editors );
		}

		/** On intermediate_image_sizes_advanced filter.
		 *
		 * Early priority (-100) will ignore RT plugin "skipping sizes".
		 *
		 * @param array $sizes - attachment sizes.
		 * @param array $metadata - attachment meta data.
		 */
		public function on_intermediate_image_sizes_advanced_minus100_filter( $sizes, $metadata ) {

			$this->sizes = false;

			if ( ! is_array( $sizes ) ) {
				return $sizes;
			}
			if ( 0 === count( $sizes ) ) {
				return $sizes;
			}
			if ( ! self::is_valid_metadata( $metadata ) ) {
				return $sizes;
			}

			$image_mime_type = wp_check_filetype( $metadata ['file'] );
			$image_mime_type = $image_mime_type['type'];
			switch ( $image_mime_type ) {
				case 'image/jpeg':
				case 'image/png':
					$this->mime_type = $image_mime_type;
					$this->sizes     = $sizes;
					$sizes           = array();
					break;
				default:
					$this->sizes = false;
					break;
			}
			return $sizes;
		}

		/** On wp_generate_attachment_metadata filter.
		 *
		 * Replace wp_generate_attachment_metadata() functionality for JPEG/PNG images between
		 * intermediate_image_sizes_advanced and wp_generate_attachment_metadata hooks
		 * Late priority (+100) will overwrite RT plugin returned sizes.
		 *
		 * @param array $metadata - attachment meta data.
		 * @param int   $attachment_id - number.
		 */
		public function on_wp_generate_attachment_metadata_100_filter( $metadata, $attachment_id ) {

			if ( ! $this->sizes ) {
				return $metadata;
			}

			if ( ! self::is_valid_metadata( $metadata ) ) {
				return $metadata;
			}

			if ( ! array_key_exists( 'sizes', $metadata ) || ! is_array( $metadata ['sizes'] ) ) {
				$metadata ['sizes'] = array();
			}

			if ( ! array_key_exists( 'image_meta', $metadata ) || ! is_array( $metadata ['image_meta'] ) ) {
				$metadata ['image_meta'] = array();
			}

			$backup_file_path = false;
			$backup_width     = 0;
			$backup_height    = 0;
			$backup_record    = array();

			$cloned_file_path = false;
			$cloned_width     = 0;
			$cloned_height    = 0;
			$cloned_record    = array();

			$max_image_width = false;
			$is_resized      = false;
			$stored_sizes    = array();

			$this->file_path = \trailingslashit( \wp_upload_dir() ['basedir'] ) . $metadata ['file'];
			$this->sizes_dir = \trailingslashit( dirname( $this->file_path ) );

			$old_metadata  = wp_get_attachment_metadata( $attachment_id, true );
			$is_regenerate = is_array( $old_metadata ) && array_key_exists( 'sizes', $old_metadata ) && is_array( $old_metadata ['sizes'] );

			if ( 'image/png' === $this->mime_type ) {

				$this->transparency = $this->can_generate_webp_clones() ? $this->get_image_file_transparency( $this->file_path, $this->mime_type ) : true;
			} else {
				$this->transparency = false;
			}

			if ( $is_regenerate ) {

				$stored_sizes = Lib::safe_key_value( $old_metadata, 'sizes', array() );

				if ( ! empty( Lib::safe_key_value( $old_metadata, array( 'image_meta', $this->get_over_width_size_name() ), array() ) ) ) {
					$stored_sizes [ $this->get_over_width_size_name() ] = Lib::safe_key_value(
						$old_metadata, array( 'image_meta', $this->get_over_width_size_name() ), array()
					);
				}

				if ( ! empty( Lib::safe_key_value( $old_metadata, array( 'image_meta', $this->get_same_width_size_name() ), array() ) ) ) {
					$stored_sizes [ $this->get_same_width_size_name() ] = Lib::safe_key_value(
						$old_metadata, array( 'image_meta', $this->get_same_width_size_name() ), array()
					);
				}

				$backup_sizes = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
				if ( is_array( $backup_sizes ) ) {
					foreach ( $backup_sizes as $bak_size_name => $bak_size_data ) {
						$stored_sizes [ $bak_size_name ] = $bak_size_data;
					}
				}

				$stored_sizes ['attachment'] = array( 'file' => pathinfo( $metadata ['file'], PATHINFO_BASENAME ) );

				$backup_record = Lib::safe_key_value(
					$old_metadata,
					array(
						'image_meta',
						$this->get_over_width_size_name(),
					), array(), false
				);
				if ( is_array( $backup_record ) ) {
					$backup_width     = $backup_record ['width'];
					$backup_height    = $backup_record ['height'];
					$backup_file_path = $this->sizes_dir . $backup_record ['file'];
					$metadata ['image_meta'][ $this->get_over_width_size_name() ] = $backup_record;
				}

				$cloned_record = Lib::safe_key_value(
					$old_metadata,
					array(
						'image_meta',
						$this->get_same_width_size_name(),
					), array(), false
				);
				if ( is_array( $cloned_record ) ) {
					$cloned_width     = $cloned_record ['width'];
					$cloned_height    = $cloned_record ['height'];
					$cloned_file_path = $this->sizes_dir . $cloned_record ['file'];
					$metadata ['image_meta'][ $this->get_same_width_size_name() ] = $cloned_record;
				}
			}

			if ( is_array( $this->sizes ) ) {

				$max_image_width = $this->get_max_image_width();

				if ( ! self::is_edited( $this->file_path ) ) {

					if ( $max_image_width ) {

						if ( ! $is_regenerate ) {

							if ( $max_image_width < $metadata ['width'] ) {

								if ( $this->do_over_width_size_backup() ) {

									if ( ! $backup_file_path || ! file_exists( $backup_file_path ) ) {

										$backup_file_path = self::add_suffix_to_file_name( $this->file_path, "-{$metadata['width']}x{$metadata['height']}" );

										$backup_file_path = $this->check_create_file_backup( $this->file_path, $backup_file_path );

										if ( $backup_file_path ) {

											chmod( $backup_file_path, 0444 );

											$backup_width  = $metadata ['width'];
											$backup_height = $metadata ['height'];
											$backup_record = array(
												'file'   => pathinfo( $backup_file_path, PATHINFO_BASENAME ),
												'width'  => $backup_width,
												'height' => $backup_height,
												'mime-type' => $this->mime_type,
											);

											$metadata ['image_meta'][ $this->get_over_width_size_name() ] = $backup_record;

										}
									}
								}
							}
						} else {
							false;
						}

						$check_is_backup_created = false;

						if ( $this->do_over_width_size_backup() ) {

							if ( ! $backup_file_path || ! file_exists( $backup_file_path ) ) {

								$backup_file_path = self::add_suffix_to_file_name( $this->file_path, "-{$metadata['width']}x{$metadata['height']}" );

								if ( ! file_exists( $backup_file_path ) ) {
									$check_is_backup_created = true;
								}
							}
						} else {

							$backup_file_path = false;
						}

						$geometry_resized = $this->check_resize_image_width( $this->file_path, $this->file_path, $max_image_width, $backup_file_path );

						if ( true === $check_is_backup_created && false !== $backup_file_path ) {

							if ( file_exists( $backup_file_path ) ) {

								chmod( $backup_file_path, 0444 );

								$backup_width  = $metadata ['width'];
								$backup_height = $metadata ['height'];
								$backup_record = array(
									'file'      => pathinfo( $backup_file_path, PATHINFO_BASENAME ),
									'width'     => $backup_width,
									'height'    => $backup_height,
									'mime-type' => $this->mime_type,
								);

								$metadata ['image_meta'][ $this->get_over_width_size_name() ] = $backup_record;
							}
						}

						if ( $geometry_resized ) {
							$is_resized          = true;
							$metadata ['width']  = $geometry_resized ['width'];
							$metadata ['height'] = $geometry_resized ['height'];
						}
					}

					if ( $this->do_generate_webp_clones() && false === $this->transparency ) {

						$this->webp_clone_image( $this->file_path, $this->mime_type, $this->transparency );
					}

					if ( $this->do_generate_same_width_size() ) {

						$this->sizes[ $this->get_same_width_size_name() ] = array(
							'width'  => $metadata ['width'],
							'height' => $metadata ['height'],
							'crop'   => false,
						);
					}
				}

				$editor = wp_get_image_editor( $this->file_path );

				if ( is_wp_error( $editor ) ) {
					if ( $is_regenerate ) {

						$metadata ['sizes'] = $old_metadata ['sizes'];
					} else {
						$metadata ['sizes'] = array();
					}
					Lib::error( 'Function wp_get_image_editor() returned an error: ' . $editor->get_error_message() );
					return $metadata;
				}

				if ( 'Warp_Image_Editor_Imagick' !== get_class( $editor ) ) {
					if ( $is_regenerate ) {

						$metadata ['sizes'] = $old_metadata ['sizes'];
					} else {
						$metadata ['sizes'] = array();
					}
					Lib::error( 'Wrong editor class?: ' . get_class( $editor ) );
					return $metadata;
				}

				$metadata['sizes'] = $editor->multi_resize( $this->sizes );

				if ( $backup_file_path ) {

					if ( file_exists( $backup_file_path ) ) {

						$metadata ['sizes'][ $this->get_over_width_size_name() ] = $backup_record;

						add_image_size( $this->get_over_width_size_name(), $backup_width, $backup_height );

						$this->protected_files[] = self::normalize_path_name( $backup_file_path );

					} else {

						unset( $metadata ['sizes'][ $this->get_over_width_size_name() ] );
						unset( $metadata ['image_meta'][ $this->get_over_width_size_name() ] );
					}
				}

				if ( ! self::is_edited( $this->file_path ) ) {

					if ( $this->do_generate_same_width_size() ) {

						if ( ! array_key_exists( $this->get_same_width_size_name(), $metadata ['sizes'] ) ) {

							$cloned_file_path = $this->sizes_dir . pathinfo( $metadata ['file'], PATHINFO_BASENAME );
							$cloned_file_path = self::add_suffix_to_file_name( $cloned_file_path, "-{$metadata ['width']}x{$metadata ['height']}" );
							if ( realpath( $cloned_file_path ) ) {

								$cloned_record = array(
									'file'      => pathinfo( $cloned_file_path, PATHINFO_BASENAME ),
									'width'     => $metadata ['width'],
									'height'    => $metadata ['height'],
									'mime-type' => $this->mime_type,
								);
								$metadata ['sizes'][ $this->get_same_width_size_name() ] = $cloned_record;
							} else {

								wp_delete_file( $this->get_webp_file_name( $cloned_file_path ) );

								$cloned_file_path = false;
								$cloned_record    = false;
							}
						} else {

							$cloned_record    = $metadata ['sizes'][ $this->get_same_width_size_name() ];
							$cloned_file_path = $this->sizes_dir . $cloned_record ['file'];
						}

						if ( realpath( $cloned_file_path ) && is_array( $cloned_record ) ) {

							$metadata ['image_meta'][ $this->get_same_width_size_name() ] = $cloned_record;

							add_image_size( $this->get_same_width_size_name(), $cloned_record ['width'], $cloned_record ['height'] );

							$this->protected_files[] = self::normalize_path_name( $cloned_file_path );

						} else {

							unset( $metadata ['sizes'][ $this->get_same_width_size_name() ] );
							unset( $metadata ['image_meta'][ $this->get_same_width_size_name() ] );

						}
					}
				}

				if ( ! self::is_edited( $this->file_path ) && $is_regenerate && $is_resized ) {

					$new_files = array();
					foreach ( $metadata['sizes'] as $size_data ) {
						$new_files [ $size_data['file'] ] = null;
					}

					foreach ( $old_metadata['sizes'] as $size_name => $size_data ) {
						if ( ! array_key_exists( $size_data['file'], $new_files )
						&& array_key_exists( $size_name, $metadata['sizes'] ) ) {

							$del_size_file = self::normalize_path_name( $this->sizes_dir . $size_data['file'] );
							if ( trim( $del_size_file ) && ! in_array( $del_file, $this->protected_files, true ) ) {

								wp_delete_file( $del_size_file );

								$del_webp_file = self::normalize_path_name( $this->sizes_dir . self::get_webp_file_name( $size_data['file'] ) );
								if ( trim( $del_webp_file ) ) {
									wp_delete_file( $del_webp_file );
								}
							}
						}
					}
				}

				add_filter( 'wp_delete_file', array( $this, 'wp_delete_file_protect' ), -100 );

			}

			if ( $is_regenerate ) {

				if ( $this->do_generate_webp_clones() && false === $this->transparency ) {

					foreach ( $stored_sizes as $webp_size_add ) {
						$original_file = $this->sizes_dir . Lib::safe_key_value( $webp_size_add, 'file', '' );
						if ( is_readable( $original_file ) ) {
							$webp_file_add = self::get_webp_file_name( $original_file );
							if ( ! file_exists( $webp_file_add ) ) {
								$this->webp_clone_image( $original_file, $this->mime_type, $this->transparency );
							}
						}
					}
				} else {

					foreach ( $stored_sizes as $webp_size_del ) {
						$webp_file_del = Lib::safe_key_value( $webp_size_del, 'file', '', false );
						if ( $webp_file_del ) {
							$webp_file_del = $this->sizes_dir . self::get_webp_file_name( $webp_file_del );
							if ( file_exists( $webp_file_del ) ) {
								wp_delete_file( $webp_file_del );
							}
						}
					}
				}

				$del_cloned_file = $this->sizes_dir . pathinfo( $metadata ['file'], PATHINFO_BASENAME );
				$del_cloned_file = self::add_suffix_to_file_name( $del_cloned_file, "-{$metadata ['width']}x{$metadata ['height']}" );

				if ( ! $this->do_generate_same_width_size() || ! file_exists( $del_cloned_file ) ) {
					if ( file_exists( $del_cloned_file ) ) {
						wp_delete_file( $del_cloned_file );
					}
					$del_cloned_webp = $this->get_webp_file_name( $del_cloned_file );
					if ( file_exists( $del_cloned_webp ) ) {
						wp_delete_file( $del_cloned_webp );
					}
				}
			}

			return $metadata;
		}

		/** Protect special image files/sizes on 'wp_delete_file' filter.
		 *
		 * Prevent other plugins/hooks to delete special/protected image files.
		 *
		 * @param string $file - name of file trying to delete.
		 */
		public function wp_delete_file_protect( $file ) {

			$file = self::normalize_path_name( $file );

			if ( trim( $file ) && in_array( $file, $this->protected_files, true ) ) {
				$file = '';
			}
			return $file;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Delete Post/Attachment Hooks

		/** On 'delete_attachment' action.
		 *
		 * Make sure all additional images/files are deleted when attachment deleted.
		 *
		 * @param int $post_id - attachment id.
		 */
		public function on_delete_attachment_action( $post_id ) {

			$imgname = get_attached_file( $post_id );
			if ( ! file_exists( $imgname ) ) {
				return;
			}

			$dirname = dirname( $imgname );
			$imgname = pathinfo( $imgname, PATHINFO_BASENAME );

			$metadata = wp_get_attachment_metadata( $post_id );

			$delete_extra_files = array();

			$special_files = array();
			$special_files[ Lib::safe_key_value( $metadata, array( 'image_meta', $this->get_over_width_size_name(), 'file' ), '' ) ] = null;
			$special_files[ Lib::safe_key_value( $metadata, array( 'image_meta', $this->get_same_width_size_name(), 'file' ), '' ) ] = null;
			$special_files[ Lib::safe_key_value( $metadata, array( 'sizes', $this->get_over_width_size_name(), 'file' ), '' ) ]      = null;
			$special_files[ Lib::safe_key_value( $metadata, array( 'sizes', $this->get_same_width_size_name(), 'file' ), '' ) ]      = null;

			foreach ( $special_files as $special_file => $null ) {
				if ( trim( $special_file ) ) {
					$delete_extra_files[ $special_file ] = null;
				}
			}

			foreach ( $delete_extra_files as $delete_extra_file => $null ) {
				if ( trim( $delete_extra_file ) ) {
					if ( trim( $dirname ) && trim( $filename ) ) {
						$delete_file = self::normalize_path_name( \trailingslashit( $dirname ) . $filename );

						$this->on_deleted_post_files[ $delete_file ] = null;
					}
				}
			}

			if ( ! empty( $this->on_deleted_post_files ) ) {
				$this->on_deleted_post_id = $post_id;
				add_action( 'deleted_post', array( $this, 'delete_attached_extra_image_files' ) );
			}

			add_action(
				'wp_delete_file', function( $path ) {
					if ( trim( $path ) && is_readable( $path ) ) {
						$mime_type = wp_check_filetype( $path );
						$mime_type = $mime_type['type'];
						switch ( $mime_type ) {
							case 'image/jpeg':
							case 'image/png':
								$delete_webp = self::get_webp_file_name( $path );
								if ( file_exists( $delete_webp ) ) {
									unlink( $delete_webp );
								}
								break;
						}
					}
					return $path;
				}
			);
		}

		/** Conditional 'deleted_post' action handler.
		 *
		 * Delete additional files after 'delete_attachment' and after attached files deleted.
		 * Activated only if additional files detected.
		 *
		 * @param int $post_id - attachment id.
		 */
		public function delete_attached_extra_image_files( $post_id ) {
			if ( $this->on_deleted_post_id === $post_id ) {
				foreach ( $this->on_deleted_post_files as $image_file => $null ) {
					if ( file_exists( $image_file ) ) {
						wp_delete_file( $image_file );
					}
				}
				$this->on_deleted_post_id    = false;
				$this->on_deleted_post_files = array();
			}
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Helper functions

		/** Return max image width (if enabled by option) */
		public function get_max_image_width() {
			if ( $this->get_option( 'image-max-width-enabled', false ) ) {
				$max_image_width = $this->get_option( 'image-max-width-pixels', 0 );

				if ( $max_image_width >= self::max_width_min_val() && $max_image_width <= self::max_width_max_val() ) {
					return $max_image_width;
				}
			}
			return false;
		}

		/** Get same-width size name (for optimized copy of original image). */
		public function get_same_width_size_name() {
			return $this->get_slug() . '-same-width';
		}

		/** Generate same-width $size? */
		public function do_generate_same_width_size() {
			return false && $this->get_option( 'extra-size-same-width', false );
		}

		/** Get over-width $size name (for downsized/backup). */
		public function get_over_width_size_name() {
			return $this->get_slug() . '-over-width';
		}

		/** Generate over-width $size? */
		public function do_over_width_size_backup() {
			return false && $this->get_option( 'image-max-width-backup', false );
		}

		/** Generate webp clones? */
		public function do_generate_webp_clones() {
			return $this->can_generate_webp_clones()
			&& $this->get_option( 'webp-images-create', false );
		}

		/** Can Generate webp clones? */
		public function can_generate_webp_clones() {
			if ( null === $this->can_generate_webp ) {
				$functions = array(
					'\\imagewebp',
					'\\imagecreatefromjpeg',
					'\\imagecreatefrompng',
					'\\imageistruecolor',
					'\\imagepalettetotruecolor',
					'\\imagealphablending',
					'\\imagesavealpha',
				);

				$this->can_generate_webp = true;
				foreach ( $functions as $function ) {
					if ( ! function_exists( $function ) ) {
						$this->can_generate_webp = false;

						break;
					}
				}
			}
			return $this->can_generate_webp;
		}

		/** Get transparency from file
		 *
		 * @param string $file_path to check.
		 * @param string $mime_type of image.
		 */
		public function get_image_file_transparency( $file_path, $mime_type ) {

			$is_transparent_image_file = null;
			if ( is_readable( $file_path ) && 'image/png' === $mime_type ) {

				try {
					$im_image = new \Imagick( $file_path );
					if ( $im_image instanceof \Imagick ) {
						$is_transparent_image_file = self::is_transparent( $im_image );
						$im_image->clear();
						$im_image->destroy();
						$im_image = null;
					}
				} catch ( Exception $e ) {
					Lib::error( 'Exception: ', $e->getMessage() );
				}
			}
			return $is_transparent_image_file;
		}

		/** Create webp clone.
		 *
		 * @param string $source_image to clone.
		 * @param string $mime_type of source image.
		 * @param bool   $transparency of image. Default null.
		 */
		public function webp_clone_image( $source_image, $mime_type = '', $transparency = null ) {
			if ( ! $this->can_generate_webp_clones() ) {
				return;
			}
			if ( ! is_readable( $source_image ) ) {
				return;
			}
			if ( ! is_string( $mime_type ) || ! trim( $mime_type ) ) {
				$mime_type = wp_check_filetype( $source_image );
				$mime_type = $mime_type['type'];
			}

			$webp_quality = 80;

			$gd_image = false;
			switch ( $mime_type ) {
				case 'image/jpeg':
					$webp_quality = $this->get_option( 'jpeg-compression-quality', 80 );
					$gd_image     = \imagecreatefromjpeg( $source_image );
					break;
				case 'image/png':
					if ( false === $transparency
					|| ( null === $transparency
						&& false === $this->get_image_file_transparency( $source_image, $mime_type ) ) ) {
						$gd_image = \imagecreatefrompng( $source_image );
						if ( $gd_image ) {
							imagealphablending( $gd_image, true );
							imagesavealpha( $gd_image, false );
							if ( ! imageistruecolor( $gd_image ) ) {
								imagepalettetotruecolor( $gd_image );
							}
						}
					}
					break;
			}

			$webp_file = self::get_webp_file_name( $source_image );
			if ( $gd_image ) {
				if ( \imagewebp( $gd_image, $webp_file, $webp_quality ) ) {
					if ( file_exists( $webp_file ) ) {

						if ( filesize( $webp_file ) % 2 === 1 ) {
							// phpcs:ignore
							file_put_contents( $webp_file, "\0", FILE_APPEND );
						}

						$stat  = stat( dirname( $webp_file ) );
						$perms = $stat['mode'] & 0000666;
						chmod( $webp_file, $perms );
					} else {
						Lib::debug( 'imagewebp: file not created' );
					}
				} else {
					Lib::debug( 'imagewebp: failed' );
					if ( file_exists( $webp_file ) ) {
						wp_delete_file( $webp_file );
					}
				}
			} else {

				if ( file_exists( $webp_file ) ) {
					wp_delete_file( $webp_file );
				}
			}
		}
		/** Create file backup.
		 *
		 * @param string $source_file_path file name to backup.
		 * @param string $target_file_path backup file name.
		 * @param bool   $overwrite flag, false by default.
		 *
		 * @return bool|string $target_file_path if file backed up else false.
		 */
		private function check_create_file_backup( $source_file_path, $target_file_path, $overwrite = false ) {
			if ( is_string( $target_file_path ) && '' !== $target_file_path && file_exists( $source_file_path )
			&& ( true === $overwrite || ! file_exists( $target_file_path ) ) ) {
				if ( true === copy( $source_file_path, $target_file_path ) ) {
					if ( file_exists( $target_file_path ) ) {
						return $target_file_path;
					}
					Lib::error( 'Target check failed for: ' . $target_file_path );
				} else {
					Lib::error( 'Target write failed for: ' . $source_file_path );
				}
			}
			return false;
		}

		/** Resize image (maybe).
		 *
		 * @param string $source_file_path image file name to read.
		 * @param string $target_file_path image file name to write.
		 * @param int    $max_image_width to reduce target to.
		 * @param string $backup_file_path file name to backup.
		 *
		 * @return array|bool  geometry if image geometry changed, else false.
		 */
		private function check_resize_image_width( $source_file_path, $target_file_path, $max_image_width, $backup_file_path = false ) {

			$success = false;

			$resize_w = false;
			$target_w = false;

			$backup_geometry = self::get_geometry( $backup_file_path );
			$source_geometry = self::get_geometry( $source_file_path );

			if ( is_array( $backup_geometry ) ) {

				if ( $max_image_width > $backup_geometry ['width'] ) {

					$resize_w = $backup_geometry ['width'];
				} else {

					$resize_w = $max_image_width;
				}

				$source_file_path = $backup_file_path;
				$backup_file_path = false;

			} elseif ( is_array( $source_geometry ) ) {

				if ( $max_image_width < $source_geometry ['width'] ) {
					$resize_w = $max_image_width;
				}
			}

			if ( false !== $resize_w ) {

				$source_w = $source_geometry ['width'];
				$source_h = $source_geometry ['height'];

				if ( file_exists( $target_file_path ) ) {

					$target_geometry = self::get_geometry( $target_file_path );
					if ( is_array( $target_geometry ) ) {
						$target_w = $target_geometry ['width'];
					}
				}

				if ( $target_w !== $resize_w ) {

					$start = microtime( true );

					$this->check_create_file_backup( $source_file_path, $backup_file_path );

					try {

						$imagick = new \Imagick( $source_file_path );
						if ( $imagick instanceof \Imagick ) {

							$imagick->scaleImage( $resize_w, 0 );

							if ( true === $imagick->writeImage( $target_file_path ) ) {
								$success = $imagick->getImageGeometry();
							}

							$imagick->clear();
							$imagick->destroy();
							$imagick = null;
						}
					} catch ( Exception $e ) {
						Lib::error( 'Exception: ', $e->getMessage() );
					}
				}
			}
			return $success;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Static Helper functions

		/** Is transparent image?
		 *
		 * @param object $im_image to check for transparency.
		 */
		public static function is_transparent( $im_image ) {
			$is_transparent_image_obj = null;
			if ( $im_image instanceof \Imagick ) {
				try {
					$imgalpha = $im_image->getImageAlphaChannel();
					if ( $imgalpha ) {
						$img_mean = $im_image->getImageChannelMean( \Imagick::CHANNEL_ALPHA );
						if ( is_array( $img_mean ) ) {
							if ( ! in_array( $img_mean['mean'], array( 0.0, 1.0 ), true )
							&& ! in_array( $img_mean['standardDeviation'], array( 0.0, 1.0 ), true ) ) {
								$is_transparent_image_obj = true;
							} else {
								$is_transparent_image_obj = false;
							}
						}
					} else {
						$is_transparent_image_obj = false;
					}
				} catch ( Exception $e ) {
					Lib::error( 'Exception: ', $e->getMessage() );
				}
			}
			return $is_transparent_image_obj;
		}

		/** Check if metadata valid and contains required keys.
		 *
		 * @param array $metadata to check.
		 */
		private static function is_valid_metadata( $metadata ) {

			if ( ! is_array( $metadata ) ) {
				return false;
			}
			if ( ! array_key_exists( 'file', $metadata ) ) {
				return false;
			}
			if ( ! array_key_exists( 'width', $metadata ) ) {
				return false;
			}
			if ( ! array_key_exists( 'height', $metadata ) ) {
				return false;
			}
			return true;

		}

		/** Replace filename extension
		 *
		 * @param string $file_name_path to replace extension.
		 * @param string $extension to replace with.
		 */
		public static function replace_file_name_extension( $file_name_path, $extension ) {
			$pathinfo = pathinfo( $file_name_path );
			if ( array_key_exists( 'filename', $pathinfo ) ) {
				return ( array_key_exists( 'dirname', $pathinfo ) && '.' !== $pathinfo ['dirname'] ? trailingslashit( $pathinfo ['dirname'] ) : '' )
					. $pathinfo ['filename']
					. '.' . $extension;
			}
			return false;
		}

		/** Append filename extension
		 *
		 * @param string $file_name_path to append extension.
		 * @param string $extension to replace with.
		 */
		public static function append_file_name_extension( $file_name_path, $extension ) {
			$pathinfo = pathinfo( $file_name_path );
			if ( array_key_exists( 'basename', $pathinfo ) ) {
				return ( array_key_exists( 'dirname', $pathinfo ) && '.' !== $pathinfo ['dirname'] ? trailingslashit( $pathinfo ['dirname'] ) : '' )
					. $pathinfo ['basename']
					. '.' . $extension;
			}
			return false;
		}

		/** Get webp filename.
		 *
		 * @param string $source_file_name to convert to webp name.
		 */
		public static function get_webp_file_name( $source_file_name ) {
			return self::append_file_name_extension( $source_file_name, 'webp' );
		}

		/** Add suffix to filename
		 *
		 * @param string $file_name_path to receive suffix.
		 * @param string $suffix to append at the end of $file_name_path filename.
		 */
		public static function add_suffix_to_file_name( $file_name_path, $suffix ) {
			$pathinfo = pathinfo( $file_name_path );
			if ( array_key_exists( 'filename', $pathinfo ) ) {
				return ( array_key_exists( 'dirname', $pathinfo ) && '.' !== $pathinfo ['dirname'] ? trailingslashit( $pathinfo ['dirname'] ) : '' )
					. $pathinfo ['filename'] . $suffix
					. ( array_key_exists( 'extension', $pathinfo ) ? '.' . $pathinfo ['extension'] : '' );
			}
			return false;
		}

		/** Get file-image-geometry.
		 *
		 * @param string $image_file_name to get geometry from.
		 */
		public static function get_geometry( $image_file_name ) {

			$image_geometry = function_exists( '\\getimagesize' ) && file_exists( $image_file_name ) ? \getimagesize( $image_file_name ) : false;
			if ( is_array( $image_geometry )
			&& array_key_exists( 0, $image_geometry )
			&& array_key_exists( 1, $image_geometry ) ) {
				$image_w        = intval( $image_geometry [0] );
				$image_h        = intval( $image_geometry [1] );
				$image_geometry = array(
					'width'  => $image_w,
					'height' => $image_h,
				);
			} else {
				$image_geometry = false;
			}
			return $image_geometry;
		}

		/** Is file edited?
		 *
		 * Use file name to find out.
		 *
		 * @param string $image_file_path to check.
		 */
		public static function is_edited( $image_file_path ) {
			if ( preg_match( '/-e[0-9]{13}$/', pathinfo( $image_file_path, PATHINFO_FILENAME ) ) ) {
				return true;
			}
			return false;
		}

		/** Normalize file path/name.
		 *
		 * @param string $file_path_name - File path and name.
		 *
		 * @return string normalized path-name if file exists, else empty string.
		 */
		public static function normalize_path_name( $file_path_name ) {
			return realpath( wp_normalize_path( trim( '' . $file_path_name ) ) );
		}

		/** Get default jpeg quality. */
		public static function jpeg_quality_default() {
			return 50;
		}

		/** Get minimal jpeg quality. */
		public static function jpeg_quality_min_val() {
			return 30;
		}

		/** Get maximal jpeg quality. */
		public static function jpeg_quality_max_val() {
			return 85;
		}

		/** Get default max colors. */
		public static function png_max_colors_default() {
			return 1024;
		}

		/** Get minimal max colors. */
		public static function png_max_colors_min_val() {
			return 16;
		}

		/** Get maximal max colors. */
		public static function png_max_colors_max_val() {
			return 1024;
		}

		/** Get maximal max colors. */
		public static function png_max_colors_palette() {
			return 256;
		}

		/** Get default width limit. */
		public static function max_width_default() {
			return 1600;
		}

		/** Get minimal width limit. */
		public static function max_width_min_val() {
			return 768;
		}

		/** Get maximal width limit. */
		public static function max_width_max_val() {
			return 3000;
		}

		// phpcs:ignore
	# endregion

		// phpcs:ignore
	# region Attachment Test Template

		/** Add Template Endpoint */
		public function add_template_endpoint() {
			\add_rewrite_endpoint( $this->get_slug(), EP_ALL );
		}

		/** Handle template endpoint. */
		public function handle_template_endpoint() {

			if ( $this->is_raw_image_template_request() ) {

				\remove_all_actions( 'template_redirect' );
				\add_action(
					'template_redirect', function() {
						\remove_all_filters( 'template_include' );
						\add_filter(
							'template_include', function( $template ) {

								$raw_image_template = $this->get_path() . '/templates/raw-image-template.php';
								if ( is_file( $raw_image_template ) ) {
									header( $this->get_slug() . ': template' );
									$template = $raw_image_template;
								} else {
									Lib::error( 'Template file not found: ' . $raw_image_template );
								}
								return $template;
							}
						);
						return false;
					}
				);
			}
		}

		/** Is Template Request */
		private function is_raw_image_template_request() {

			$my_wp_query = $GLOBALS['wp_the_query'];

			if ( ! isset( $my_wp_query->query_vars[ $this->get_slug() ] ) ) {
				return false;
			}

			if ( ! in_array( $my_wp_query->query_vars[ $this->get_slug() ], array( 'raw', 'all', 'full', 'webp' ), true ) ) {
				return false;
			}

			if ( ! isset( $my_wp_query->post->post_type ) || 'attachment' !== $my_wp_query->post->post_type ) {
				return false;
			}

			if ( ! isset( $my_wp_query->post->post_mime_type ) || ! Lib::starts_with( $my_wp_query->post->post_mime_type, 'image/' ) ) {
				return false;
			}

			return true;
		}

		// phpcs:ignore
	# endregion


		// phpcs:ignore
	# region Imagick Validation Arrays

		/** All Known & Defined Imagick Compression Types */
		public static function get_imagick_commpression_types() {

			$values = array();

			if ( defined( '\\Imagick::COMPRESSION_UNDEFINED' ) ) {
				$values [ \Imagick::COMPRESSION_UNDEFINED ] = __( 'UNDEFINED', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_NO' ) ) {
				$values [ \Imagick::COMPRESSION_NO ] = __( 'DISABLED', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_BZIP' ) ) {
				$values [ \Imagick::COMPRESSION_BZIP ] = __( 'BZIP', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_DXT1' ) ) {
				$values [ \Imagick::COMPRESSION_DXT1 ] = __( 'DXT1', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_DXT3' ) ) {
				$values [ \Imagick::COMPRESSION_DXT3 ] = __( 'DXT3', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_DXT5' ) ) {
				$values [ \Imagick::COMPRESSION_DXT5 ] = __( 'DXT5', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_FAX' ) ) {
				$values [ \Imagick::COMPRESSION_FAX ] = __( 'FAX', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_GROUP4' ) ) {
				$values [ \Imagick::COMPRESSION_GROUP4 ] = __( 'GROUP4', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_JPEG' ) ) {
				$values [ \Imagick::COMPRESSION_JPEG ] = __( 'JPEG', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_JPEG2000' ) ) {
				$values [ \Imagick::COMPRESSION_JPEG2000 ] = __( 'JPEG2000', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_LOSSLESSJPEG' ) ) {
				$values [ \Imagick::COMPRESSION_LOSSLESSJPEG ] = __( 'LOSSLESSJPEG', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_LZW' ) ) {
				$values [ \Imagick::COMPRESSION_LZW ] = __( 'LZW', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_RLE' ) ) {
				$values [ \Imagick::COMPRESSION_RLE ] = __( 'RLE', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_ZIP' ) ) {
				$values [ \Imagick::COMPRESSION_ZIP ] = __( 'ZIP', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_ZIPS' ) ) {
				$values [ \Imagick::COMPRESSION_ZIPS ] = __( 'ZIPS', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_PIZ' ) ) {
				$values [ \Imagick::COMPRESSION_PIZ ] = __( 'PIZ', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_PXR24' ) ) {
				$values [ \Imagick::COMPRESSION_PXR24 ] = __( 'PXR24', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_B44' ) ) {
				$values [ \Imagick::COMPRESSION_B44 ] = __( 'B44', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_B44A' ) ) {
				$values [ \Imagick::COMPRESSION_B44A ] = __( 'B44A', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_LZMA' ) ) {
				$values [ \Imagick::COMPRESSION_LZMA ] = __( 'LZMA', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_JBIG1' ) ) {
				$values [ \Imagick::COMPRESSION_JBIG1 ] = __( 'JBIG1', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COMPRESSION_JBIG2' ) ) {
				$values [ \Imagick::COMPRESSION_JBIG2 ] = __( 'JBIG2', 'warp-imagick' );
			}

			return $values;
		}

		/** All Known & Defined Imagick Interlace Types */
		public static function get_imagick_interlace_types() {

			$values = array();

			if ( defined( '\\Imagick::INTERLACE_UNDEFINED' ) ) {
				$values [ \Imagick::INTERLACE_UNDEFINED ] = __( 'UNDEFINED', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::INTERLACE_NO' ) ) {
				$values [ \Imagick::INTERLACE_NO ] = __( 'DISABLED', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::INTERLACE_LINE' ) ) {
				$values [ \Imagick::INTERLACE_LINE ] = __( 'LINE', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::INTERLACE_PLANE' ) ) {
				$values [ \Imagick::INTERLACE_PLANE ] = __( 'PLANE', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::INTERLACE_PARTITION' ) ) {
				$values [ \Imagick::INTERLACE_PARTITION ] = __( 'PARTITION', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::INTERLACE_GIF' ) ) {
				$values [ \Imagick::INTERLACE_GIF ] = __( 'GIF', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::INTERLACE_JPEG' ) ) {
				$values [ \Imagick::INTERLACE_JPEG ] = __( 'JPEG', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::INTERLACE_PNG' ) ) {
				$values [ \Imagick::INTERLACE_PNG ] = __( 'PNG', 'warp-imagick' );
			}

			return $values;

		}

		/** All Known & Defined Imagick Colorspaces */
		public static function get_imagick_colorspaces() {

			$values = array();

			if ( defined( '\\Imagick::COLORSPACE_UNDEFINED' ) ) {
				$values [ \Imagick::COLORSPACE_UNDEFINED ] = __( 'UNDEFINED', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_RGB' ) ) {
				$values [ \Imagick::COLORSPACE_RGB ] = __( 'RGB', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_GRAY' ) ) {
				$values [ \Imagick::COLORSPACE_GRAY ] = __( 'GRAY', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_TRANSPARENT' ) ) {
				$values [ \Imagick::COLORSPACE_TRANSPARENT ] = __( 'TRANSPARENT', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_OHTA' ) ) {
				$values [ \Imagick::COLORSPACE_OHTA ] = __( 'OHTA', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_LAB' ) ) {
				$values [ \Imagick::COLORSPACE_LAB ] = __( 'LAB', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_XYZ' ) ) {
				$values [ \Imagick::COLORSPACE_XYZ ] = __( 'XYZ', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_YCBCR' ) ) {
				$values [ \Imagick::COLORSPACE_YCBCR ] = __( 'YCBCR', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_YCC' ) ) {
				$values [ \Imagick::COLORSPACE_YCC ] = __( 'YCC', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_YIQ' ) ) {
				$values [ \Imagick::COLORSPACE_YIQ ] = __( 'YIQ', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_YPBPR' ) ) {
				$values [ \Imagick::COLORSPACE_YPBPR ] = __( 'YPBPR', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_YUV' ) ) {
				$values [ \Imagick::COLORSPACE_YUV ] = __( 'YUV', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_CMYK' ) ) {
				$values [ \Imagick::COLORSPACE_CMYK ] = __( 'CMYK', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_SRGB' ) ) {
				$values [ \Imagick::COLORSPACE_SRGB ] = __( 'SRGB - Default', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_HSB' ) ) {
				$values [ \Imagick::COLORSPACE_HSB ] = __( 'HSB', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_HSL' ) ) {
				$values [ \Imagick::COLORSPACE_HSL ] = __( 'HSL', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_HWB' ) ) {
				$values [ \Imagick::COLORSPACE_HWB ] = __( 'HWB', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_REC601LUMA' ) ) {
				$values [ \Imagick::COLORSPACE_REC601LUMA ] = __( 'REC601LUMA', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_REC709LUMA' ) ) {
				$values [ \Imagick::COLORSPACE_REC709LUMA ] = __( 'REC709LUMA', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_LOG' ) ) {
				$values [ \Imagick::COLORSPACE_LOG ] = __( 'LOG', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_CMY' ) ) {
				$values [ \Imagick::COLORSPACE_CMY ] = __( 'CMY', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_LUV' ) ) {
				$values [ \Imagick::COLORSPACE_LUV ] = __( 'LUV', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_HCL' ) ) {
				$values [ \Imagick::COLORSPACE_HCL ] = __( 'HCL', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_LCH' ) ) {
				$values [ \Imagick::COLORSPACE_LCH ] = __( 'LCH', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_LMS' ) ) {
				$values [ \Imagick::COLORSPACE_LMS ] = __( 'LMS', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_LCHAB' ) ) {
				$values [ \Imagick::COLORSPACE_LCHAB ] = __( 'LCHAB', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_LCHUV' ) ) {
				$values [ \Imagick::COLORSPACE_LCHUV ] = __( 'LCHUV', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_SCRGB' ) ) {
				$values [ \Imagick::COLORSPACE_SCRGB ] = __( 'SCRGB', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_HSI' ) ) {
				$values [ \Imagick::COLORSPACE_HSI ] = __( 'HSI', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_HSV' ) ) {
				$values [ \Imagick::COLORSPACE_HSV ] = __( 'HSV', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_HCLP' ) ) {
				$values [ \Imagick::COLORSPACE_HCLP ] = __( 'HCLP', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_YDBDR' ) ) {
				$values [ \Imagick::COLORSPACE_YDBDR ] = __( 'YDBDR', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_REC601YCBCR' ) ) {
				$values [ \Imagick::COLORSPACE_REC601YCBCR ] = __( 'REC601YCBCR', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_REC709YCBCR' ) ) {
				$values [ \Imagick::COLORSPACE_REC709YCBCR ] = __( 'REC709YCBCR', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::COLORSPACE_XYY' ) ) {
				$values [ \Imagick::COLORSPACE_XYY ] = __( 'XYY', 'warp-imagick' );
			}

			return $values;

		}

		/** All Known & Defined Imagick Interlace Types */
		public static function get_imagick_imgtypes() {

			$values = array();

			if ( defined( '\\Imagick::IMGTYPE_UNDEFINED' ) ) {
				$values [ \Imagick::IMGTYPE_UNDEFINED ] = __( 'UNDEFINED', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::IMGTYPE_BILEVEL' ) ) {
				$values [ \Imagick::IMGTYPE_BILEVEL ] = __( 'BILEVEL', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::IMGTYPE_GRAYSCALE' ) ) {
				$values [ \Imagick::IMGTYPE_GRAYSCALE ] = __( 'GRAYSCALE', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::IMGTYPE_GRAYSCALEMATTE' ) ) {
				$values [ \Imagick::IMGTYPE_GRAYSCALEMATTE ] = __( 'GRAYSCALEMATTE', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::IMGTYPE_PALETTE' ) ) {
				$values [ \Imagick::IMGTYPE_PALETTE ] = __( 'PALETTE', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::IMGTYPE_PALETTEMATTE' ) ) {
				$values [ \Imagick::IMGTYPE_PALETTEMATTE ] = __( 'PALETTEMATTE', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::IMGTYPE_TRUECOLOR' ) ) {
				$values [ \Imagick::IMGTYPE_TRUECOLOR ] = __( 'TRUECOLOR', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::IMGTYPE_TRUECOLORMATTE' ) ) {
				$values [ \Imagick::IMGTYPE_TRUECOLORMATTE ] = __( 'TRUECOLORMATTE', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::IMGTYPE_COLORSEPARATION' ) ) {
				$values [ \Imagick::IMGTYPE_COLORSEPARATION ] = __( 'COLORSEPARATION', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::IMGTYPE_COLORSEPARATIONMATTE' ) ) {
				$values [ \Imagick::IMGTYPE_COLORSEPARATIONMATTE ] = __( 'COLORSEPARATIONMATTE', 'warp-imagick' );
			}
			if ( defined( '\\Imagick::IMGTYPE_OPTIMIZE' ) ) {
				$values [ \Imagick::IMGTYPE_OPTIMIZE ] = __( 'OPTIMIZE', 'warp-imagick' );
			}

			return $values;

		}

		// phpcs:ignore
	# endregion

	}
}
