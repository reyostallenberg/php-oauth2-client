<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 24.10.2019
 * Time: 12:08
 */

namespace Imper86\OauthClient\Model;

/**
 * Interface CredentialsInterface
 * @package Imper86\OauthClient\Model
 */
interface CredentialsInterface
{
    /**
     * @return string
     */
    public function getClientId(): string;

    /**
     * @return string
     */
    public function getClientSecret(): string;

    /**
     * @return string
     */
    public function getRedirectUri(): string;

    /**
     * @return array
     */
    public function getScopes(): array;
}
