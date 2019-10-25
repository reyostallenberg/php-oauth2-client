<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 24.10.2019
 * Time: 14:29
 */

namespace Imper86\OauthClient\Model;

use DateTime;
use DateTimeImmutable;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Token implements TokenInterface
{
    /**
     * @var array
     */
    private $options;

    public function __construct(array $options)
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired([
            'access_token',
            'grant_type',
            'expiry_time',
        ]);

        $resolver->setAllowedTypes('access_token', 'string');
        $resolver->setAllowedTypes('grant_type', 'string');
        $resolver->setAllowedTypes('expiry_time', 'int');

        $resolver->setDefault('refresh_token', null);
        $resolver->setAllowedTypes('refresh_token', ['null', 'string']);

        $resolver->setDefault('resource_owner_id', null);
        $resolver->setAllowedTypes('resource_owner_id', ['null', 'string']);

        $this->options = $resolver->resolve($options);
    }

    public function __toString()
    {
        return $this->options['access_token'];
    }

    public function getAccessToken(): string
    {
        return $this->options['access_token'];
    }

    public function getRefreshToken(): ?string
    {
        return $this->options['refresh_token'];
    }

    public function getGrantType(): string
    {
        return $this->options['grant_type'];
    }

    public function getResourceOwnerId(): ?string
    {
        return $this->options['resource_owner_id'];
    }

    public function isExpired(?DateTime $now = null): bool
    {
        if (null === $now) {
            $now = new DateTimeImmutable();
        }

        return $now > $this->getExpiryTime();
    }

    public function getExpiryTime(): DateTimeImmutable
    {
        return new DateTimeImmutable('@' . $this->options['expiry_time']);
    }

    public function serialize(): array
    {
        return $this->options;
    }
}
