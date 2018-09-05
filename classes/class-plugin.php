<?php
/**
 * Plugin class.
 *
 * @package RestApiImport
 */

declare( strict_types=1 );

namespace XedinUnknown\RestApiImport;

use Exception;
use WP_Error;

/**
 * The main class of the plugin.
 *
 * Asset management, API registration, and some utility functions.
 *
 * @since 0.1
 */
class Plugin {

	/**
	 * The container of services and configuration used by the plugin.
	 *
	 * @since 0.1
	 *
	 * @var DI_Container
	 */
	protected $config;

	/**
	 * ID of the page which supports the user table.
	 *
	 * @since 0.1
	 *
	 * @var string|null
	 */
	protected $page_id;

	/**
	 * Plugin constructor.
	 *
	 * @since 0.1
	 *
	 * @param DI_Container $config The configuration of this plugin.
	 */
	public function __construct( DI_Container $config ) {
		$this->config = $config;
	}

	/**
	 * Runs the plugin.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function run() {
		$this->hook();
	}

	/**
	 * Adds plugin hooks
	 *
	 * @since 0.1
	 * @return void
	 */
	protected function hook() {
        add_action(
            'init', function () {
                $this->page_id = add_submenu_page(
                    $this->get_config( 'page_parent_slug' ),
                    __( 'REST API Import', 'rest-api-import' ),
                    __( 'REST API Import', 'rest-api-import' ),
                    $this->get_config( 'page_cap' ),
                    $this->get_config( 'page_slug' ),
                    function () {
                        $result = $this->handle_auth();
                        $auth_key = $this->get_linkedin_auth_token();
                        $error_message = is_wp_error($result) ? $result->get_error_message() : null;

                        echo $this->get_config('block_factory')('import-settings-page.php', [ // phpcs:ignore WordPress.Security.EscapeOutput
                            'linkedin_auth_button_id'           => $this->get_config('linkedin_auth_button_id'),
                            'is_linked'                         => !empty($auth_key),
                            'is_authorized'                     => $error_message ? false : isset($_GET['code']),
                            'error_message'                     => $error_message,
                            'is_linking'                        => $this->is_linking(),
                            'url_to_link'                       => $this->get_config('url_to_link'),
                            'url_to_unlink'                     => $this->get_config('url_to_unlink'),
                        ]);
                    }
                );
            }
        );
        add_action(
            'init', function () {
                $this->register_assets();
            }
        );
        add_action(
            'admin_enqueue_scripts', function ( string $slug ) {
                $screen = get_current_screen();
                if ( $screen && $screen->id === $this->page_id ) {
                    $this->enqueue_assets();
                }
            }
        );
        add_action(
            'admin_head', function () {
                $screen = get_current_screen();
                if ( $screen && $screen->id === $this->page_id ) {
                    echo $this->get_config('block_factory')('linkedin-sdk.php', ['vars' => [
                        'api_key'          => $this->get_config('linkedin_api_key'),
                        'onLoad'           => '',
                        'authorize'        => $this->get_config('linkedin_sdk_is_cookie_auth'),
                        'lang'             => get_locale(),
                    ]]); // phpcs:ignore WordPress.Security.EscapeOutput
                }
            }
        );
	}

	/**
	 * Registers assets used by the plugin.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	protected function register_assets() {
        wp_register_script(
            'rai-settings',
            $this->get_js_url( 'settings.js' ),
            [ 'jquery' ],
            $this->get_config( 'version' ),
            false
        );

        wp_localize_script(
            'rai-settings', 'rai_settings', [
                'linkedin_auth_button_id'           => $this->get_config( 'linkedin_auth_button_id' ),
                'linkedin_auth_key_url_param_name'  => $this->get_config( 'linkedin_auth_key_url_param_name' ),
                'linkedin_client_id'                => $this->get_config( 'linkedin_api_key' ),
                'linkedin_auth_url'                 => $this->get_config( 'linkedin_auth_url' ),
                'linkedin_auth_state'               => $this->get_config( 'linkedin_authorization_nonce' ),
                'unlink_url_param_name'             => $this->get_config( 'linkedin_unlink_key' ),
                'unlink_url_param_value'            => $this->get_config( 'linkedin_unlink_value' ),
            ]
        );
	}

	/**
	 * Enqueues assets used by the plugin
	 *
	 * @since 0.1
	 * @return void
	 */
	protected function enqueue_assets() {
//        wp_enqueue_script( 'rai-settings' );
	}

	/**
	 * Registers the API endpoints.
	 *
	 * @since 0.1
	 * @return void
	 */
	protected function register_endpoints() {
	}

	/**
	 * Retrieves a URL to the JS directory of the plugin.
	 *
	 * @since 0.1
	 *
	 * @param string $path The path relative to the JS directory.
	 *
	 * @return string The absolute URL to the JS directory.
	 */
	protected function get_js_url( string $path = '' ): string {
		$base_url = $this->get_config( 'base_url' );

		return "$base_url/assets/js/$path";
	}

	/**
	 * Retrieves a URL to the CSS directory of the plugin.
	 *
	 * @since 0.1
	 *
	 * @param string $path The path relative to the CSS directory.
	 *
	 * @return string The absolute URL to the CSS directory.
	 */
	protected function get_css_url( string $path = '' ): string {
		$base_url = $this->get_config( 'base_url' );

		return "$base_url/assets/css/$path";
	}

	/**
	 * Retrieves the API endpoint URL.
	 *
	 * @since 0.1
	 *
	 * @return string The absolute URL to the API endpoint.
	 */
	protected function get_api_url(): string {
		$namespace = trim( $this->get_config( 'api_namespace' ), '/' );
		$endpoint  = trim( $this->get_config( 'api_endpoint' ), '/' );

		return get_rest_url( null, "$namespace/$endpoint" );
	}

	/**
	 * Retrieves a config value.
	 *
	 * @since 0.1
	 *
	 * @param string $key The key of the config value to retrieve.
	 *
	 * @return mixed The config value.
	 */
	protected function get_config( string $key ) {
		return $this->config->get( $key );
	}

	protected function get_linkedin_auth_token()
    {
        return get_transient($this->get_config('auth_key_option_name'), null);
    }

    /**
     * Handle authentication.
     *
     * Remembers or forgets an authentication token.
     *
     * @since 0.1
     *
     * @return bool|WP_Error True if authentication action succeeded; WP_Error otherwise.
     */
    protected function handle_auth()
    {
        $handler = $this->get_config('linked_in_auth_handler');

        try {
            $handler(
                $this->is_linking(),
                isset($_GET['code']) ? $_GET['code'] : null,
                isset($_GET['error_description']) ? $_GET['error_description'] : null,
                isset($_GET['state']) ? $_GET['state'] : null,
                $this->get_config('settings_page_url')
            );
        } catch (Exception $e) {
            return new WP_Error('rai_auth_error', $e->getMessage());
        }

        return true;
    }

    /**
     * @return bool True if currently linking; false if unlinking.
     */
    protected function is_linking()
    {
        $unlinkUrlParamName = $this->get_config('linkedin_unlink_key');
        $unlinkUrlParamvalue = $this->get_config('linkedin_unlink_value');

        return !(isset($_GET[$unlinkUrlParamName]) && $_GET[$unlinkUrlParamName] === $unlinkUrlParamvalue);
    }
}
