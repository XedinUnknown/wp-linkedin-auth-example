<?php
/**
 * Contains service definitions used by the plugin.
 *
 * @package RestApiImport
 */

declare( strict_types=1 );

use XedinUnknown\RestApiImport\DI_Container;
use XedinUnknown\RestApiImport\LinkedIn_Auth_Handler;
use XedinUnknown\RestApiImport\LinkedIn_Authorizer;
use XedinUnknown\RestApiImport\Template_Block;

/**
 * A factory of a service definition map.
 *
 * @since 0.1
 * @param string $base_path Path to the plugin file.
 * @param string $base_url URL of the plugin folder.
 *
 * @return array A map of service names to service definitions.
 */
return function ( string $base_path, string $base_url ):array {
		return [
			'version'                           => '0.1',
			'base_path'                         => $base_path,
			'base_dir'                          => dirname( $base_path ),
			'base_url'                          => $base_url,
			'js_path'                           => '/assets/js',
            'templates_path'                    => '/templates',
			'page_cap'                          => 'manage_options',
			'page_slug'                         => 'rest-api-import-settings',
			'page_parent_slug'                  => 'options-general.php',
            'linkedin_auth_button_id'           => 'linkedin-authorize',
            'linkedin_api_key'                  => '77ukqcs5tjdnyv',
            'linkedin_api_secret'               => '8JVMwvmOLuAYvmhY',
            'linkedin_sdk_is_cookie_auth'       => false,
            'auth_key_option_name'              => 'rai_auth_key',
            'linkedin_auth_key_url_param_name'  => 'linkedin_auth_key',
            'linkedin_auth_url'                 => 'https://www.linkedin.com/oauth/v2/authorization',
            'linkedin_auth_endpoint_url'        => 'https://www.linkedin.com/oauth/v2/accessToken',
            'linkedin_authorization_nonce'      => function (): string {
		        return wp_create_nonce();
            },
            'linkedin_unlink_key'               => 'is_link',
            'linkedin_unlink_value'             => 'false',
            'url_to_link'                       => function ( DI_Container $c ): string {
		        $settingsUrl = $c->get('settings_page_url');
                return add_query_arg([
                    'redirect_uri'                  => esc_url($settingsUrl),
                    'client_id'                     => $c->get('linkedin_api_key'),
                    'response_type'                 => 'code',
                    'state'                         => $c->get('linkedin_authorization_nonce')
                ], $c->get('linkedin_auth_url'));
            },
            'url_to_unlink'                     => function ( DI_Container $c ): string {
                $settingsUrl = $c->get('settings_page_url');
                return add_query_arg([
                    $c->get('linkedin_unlink_key')  => $c->get('linkedin_unlink_value'),
                ], $settingsUrl);
            },
            'settings_page_url'                 => function ( DI_Container $c ): string {
		        return menu_page_url($c->get('page_slug'), false);
            },
			'validator_numeric'                 => function ( DI_Container $c ): callable {
				/**
				 * Validates a numeric parameter.
				 *
				 * @param string $param The parameter value.
				 * @param WP_REST_Request The request.
				 * @param string $key The param key.
				 *
				 * @return bool True if the argument is valid; false otherwise.
				 */
				return function ( string $param ): bool {
					return is_numeric( $param );
				};
			},
			'normalizer_integer'              => function( DI_Container $c ): callable {
				return function ( $value ): int {
					return absint( $value );
				};
			},
			'normalizer_csv'                  => function( DI_Container $c ): callable {
				return function ( $value ): array {
					return explode( ',', $value );
				};
			},
            'block_factory'                 => function ( DI_Container $c ) use ($base_path): callable {
                $templates_path = $c->get('templates_path');
                $templates_path = "$base_path$templates_path";
                return function ($template, $context) use ($templates_path) {
                    return new Template_Block("$templates_path/$template", $context);
                };
            },
            'linked_in_auth_handler'            => function ( DI_Container $c ): callable {
                return new LinkedIn_Auth_Handler(
                    $c->get('linkedin_authorization_nonce'),
                    $c->get('auth_key_option_name'),
                    $c->get('linkedin_authorizer')
                );
            },
            'http_client'                       => function ( DI_Container $c ): WP_Http {
		        return _wp_http_get_object();
            },
            'linkedin_authorizer'               => function ( DI_Container $c ): LinkedIn_Authorizer {
                return new LinkedIn_Authorizer(
                    $c->get('linkedin_auth_endpoint_url'),
                    $c->get('linkedin_api_key'),
                    $c->get('linkedin_api_secret'),
                    $c->get('auth_key_option_name'),
                    $c->get('http_client')
                );
            },
		];
};
