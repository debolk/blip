<?php

namespace Helper;

use Slim\Psr7\Response;

class ResponseHelper
{

    public static function create(Response $response, int $code, string $message, string $contentType = "text/plain"): Response
    {
        $new_response = $response->withStatus($code);

        return ResponseHelper::data($new_response, $message, $contentType);
    }

    public static function data(Response $response, mixed $payload, string $type) : Response {
        $response->getBody()->write($payload);
        return $response->withHeader("Content-Type", $type);
    }

    public static function json(Response $response, string $json): Response {
        $response->getBody()->write($json);
        return $response->withHeader("Content-Type", 'application/json');
    }

}