<?php
/**
 * Plugin Name: WordPress Metadata AI Generator
 * Plugin URI:  https://example.com/
 * Description: Generate WordPress excerpts and taxonomy descriptions with an OpenAI-compatible API.
 * Version:     0.1.2
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      Circle Crop
 * Text Domain: wordpress-metadata-aigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WMAIGEN_VERSION', '0.1.2' );
define( 'WMAIGEN_PLUGIN_FILE', __FILE__ );
define( 'WMAIGEN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WMAIGEN_VIEWS_DIR', WMAIGEN_PLUGIN_DIR . 'views/' );

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'WMAIGEN\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( $prefix ) );
		$relative_path  = str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class );
		$file_path      = WMAIGEN_PLUGIN_DIR . 'src/' . $relative_path . '.php';

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		\WMAIGEN\Plugin::bootstrap();
	}
);
