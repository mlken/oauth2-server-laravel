<?php
/**
 * Laravel Service Provider for the OAuth 2.0 Server
 *
 * @package   lucadegasperi/oauth2-server-laravel
 * @author    Luca Degasperi <luca@lucadegasperi.com>
 * @copyright Copyright (c) Luca Degasperi
 * @licence   http://mit-license.org/
 * @link      https://github.com/lucadegasperi/oauth2-server-laravel
 */

namespace LucaDegasperi\OAuth2Server;

use League\OAuth2\Server\AuthorizationServer as Issuer;
use League\OAuth2\Server\Exception\AccessDeniedException;
use League\OAuth2\Server\ResourceServer as Checker;
use League\OAuth2\Server\TokenType\TokenTypeInterface;
use League\OAuth2\Server\Util\RedirectUri;
use Symfony\Component\HttpFoundation\Request;

class Authorizer
{
    /**
     * The authorization server (aka the issuer)
     * @var \League\OAuth2\Server\AuthorizationServer
     */
    protected $issuer;

    /**
     * The resource server (aka the checker)
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $checker;

    /**
     * The auth code request parameters
     * @var array
     */
    protected $authCodeRequestParams;

    /**
     * The redirect uri generator
     */
    protected $redirectUri = null;

    /**
     * Create a new Authorizer instance
     * @param Issuer $issuer
     * @param Checker $checker
     */
    public function __construct(Issuer $issuer, Checker $checker)
    {
        $this->issuer = $issuer;
        $this->checker = $checker;
        $this->authCodeRequestParams = [];
    }

    /**
     * @return \League\OAuth2\Server\AuthorizationServer
     */
    public function getIssuer()
    {
        return $this->issuer;
    }

    /**
     * @return \League\OAuth2\Server\ResourceServer
     */
    public function getChecker()
    {
        return $this->checker;
    }

    /**
     * Issue an access token if the request parameters are valid
     * @return array a response object for the protocol in use
     */
    public function issueAccessToken()
    {
        return $this->issuer->issueAccessToken();
    }

    /**
     * Get the Auth Code request parameters
     * @return array
     */
    public function getAuthCodeRequestParams()
    {
        return $this->authCodeRequestParams;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getAuthCodeRequestParam($key, $default = null)
    {
        if(array_key_exists($key, $this->authCodeRequestParams)) {
            return $this->authCodeRequestParams[$key];
        }
        return $default;
    }

    /**
     * @return null a response appropriate for the protocol in use
     */
    public function checkAuthCodeRequest()
    {
        $this->authCodeRequestParams = $this->issuer->getGrantType('authorization_code')->checkAuthorizeParams();
    }

    /**
     * Issue an auth code
     * @param string $ownerType the auth code owner type
     * @param string $ownerId the auth code owner id
     * @param array $params additional parameters to merge
     * @return string the auth code redirect url
     */
    public function issueAuthCode($ownerType, $ownerId, $params = array())
    {
        $params = array_merge($this->authCodeRequestParams, $params);
        return $this->issuer->getGrantType('authorization_code')->newAuthorizeRequest($ownerType, $ownerId, $params);
    }

    public function authCodeRequestDeniedRedirectUri()
    {
        $error = new AccessDeniedException;
        return $this->getRedirectUri()->make($this->getAuthCodeRequestParam('redirect_uri'), [
                'error' =>  $error->errorType,
                'message'   =>  $error->getMessage()
            ]
        );
    }

    public function getRedirectUri()
    {
        if(is_null($this->redirectUri)) {
            $this->redirectUri = new RedirectUri();
        }
        return $this->redirectUri;
    }

    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * Validate a request with an access token in it
     * @param bool $httpHeadersOnly whether or not to check only the http headers of the request
     * @param string|null $accessToken an access token to validate
     * @return mixed
     */
    public function validateAccessToken($httpHeadersOnly = false, $accessToken = null)
    {
        $this->checker->isValidRequest($httpHeadersOnly, $accessToken);
    }

    /**
     * get the scopes associated with the current request
     * @return array
     */
    public function getScopes()
    {
        return $this->checker->getScopes();
    }

    /**
     * Check if the current request has all the scopes passed
     * @param string|array $scope the scope(s) to check for existence
     * @return bool
     */
    public function hasScope($scope)
    {
        return $this->checker->hasScope($scope);
    }

    /**
     * Get the resource owner ID of the current request
     * @return string
     */
    public function getResourceOwnerId()
    {
        return $this->checker->getOwnerId();
    }

    /**
     * Get the resource owner type of the current request (client or user)
     * @return string
     */
    public function getResourceOwnerType()
    {
        return $this->checker->getOwnerType();
    }

    /**
     * get the client id of the current request
     * @return string
     */
    public function getClientId()
    {
        return $this->checker->getClientId();
    }

    /**
     * Set the request to use on the issuer and checker
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function setRequest(Request $request)
    {
        $this->issuer->setRequest($request);
        $this->checker->setRequest($request);
    }

    /**
     * Set the token type to use
     * @param \League\OAuth2\Server\TokenType\TokenTypeInterface $tokenType
     */
    public function setTokenType(TokenTypeInterface $tokenType)
    {
        $this->issuer->setIdType($tokenType);
        $this->checker->setIdType($tokenType);
    }
}
