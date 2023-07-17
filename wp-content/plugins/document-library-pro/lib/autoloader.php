<?php

namespace Barn2\DLP_Lib;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


spl_autoload_register( [ __NAMESPACE__ . '\\Autoloader', 'load' ] );

/**
 * The Barn2 library autoloader.
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @link      https://barn2.com
 * @copyright Barn2 Plugins
 */
final class Autoloader {

    const SOURCE_PATHS = [
        'Barn2\\Lib'          => __DIR__,
        'WPTRT\\AdminNotices' => __DIR__ . '/vendor/admin-notices/src'
    ];

    public static function load( $class ) {
        $src_path = false;

        foreach ( self::SOURCE_PATHS as $namespace => $path ) {
            if ( 0 === strpos( $class, $namespace ) ) {
                $src_path = $path;
                break;
            }
        }

        // Bail if the class is not in our namespace.
        if ( ! $src_path ) {
            return;
        }

        // Strip namespace from class name.
        $class = str_replace( $namespace, '', $class );

        // Build the filename - realpath returns false if the file doesn't exist.
        $file = realpath( $src_path . '/' . str_replace( '\\', '/', $class ) . '.php' );

        // If the file exists for the class name, load it.
        if ( $file ) {
            include_once $file;
        }
    }

}
