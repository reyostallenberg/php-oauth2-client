<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 24.10.2019
 * Time: 11:56
 */

namespace Imper86\OauthClient;

use Imper86\OauthClient\Model\TokenInterface;
use Psr\Http\Message\UriInterface;

/**
 * Interface OauthClientInterface
 * @package Imper86\OauthClient
 */
interface OauthClientInterface
{
    /**
     * @param string $state
     * @return UriInterface
     */
    public function getAuthorizationUrl(string $state): UriInterface;

    /**
     * @param string $code
     * @return TokenInterface
     */
    public function fetchToken(string $code): TokenInterface;

    /**
     * @return TokenInterface
     */
    public function fetchClientCredentialsToken(): TokenInterface;

    /**
     * @param TokenInterface $token
     * @return TokenInterface
     */
    public function refreshToken(TokenInterface $token): TokenInterface;
}
