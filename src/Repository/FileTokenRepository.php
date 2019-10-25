<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 25.10.2019
 * Time: 14:29
 */

namespace Imper86\OauthClient\Repository;

use Imper86\OauthClient\Exception\NoResourceOwnerException;
use Imper86\OauthClient\Exception\TokenNotFoundException;
use Imper86\OauthClient\Model\Token;
use Imper86\OauthClient\Model\TokenInterface;
use InvalidArgumentException;

class FileTokenRepository implements TokenRepositoryInterface
{
    /**
     * @var string
     */
    private $workDir;

    public function __construct(string $workDir)
    {
        if (!is_dir($workDir)) {
            throw new InvalidArgumentException("{$workDir} is not a directory");
        }

        if (!file_exists($workDir)) {
            throw new InvalidArgumentException("{$workDir} doesn't exists");
        }

        $this->workDir = $workDir;
    }

    public function load(string $ownerIdentifier): TokenInterface
    {
        $path = $this->getPath($ownerIdentifier);

        if (!file_exists($path)) {
            throw new TokenNotFoundException("Token not found for owner: {$ownerIdentifier}");
        }

        $data = json_decode(file_get_contents($path), true);

        return new Token($data);
    }

    public function save(TokenInterface $token, ?string $ownerIdentifier = null): void
    {
        if (null === $ownerIdentifier) {
            if (!$token->getResourceOwnerId()) {
                throw new NoResourceOwnerException("Invalid arguments: resource owner id not found");
            }

            $ownerIdentifier = $token->getResourceOwnerId();
        }

        file_put_contents($this->getPath($ownerIdentifier), json_encode($token->serialize()));
    }

    public function getPath(string $ownerIdentifier): string
    {
        return "{$this->workDir}/{$ownerIdentifier}.token.json";
    }
}
