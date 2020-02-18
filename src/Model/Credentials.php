<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 24.10.2019
 * Time: 14:25
 */

namespace Imper86\OauthClient\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Credentials implements CredentialsInterface
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired([
            'client_id',
            'client_secret',
            'redirect_uri',
        ]);

        $resolver->setAllowedTypes('client_id', 'string');
        $resolver->setAllowedTypes('client_secret', 'string');
        $resolver->setAllowedTypes('redirect_uri', 'string');

        $resolver->setDefault('scopes', []);
        $resolver->setAllowedTypes('scopes', 'string[]');

        $resolver->setDefault('base_uri', null);
        $resolver->setAllowedTypes('base_uri', ['null', 'string']);

        $this->config = $resolver->resolve($config);
    }

    public function getClientId(): string
    {
        return $this->config['client_id'];
    }

    public function getClientSecret(): string
    {
        return $this->config['client_secret'];
    }

    public function getRedirectUri(): string
    {
        return $this->config['redirect_uri'];
    }

    public function getScopes(): array
    {
        return $this->config['scopes'];
    }

    public function getBaseUri(): ?string
    {
        return $this->config['base_uri'] ?? null;
    }
}
