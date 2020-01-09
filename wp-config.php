<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'wp_react_bbfs' );

/** MySQL database username */
define( 'DB_USER', 'wprbbfs' );

/** MySQL database password */
define( 'DB_PASSWORD', 'wprbbfs123!@#' );

/** MySQL hostname */
define( 'DB_HOST', 'localhost' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'DMcg uyr&E{H/G7xz2VbV{)T+NV>j |ni;Fnr*B!=$&Ry4u[ /U>Otl,jg{o3VEr' );
define( 'SECURE_AUTH_KEY',  'o0B=UD[^-0*[Q&1v7bI/x:lVYhJ;7|H/[@?U8$LcG?+0JMoiQ!:|~-o@%s`j25?N' );
define( 'LOGGED_IN_KEY',    '<5:xpuc!U<Qe|ArtJ<,2t?.<$+]tt$+pFJRq!!+l}3!_O?53B{gB]Dr.Uy>@}_|S' );
define( 'NONCE_KEY',        'U*N[^ bboZ>2}PX*e> +zTcn+*a9aD7c7l?6]i<C#g!SyqAaf)%f[(2%46CTi0NW' );
define( 'AUTH_SALT',        'y1@n+s-%8);Tb<P0n8+Yz&Tz3cZhuQ]>m_4wkP5icr#(2L2v[7UShrX*bmy4b0u<' );
define( 'SECURE_AUTH_SALT', ' w{4FXL[IqdRV#OW3uBv8{`Qf)HoyF=z/XR16UB<!i(gx%D_WqP>AA_;kjLYj,Sm' );
define( 'LOGGED_IN_SALT',   '[}]yNsN;<P<S]j*WIuQIYw%g7#gzXat8*LOlJ4pvb7ijM`|s#ProWVdB`}a+0r&A' );
define( 'NONCE_SALT',       '_hf,yqW!2h%@vM[E]n-tk ,_B(JsB*x`Oje77!H@|/*~^P$YP720M[5kRn15Qlk#' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );

