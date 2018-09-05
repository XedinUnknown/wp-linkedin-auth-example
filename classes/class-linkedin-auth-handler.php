<?php
/**
 * LinkedIn_Auth_Handler class
 *
 * @package RestApiImport
 */

declare( strict_types=1 );

namespace XedinUnknown\RestApiImport;

use Exception;


/**
 * A handler for the 'users` REST API endpoint.
 *
 * @see https://developer.linkedin.com/docs/oauth2
 *
 * @since 0.1
 */
class LinkedIn_Auth_Handler {

    /**
     * The local state code.
     *
     * @since 0.1
     *
     * @var string|null
     */
    protected $currentState;

    /**
     * Name of the option holding the auth code.
     *
     * @since 0.1
     *
     * @var string
     */
    protected $tokenOptionName;

    /**
     * The authorizer used to generate the access token.
     *
     * @since 0.1
     *
     * @var LinkedIn_Authorizer
     */
    protected $authorizer;


	/**
	 * LinkedIn_Auth_Handler constructor
	 *
	 * @since 0.1.
	 *
     * @param string|null $currentState The local state code.
     * @param string $tokenOptionName Name of the option holding the auth token.
     * @param LinkedIn_Authorizer $authorizer The authorizer used to generate the access token.
	 */
	public function __construct(
        $currentState,
        $tokenOptionName,
        $authorizer
    ) {
	    $this->currentState = $currentState;
	    $this->tokenOptionName = $tokenOptionName;
	    $this->authorizer = $authorizer;
	}

	/**
	 * Handles the request.
	 *
	 * @since 0.1
     *
     * @param bool $isLink If true, this handler will remember the authorization code. Otherwise, will forget.
     * @param string|null $inputCode The authorization code that is coming from LinkedIn.
     * @param string|null $inputErrorMessage The error message that is coming from LinkedIn.
     * @param string|null $inputState The response state code that is coming from LinkedIn.
     * @param string $redirectUrl The redirect URL used when authenticating user with permissions.
     *
     * @throws Exception If problem handling.
     *
     * @return void
	 */
	public function __invoke(
	    $isLink,
        $code,
        $errorMessage,
        $state,
        $redirectUrl
    ) {
	    if (!$isLink) {
	        $this->_clearAuthToken();

	        return;
        }

	    if ($code === null && $errorMessage === null) {
	        return null;
        }

        if($state !== $this->currentState) {
            throw new Exception(vsprintf('Could not handle LinkedIn authentication: state code did not match', []));
        }

        if ($errorMessage !== null) {
            throw new Exception($errorMessage);
        }

        $result = $this->authorizer->authorize($code, $redirectUrl);

        $this->_saveAuthToken($result->access_token, $result->expires_in);
	}

    /**
     * Saves the authorization token.
     *
     * @since 0.1
     *
     * @param string|null $token The auth token.
     * @param int $ttl Time to live. The amount of seconds after which the token will expire.
     */
    protected function _saveAuthToken($token, $ttl)
    {
        set_transient($this->tokenOptionName, $token, $ttl);
    }

    /**
     * Removes the access token.
     *
     * @since 0.1
     */
    protected function _clearAuthToken()
    {
        delete_transient($this->tokenOptionName);
    }
}
