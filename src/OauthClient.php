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
use Imper86\HttpClientBuilder\BuilderInterface;
use Imper86\OauthClient\Constants\AuthorizationResponseType;
use Imper86\OauthClient\Constants\ContentType;
use Imper86\OauthClient\Constants\GrantType;
use Imper86\OauthClient\Constants\TokenEndpointCredentialsPlace;
use Imper86\OauthClient\Constants\TokenEndpointParamsPlace;
use Imper86\OauthClient\Factory\TokenFactoryInterface;
use Imper86\OauthClient\Model\Configuration;
use Imper86\OauthClient\Model\CredentialsInterface;
use Imper86\OauthClient\Model\TokenInterface;
use Imper86\OauthClient\Repository\TokenRepositoryInterface;
use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

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
    /**
     * @var TokenRepositoryInterface|null
     */
    private $tokenRepository;

    public function __construct(array $config)
    {
        $resolver = new Configuration();

        $this->config = $resolver->resolve($config);
        $this->credentials = $this->config['credentials'];
        $this->tokenFactory = $this->config['token_factory'];
        $this->httpClientBuilder = $this->config['http_client_builder'];
        $this->tokenRepository = $this->config['token_repository'];

        $this->httpClientBuilder->addPlugin(new HeaderDefaultsPlugin($this->getDefaultHeaders()));
        $this->httpClientBuilder->addPlugin(new ErrorPlugin());
    }

    public function getAuthorizationUrl(string $state): UriInterface
    {
        $config = $this->config['authorize_endpoint'];

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

        $token = $this->tokenFactory->createFromResponse(GrantType::AUTHORIZATION_CODE, $response);

        if ($this->tokenRepository) {
            $this->tokenRepository->save($token);
        }

        return $token;
    }

    public function refreshToken(TokenInterface $token): TokenInterface
    {
        $httpClient = $this->httpClientBuilder->getHttpClient();
        $request = $this->prepareTokenRequest(GrantType::REFRESH_TOKEN, $token->getRefreshToken());
        $response = $httpClient->sendRequest($request);

        $newToken = $this->tokenFactory->createFromResponse(GrantType::REFRESH_TOKEN, $response, $token);

        if ($this->tokenRepository) {
            $this->tokenRepository->save($newToken);
        }

        return $newToken;
    }

    private function prepareTokenRequest(string $grantType, ?string $grant): RequestInterface
    {
        $request = $this->httpClientBuilder->getRequestFactory()->createRequest(
            $this->config['token_endpoint']['method'],
            $this->prepareTokenUri($grantType, $grant)
        );

        if ($body = $this->prepareTokenBody($grantType, $grant)) {
            return $request->withBody($body);
        }

        return $request;
    }

    private function prepareTokenBody(string $grantType, ?string $grant = null): ?StreamInterface
    {
        $config = $this->config['token_endpoint'];

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
        $config = $this->config['token_endpoint'];

        if (TokenEndpointCredentialsPlace::HEADER_BASIC_AUTH === $config['credentials_place']) {
            $headers['Authorization'] = sprintf(
                'Basic %s',
                base64_encode("{$this->credentials->getClientId()}:{$this->credentials->getClientSecret()}")
            );
        }

        if ($config['content_type']) {
            $headers['Content-Type'] = $config['content_type'];
        }

        $headers['User-Agent'] = 'imper86/php-oauth2-client (https://github.com/imper86/oauth2-client)';
        $headers['Accept'] = $config['accept'];

        return $headers;
    }

    private function prepareTokenUri(string $grantType, ?string $grant = null): UriInterface
    {
        $uri = $this->httpClientBuilder->getUriFactory()->createUri($this->config['token_endpoint']['url']);

        if (TokenEndpointParamsPlace::QUERY === $this->config['token_endpoint']['params_place']) {
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

        if (TokenEndpointCredentialsPlace::QUERY === $this->config['token_endpoint']['credentials_place']) {
            $query['client_id'] = $this->credentials->getClientId();
            $query['client_secret'] = $this->credentials->getClientSecret();
        }

        return $query;
    }
}
