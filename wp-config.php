<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'dbkgbndqwmhapk' );

/** Database username */
define( 'DB_USER', 'ufqlzpyd83ey9' );

/** Database password */
define( 'DB_PASSWORD', 'hgilkppm6keq' );

/** Database hostname */
define( 'DB_HOST', '127.0.0.1' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '|=$Damu}.wc!:o@L{A21%QD44Kb/8d_D%AA.U3GTYukcBZo_1s/NEPGVO$=1H8TR' );
define( 'SECURE_AUTH_KEY',   'bW>8?24IrMgy]nZJ#VVpj*D>iY+3u67+e.h{y;_i+! GUV|grZ@A$Kqp#P,u aA?' );
define( 'LOGGED_IN_KEY',     'Bbxid6MbG02,!sVk(5~6yxNXE Y0faZEQ&gvp uY:uP43^KG&e)z<:>Q1{XI8:Iq' );
define( 'NONCE_KEY',         'dzza]0vu~H7zUi:X@59-]zvAT&|A6p*3P0(N0g|MTKC--=@BE7}cOadn?LYM(!)Z' );
define( 'AUTH_SALT',         's.=S#zE1Fl:MJOB/X96hJ|R=0J[DZ1VZ&Zb&c%]`{!0cRw^0_(;j%=5!=vqlddZf' );
define( 'SECURE_AUTH_SALT',  '.S},3~+xlKdWOM1IgP,lqo;7J9)We<,tzUB&nW@k9|-0 Pi<M}qB[ixWX=lk,<?K' );
define( 'LOGGED_IN_SALT',    '!mY:osOD795 IeBlk;~E6^2>~cN$gl_MF}q !OM^4.QdE3v<eP1k+gtEm_c?/u|p' );
define( 'NONCE_SALT',        'D[cir. z/7lkxao;zHo-11hV<hqlW-PNn=gFFG@EG+G!fB6DLuB7Mg^y#nGH-{g/' );
define( 'WP_CACHE_KEY_SALT', 'pU!?r?;UmITib>1hEgVD-1`c|RJrHxjxqYXTO^u(=xRfSOU})aw~Uk#;u`<*;O2Y' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'thx_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );


/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
@include_once('/var/lib/sec/wp-settings-pre.php'); // Added by SiteGround WordPress management system
require_once ABSPATH . 'wp-settings.php';
@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system
