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
    protected $codeOptionName;


	/**
	 * LinkedIn_Auth_Handler constructor
	 *
	 * @since 0.1.
	 *
     * @param string|null $currentState The local state code.
     * @param string $currentState Name of the option holding the auth code.
	 */
	public function __construct(
        $currentState,
        $codeOptionName
    ) {
	    $this->currentState = $currentState;
	    $this->codeOptionName = $codeOptionName;
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
     *
     * @throws Exception If problem handling.
     *
     * @return void
	 */
	public function __invoke(
	    $isLink,
        $code,
        $errorMessage,
        $state
    ) {
	    if (!$isLink) {
	        $this->_saveAuthCode(null);

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

        $this->_saveAuthCode($code);
	}

    /**
     * Saves the authorization code.
     *
     * @since 0.1
     *
     * @param string|null $code The auth code.
     */
	protected function _saveAuthCode($code)
    {
        update_option($this->codeOptionName, $code, true);
    }
}
