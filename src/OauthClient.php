<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 24.10.2019
 * Time: 12:12
 */

namespace Imper86\OauthClient;

use Http\Client\Common\Plugin\ErrorPlugin;
use Http\Client\Common\Plugin\HeaderDefaultsPlugin;
use Imper86\HttpClientBuilder\Builder;
use Imper86\HttpClientBuilder\BuilderInterface;
use Imper86\OauthClient\Constants\AuthorizationResponseType;
use Imper86\OauthClient\Constants\Config;
use Imper86\OauthClient\Constants\ContentType;
use Imper86\OauthClient\Constants\GrantType;
use Imper86\OauthClient\Constants\TokenEndpointCredentialsPlace;
use Imper86\OauthClient\Constants\TokenEndpointParamsPlace;
use Imper86\OauthClient\Factory\TokenFactoryInterface;
use Imper86\OauthClient\Model\CredentialsInterface;
use Imper86\OauthClient\Model\TokenInterface;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OauthClient implements OauthClientInterface
{
    /**
     * @var array
     */
    private $config;
    /**
     * @var BuilderInterface
     */
    private $httpClientBuilder;
    /**
     * @var CredentialsInterface
     */
    private $credentials;
    /**
     * @var TokenFactoryInterface
     */
    private $tokenFactory;

    public function __construct(array $config)
    {
        $resolver = new OptionsResolver();

        $resolver->setDefault(Config::TOKEN_ENDPOINT, function (OptionsResolver $resolver) {
           $resolver->setRequired('url');

           $resolver->setDefaults([
               'method' => 'POST',
               'params_place' => TokenEndpointParamsPlace::BODY,
               'credentials_place' => TokenEndpointCredentialsPlace::HEADER_BASIC_AUTH,
               'content_type' => ContentType::FORM_URLENCODED,
               'accept' => ContentType::JSON,
           ]);

           $resolver->setAllowedValues('method', ['POST', 'GET']);
           $resolver->setAllowedValues('params_place', [
               TokenEndpointParamsPlace::BODY,
               TokenEndpointParamsPlace::QUERY,
           ]);
           $resolver->setAllowedValues('credentials_place', [
               TokenEndpointCredentialsPlace::QUERY,
               TokenEndpointCredentialsPlace::HEADER_BASIC_AUTH,
           ]);
           $resolver->setAllowedValues('content_type', [
               null,
               ContentType::JSON,
               ContentType::FORM_URLENCODED,
           ]);
           $resolver->setAllowedValues('accept', [
               ContentType::JSON,
               ContentType::FORM_URLENCODED,
           ]);
        });

        $resolver->setDefault(Config::AUTHORIZE_ENDPOINT, function (OptionsResolver $resolver) {
            $resolver->setRequired('url');

            $resolver->setDefault('params', []);
            $resolver->setAllowedTypes('params', 'string[]');

            $resolver->setDefault('scope_delimiter', ' ');
            $resolver->setAllowedTypes('scope_delimiter', 'string');
        });

        $resolver->setDefault(Config::HTTP_CLIENT_BUILDER, function (Options $options) {
            return new Builder();
        });

        $resolver->setRequired([
            Config::CREDENTIALS,
            Config::TOKEN_FACTORY,
        ]);

        $resolver->setAllowedTypes(Config::CREDENTIALS, CredentialsInterface::class);
        $resolver->setAllowedTypes(Config::HTTP_CLIENT_BUILDER, BuilderInterface::class);
        $resolver->setAllowedTypes(Config::TOKEN_FACTORY, TokenFactoryInterface::class);

        $this->config = $resolver->resolve($config);
        $this->credentials = $this->config[Config::CREDENTIALS];
        $this->tokenFactory = $this->config[Config::TOKEN_FACTORY];
        $this->httpClientBuilder = $this->config[Config::HTTP_CLIENT_BUILDER];

        $this->httpClientBuilder->addPlugin(new HeaderDefaultsPlugin($this->getDefaultHeaders()));
        $this->httpClientBuilder->addPlugin(new ErrorPlugin());
    }

    public function getAuthorizationUrl(string $state): UriInterface
    {
        $config = $this->config[Config::AUTHORIZE_ENDPOINT];

        $query = array_merge(
            [
                'client_id' => $this->credentials->getClientId(),
                'redirect_uri' => $this->credentials->getRedirectUri(),
                'response_type' => AuthorizationResponseType::CODE,
                'scope' => implode($config['scope_delimiter'], $this->credentials->getScopes()),
                'state' => $state,
            ],
            $config['params']
        );

        return $this->httpClientBuilder->getUriFactory()->createUri(
            sprintf('%s?%s', $config['url'], http_build_query($query))
        );
    }

    public function fetchToken(string $code): TokenInterface
    {
        $httpClient = $this->httpClientBuilder->getHttpClient();
        $request = $this->prepareTokenRequest(GrantType::AUTHORIZATION_CODE, $code);
        $response = $httpClient->sendRequest($request);

        return $this->tokenFactory->createFromResponse(GrantType::AUTHORIZATION_CODE, $response);
    }

    public function refreshToken(TokenInterface $token): TokenInterface
    {
        $httpClient = $this->httpClientBuilder->getHttpClient();
        $request = $this->prepareTokenRequest(GrantType::REFRESH_TOKEN, $token->getRefreshToken());
        $response = $httpClient->sendRequest($request);

        return $this->tokenFactory->createFromResponse(GrantType::REFRESH_TOKEN, $response);
    }

    private function prepareTokenRequest(string $grantType, ?string $grant): RequestInterface
    {
        $request = $this->httpClientBuilder->getRequestFactory()->createRequest(
            $this->config[Config::TOKEN_ENDPOINT]['method'],
            $this->prepareTokenUri($grantType, $grant)
        );

        if ($body = $this->prepareTokenBody($grantType, $grant)) {
            return $request->withBody($body);
        }

        return $request;
    }

    private function prepareTokenBody(string $grantType, ?string $grant = null): ?StreamInterface
    {
        $config = $this->config[Config::TOKEN_ENDPOINT];

        if (TokenEndpointParamsPlace::BODY === $config['params_place']) {
            switch ($config['content_type']) {
                case ContentType::FORM_URLENCODED:
                    return $this->httpClientBuilder->getStreamFactory()->createStream(
                        http_build_query($this->prepareTokenQuery($grantType, $grant))
                    );
                case ContentType::JSON:
                    return $this->httpClientBuilder->getStreamFactory()->createStream(
                        json_encode($this->prepareTokenQuery($grantType, $grant))
                    );
                default:
                    throw new InvalidArgumentException("Unregognized content_type option value: {$config['content_type']}");
            }
        }

        return null;
    }

    private function getDefaultHeaders(): array
    {
        $config = $this->config[Config::TOKEN_ENDPOINT];

        if (TokenEndpointCredentialsPlace::HEADER_BASIC_AUTH === $config['credentials_place']) {
            $headers['Authorization'] = sprintf(
                'Basic %s',
                base64_encode("{$this->credentials->getClientId()}:{$this->credentials->getClientSecret()}")
            );
        }

        if ($config['content_type']) {
            $headers['Content-Type'] = $config['content_type'];
        }

        $headers['User-Agent'] = 'imper86/oauth2-client (https://github.com/imper86/oauth2-client)';
        $headers['Accept'] = $config['accept'];

        return $headers;
    }

    private function prepareTokenUri(string $grantType, ?string $grant = null): UriInterface
    {
        $uri = $this->httpClientBuilder->getUriFactory()->createUri($this->config[Config::TOKEN_ENDPOINT]['url']);

        if (TokenEndpointParamsPlace::QUERY === $this->config[Config::TOKEN_ENDPOINT]['params_place']) {
            $query = $this->prepareTokenQuery($grantType, $grant);
            $uri = $uri->withQuery(http_build_query($query));
        }

        return $uri;
    }

    private function prepareTokenQuery(string $grantType, ?string $grant = null): array
    {
        switch ($grantType) {
            case GrantType::AUTHORIZATION_CODE:
                $grantParam = 'code';
                break;
            case GrantType::REFRESH_TOKEN:
                $grantParam = 'refresh_token';
                break;
            default:
                $grantParam = null;
                break;
        }

        $query = [
            'grant_type' => $grantType,
            'redirect_uri' => $this->credentials->getRedirectUri(),
        ];

        if ($grantParam && $grant) {
            $query[$grantParam] = $grant;
        }

        if (TokenEndpointCredentialsPlace::QUERY === $this->config[Config::TOKEN_ENDPOINT]['credentials_place']) {
            $query['client_id'] = $this->credentials->getClientId();
            $query['client_secret'] = $this->credentials->getClientSecret();
        }

        return $query;
    }
}
