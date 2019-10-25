<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 24.10.2019
 * Time: 12:03
 */

namespace Imper86\OauthClient\Model;

use DateTime;
use DateTimeImmutable;

/**
 * Interface TokenInterface
 * @package Imper86\OauthClient\Model
 */
interface TokenInterface
{
    /**
     * Returns string access token
     *
     * @return string
     */
    public function __toString();

    /**
     * @return string
     */
    public function getAccessToken(): string;

    /**
     * @return string|null
     */
    public function getRefreshToken(): ?string;

    /**
     * @return string
     */
    public function getGrantType(): string;

    /**
     * @return string|null
     */
    public function getResourceOwnerId(): ?string;

    /**
     * @param DateTime|null $now
     * @return bool
     */
    public function isExpired(?DateTime $now = null): bool;

    /**
     * @return DateTimeImmutable
     */
    public function getExpiryTime(): DateTimeImmutable;

    /**
     * @return array
     */
    public function serialize(): array;
}
