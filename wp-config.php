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

//OK here we go.
//### Begin Axelrad Custom Config ###//
define('APP_DIR', __DIR__);

// ** ftp for dev is dev@axelradclinic.info / Ic(18o%1QrLf
//
// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', "axelradc_dev");

/** MySQL database username */
define( 'DB_USER', 'axelradc_dev' );

/** MySQL database password */
define( 'DB_PASSWORD', '][P7=goy$2}d' );

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
define( 'AUTH_KEY',         'mczelpovktuegu4vywqc9ikggrvr23nezzpnps8ufntbcotfdgupduypmlfiugx2' );
define( 'SECURE_AUTH_KEY',  'pjkth4obvzrckzfze8fa4yxn7grpmbw7atfwspcqylgd8yvpgdxzyakwoik9lryo' );
define( 'LOGGED_IN_KEY',    'rdufajofhg89ctqonuzoxcxuy3lokuymnekf7fqojcwn9ii7oir45g1y5abypd4t' );
define( 'NONCE_KEY',        'ceeoqzsxcpkdooujcqu8iadgudawiyl6z5ilcxcowupgec5nztgyfnfrvacygljn' );
define( 'AUTH_SALT',        'x7pcmzmpnzg4frzmwfs0zbqepd1docnczht4yeo8uom8ljx0edszf5yfnowxkvmi' );
define( 'SECURE_AUTH_SALT', 'i9io30pewom2qtmduahtlcsa6iqru2k2neeqpfjepi1frmbmc0o87pigwvl1yr9u' );
define( 'LOGGED_IN_SALT',   'ebzvkildycjbhufkuus2zhhp2df0roilkwcndd3sie8opygs1t76fp1akxnxtzku' );
define( 'NONCE_SALT',       '3ug9njvgkdarj2tim8z1nbmogknpfsh0s8m51ntqry6ulpwwvves7jm7lyjqzmfg' );

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp43_';

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
define( 'WP_MEMORY_LIMIT', '128M' );
define( 'WP_MAX_MEMORY_LIMIT', '256M' );
define( 'WP_AUTO_UPDATE_CORE', false );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );

@include_once('/var/lib/sec/wp-settings.php'); // Added by SiteGround WordPress management system

