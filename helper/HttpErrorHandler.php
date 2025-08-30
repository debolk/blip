<?php

namespace Helper;
use Slim\Handlers\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpMethodNotAllowedException;

class HttpErrorHandler extends ErrorHandler {
    public const BAD_REQUEST = "BAD_REQUEST";
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const SERVER_ERROR = 'SERVER_ERROR';
    public const NOT_ALLOWED = 'NOT_ALLOWED';

    protected function respond(): ResponseInterface {
        $except = $this->exception;
        $code = 500;
        $type = self::SERVER_ERROR;
        $desc = "An internal error has occurred while processing your request.";
        syslog(LOG_DEBUG, "HttpException: " . var_export($except, true));
        if ($except instanceof HttpException) {
            $code = $except->getCode();
            $desc = $except->getMessage();

            if ($except instanceof HttpNotFoundException) {
                $type = self::RESOURCE_NOT_FOUND;
            } elseif ($except instanceof HttpBadRequestException) {
                $type = self::BAD_REQUEST;
            } elseif ($except instanceof HttpMethodNotAllowedException) {
                $type = self::NOT_ALLOWED;
            } 
        }

        if (!($except instanceof HttpException)
            && ($except instanceof Exception || $except instanceof Throwable)) {
            $desc = $except->getMessage();
        }

        $error = [
            'statusCode' => $code,
            'error' => [
                'type' => $type,
                'description' => $desc,
            ],
        ];
        
        $payload = json_encode($error, JSON_PRETTY_PRINT);
        
        $response = $this->responseFactory->createResponse($code);
        $response->getBody()->write($payload);
        
        return $response;
    }
}