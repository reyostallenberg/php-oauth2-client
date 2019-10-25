<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 25.10.2019
 * Time: 17:58
 */

namespace Imper86\OauthClient\Factory;

use Imper86\OauthClient\Model\Token;
use Imper86\OauthClient\Model\TokenInterface;
use Psr\Http\Message\ResponseInterface;

class TokenFactory implements TokenFactoryInterface
{
    public function createFromResponse(string $grantType, ResponseInterface $response, ?TokenInterface $oldToken = null): TokenInterface
    {
        $data = json_decode($response->getBody()->__toString(), true);

        if (empty($data['refresh_token']) && $oldToken) {
            $data['refresh_token'] = $oldToken->getRefreshToken();
        }

        return new Token([
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'expiry_time' => ($data['expires_in'] ?? 0) + time(),
            'grant_type' => $grantType,
        ]);
    }
}
