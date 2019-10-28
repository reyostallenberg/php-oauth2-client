<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 25.10.2019
 * Time: 14:35
 */

namespace Imper86\OauthClient\Model;

use Imper86\HttpClientBuilder\Builder;
use Imper86\HttpClientBuilder\BuilderInterface;
use Imper86\OauthClient\Constants\ContentType;
use Imper86\OauthClient\Constants\TokenEndpointCredentialsPlace;
use Imper86\OauthClient\Constants\TokenEndpointParamsPlace;
use Imper86\OauthClient\Factory\TokenFactory;
use Imper86\OauthClient\Factory\TokenFactoryInterface;
use Imper86\OauthClient\Repository\TokenRepositoryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Configuration extends OptionsResolver
{
    public function __construct()
    {
        $this->setDefault('token_endpoint', function (OptionsResolver $resolver) {
            $this->prepareTokenEndpointConfig($resolver);
        });

        $this->setDefault('authorize_endpoint', function (OptionsResolver $resolver) {
            $this->prepareAuthorizeEndpointConfig($resolver);
        });

        $this->setDefault('http_client_builder', function (Options $options) {
            return new Builder();
        });
        $this->setAllowedTypes('http_client_builder', BuilderInterface::class);

        $this->setDefault('token_repository', null);
        $this->setAllowedTypes('token_repository', ['null', TokenRepositoryInterface::class]);

        $this->setDefault('token_factory', function (Options $options) {
            return new TokenFactory();
        });
        $this->setAllowedTypes('token_factory', TokenFactoryInterface::class);

        $this->setRequired('credentials');
        $this->setAllowedTypes('credentials', CredentialsInterface::class);
    }

    private function prepareTokenEndpointConfig(OptionsResolver $resolver): void
    {
        $resolver->setRequired('url');
        $resolver->setAllowedTypes('url', 'string');

        $resolver->setDefault('method', 'POST');
        $resolver->setAllowedValues('method', ['POST', 'GET']);

        $resolver->setDefault('params_place', TokenEndpointParamsPlace::BODY);
        $resolver->setAllowedValues('params_place', [
            TokenEndpointParamsPlace::BODY,
            TokenEndpointParamsPlace::QUERY,
        ]);

        $resolver->setDefault('credentials_place', TokenEndpointCredentialsPlace::HEADER_BASIC_AUTH);
        $resolver->setAllowedValues('credentials_place', [
            TokenEndpointCredentialsPlace::HEADER_BASIC_AUTH,
            TokenEndpointCredentialsPlace::QUERY,
        ]);

        $resolver->setDefault('content_type', ContentType::FORM_URLENCODED);
        $resolver->setAllowedValues('content_type', [
            ContentType::FORM_URLENCODED,
            ContentType::JSON,
        ]);

        $resolver->setDefault('accept', ContentType::JSON);
        $resolver->setAllowedValues('accept', [
            ContentType::JSON,
            ContentType::FORM_URLENCODED,
        ]);
    }

    private function prepareAuthorizeEndpointConfig(OptionsResolver $resolver)
    {
        $resolver->setRequired('url');
        $resolver->setAllowedTypes('url', 'string');

        $resolver->setDefault('params', []);
        $resolver->setAllowedTypes('params', 'string[]');

        $resolver->setDefault('scope_delimiter', ' ');
        $resolver->setAllowedTypes('scope_delimiter', 'string');
    }
}
