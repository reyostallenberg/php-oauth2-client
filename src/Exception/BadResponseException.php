<?php
/**
 * Author: Adrian Szuszkiewicz <me@imper.info>
 * Github: https://github.com/imper86
 * Date: 24.10.2019
 * Time: 15:55
 */

namespace Imper86\OauthClient\Exception;

use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class BadResponseException extends Exception
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ResponseInterface
     */
    private $response;

    public function __construct($message, RequestInterface $request, ResponseInterface $response, Throwable $previous = null)
    {
        parent::__construct($message, $response->getStatusCode(), $previous);
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
