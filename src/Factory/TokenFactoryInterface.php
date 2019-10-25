<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 24.10.2019
 * Time: 12:42
 */

namespace Imper86\OauthClient\Factory;

use Imper86\OauthClient\Model\TokenInterface;
use Psr\Http\Message\ResponseInterface;

interface TokenFactoryInterface
{
    public function createFromResponse(string $grantType, ResponseInterface $response): TokenInterface;
}
