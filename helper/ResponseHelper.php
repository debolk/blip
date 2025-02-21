<?php

namespace Helper;

use Slim\Psr7\Response;

class ResponseHelper
{

    public static function create(Response $response, int $code, string $message, string $contentType = "text/plain"): Response
    {
		if ($contentType === "text/plain"){
			$new_response = $response->withStatus($code, $message);
		} else {
			$new_response = $response->withStatus($code);
		}

		if ($code != 200){
			$new_response = $new_response->withHeader("Access-Control-Allow-Origin", '*');
		}

        return ResponseHelper::data($new_response, $message, $contentType);
    }

	public static function json(Response $response, string $json): Response {
		return self::data($response, $json,'application/json')->withStatus(200);
	}

	public static function option(Response $response, string $method): Response {
		return $response->withStatus(204)->withHeader('Allow', $method);
	}

    public static function data(Response $response, mixed $payload, string $type) : Response {
        $response = $response->withHeader("Content-Type", $type);
		$response->getBody()->write($payload);
	    return $response;
    }

}