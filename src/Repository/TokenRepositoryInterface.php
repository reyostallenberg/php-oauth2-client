<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 25.10.2019
 * Time: 14:22
 */

namespace Imper86\OauthClient\Repository;

use Imper86\OauthClient\Exception\NoResourceOwnerException;
use Imper86\OauthClient\Exception\TokenNotFoundException;
use Imper86\OauthClient\Model\TokenInterface;

/**
 * Interface TokenRepositoryInterface
 * @package Imper86\OauthClient\Repository
 */
interface TokenRepositoryInterface
{
    /**
     * @param string $ownerIdentifier
     * @return TokenInterface
     * @throws TokenNotFoundException
     */
    public function load(string $ownerIdentifier): TokenInterface;

    /**
     * @param TokenInterface $token
     * @param string|null $ownerIdentifier
     * @throws NoResourceOwnerException
     */
    public function save(TokenInterface $token, ?string $ownerIdentifier = null): void;
}
