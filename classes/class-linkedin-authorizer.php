<?php
/**
 * LinkedIn_Authorizer class.
 *
 * @package RestApiImport
 */

declare( strict_types=1 );

namespace XedinUnknown\RestApiImport;

use Exception;
use WP_Error;
use WP_Http;

/**
 * Allows getting an access token for an authorization code.
 *
 * @since 0.1
 *
 * @package RestApiImport
 */
class LinkedIn_Authorizer
{
    /**
     * The URL of the access token endpoint.
     *
     * @since 0.1
     *
     * @var string
     */
    protected $endpointUrl;

    /**
     * The client ID for LinkedIn account.
     *
     * @since 0.1
     *
     * @var string
     */
    protected $clientId;

    /**
     * The client ID for LinkedIn account.
     *
     * @since 0.1
     *
     * @var string
     */
    protected $clientSecret;

    /**
     * The name of the transient that temporarily stores the token.
     *
     * @since 0.1
     *
     * @var string
     */
    protected $optionName;

    /**
     * The HTTP client used to make requests to LinkedIn REST API.
     *
     * @since 0.1
     *
     * @var string
     */
    protected $httpClient;

    /**
     * LinkedInApi constructor.
     *
     * @since 0.1
     *
     * @param string $endpointUrl The URL of the access token endpoint.
     * @param string $clientId The client ID for LinkedIn account.
     * @param string $clientSecret The user access code received during authentication.
     * @param string $optionName The name of the transient that temporarily stores the token.
     * @param WP_Http $httpClient The HTTP client used to make requests to LinkedIn REST API.
     */
    public function __construct(
        string $endpointUrl,
        string $clientId,
        string $clientSecret,
        string $optionName,
        WP_Http $httpClient
    ) {
        $this->endpointUrl = $endpointUrl;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->optionName = $optionName;
        $this->httpClient = $httpClient;
    }

    /**
     * Retrieves the access token.
     *
     * @since 0.1
     *
     * @see https://developer.linkedin.com/docs/oauth2
     *
     * @param string $accessCode The user access code received during authentication.
     * @param string $redirectUrl The redirect URL used during authentication.
     *
     * @throws Exception If authorization failed.
     *
     * @return object The LinkedIn access token response.
     * `access_token` contains the token.
     * `expires_in` is the number of seconds for which the token is valid.
     */
    public function authorize($accessCode, $redirectUrl)
    {
        $result = $this->_request(
            $this->endpointUrl,
            'POST',
            $this->_buildRequestBody([
                'grant_type'                => 'authorization_code',
                'code'                      => $accessCode,
                'redirect_uri'              => $redirectUrl,
                'client_id'                 => $this->clientId,
                'client_secret'             => $this->clientSecret,
            ]),
            [
                'Content-Type'              => 'application/x-www-form-urlencoded',
            ]
        );

        if (is_wp_error($result)) {
            throw new Exception($result->get_error_message());
        }

        $responseCode = wp_remote_retrieve_response_code($result);
        $content = wp_remote_retrieve_body($result);
        $data = json_decode($content);

        if ($responseCode !== 200) {
            throw new Exception(vsprintf('Authentication server responded with code "%1$s": %2$s', [$responseCode, $data->error_description]));
        }


        if ( !isset($data->access_token) || !isset($data->expires_in) ) {
            throw new Exception(vsprintf('Authentication server responded with unexpected format', []));
        }

        return $data;
    }

    /**
     * Builds the request body string from parameters.
     *
     * @since 0.1
     *
     * @param array $params The parameters to encode in the body.
     * @return string The request body in the appropriate format.
     */
    protected function _buildRequestBody($params)
    {
        return http_build_query($params);
    }

    /**
     * Makes an HTTP request.
     *
     * @since 0.1
     *
     * @param string $url The URL to which to make the request.
     * @param string $method The method of the request.
     * @param string $body The request body.
     * @param array $headers The headers to send with the request.
     * @return array|WP_Error The response on success; otherwise an error object.
     */
    protected function _request($url, $method, $body, $headers = [])
    {
        return $this->httpClient->request($url, [
            'method'                    => $method,
            'headers'                   => $headers,
            'body'                      => $body,
        ]);
    }
}
