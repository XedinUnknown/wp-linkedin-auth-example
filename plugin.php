<?php
/**
 * REST API Import
 *
 * @package RestApiImport
 * @wordpress-plugin
 *
 * Plugin Name: REST API Import
 * Description: Imports content from REST APIs.
 * Author: Anton Ukhanev
 * Version: 0.1.0
 * License: GPL-3.0+
 * Text Domain: rest-api-import
 * Domain Path: /languages
 */

declare( strict_types=1 );

namespace XedinUnknown\RestApiImport;

/**
 * Retrieves the plugin singleton.
 *
 * @since 0.1.0
 * @return Plugin The plugin singleton instance.
 */
function plugin(): Plugin {
	static $instance = null;

	$autoload_file = __DIR__ . '/vendor/autoload.php';
	if ( file_exists( $autoload_file ) ) {
		require $autoload_file;
	}

	if ( is_null( $instance ) ) {
		$base_path        = __FILE__;
		$base_dir         = dirname( $base_path );
		$base_url         = plugins_url( '', $base_path );
		$services_factory = require_once "$base_dir/services.php";
		$services         = $services_factory( $base_dir, $base_url );
		$container        = new DI_Container( $services );

		$instance = new Plugin( $container );
	}

	return $instance;
}

plugin()->run();

