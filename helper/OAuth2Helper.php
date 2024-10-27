<?php

namespace Helper;

use Slim\Psr7\Response;

class OAuth2Helper
{

	private static $oauth2_resource;

    /**
     * Return a boolean whether the user can access a resource
     * @param string $resource a valid access level resource
     * @return boolean|Response           true if access was granted, false otherwise
     */
    public static function isAuthorisedFor(string $resource, Response $response): Response|bool
    {
        if (isset($_POST['access_token'])) {
            $access_token = $_POST['access_token'];
        } elseif (isset($_GET['access_token'])) {
            $access_token = $_GET['access_token'];
        } else {

            return ResponseHelper::create($response, 401, '{"error":"invalid_token","error_description":"No access token was provided"}', "application/json");
        }

        $path = self::$oauth2_resource . $resource . '?access_token=' . $access_token;

        $c = curl_init($path);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        $body = curl_exec($c);

        $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
        curl_close($c);

        if ($code == 200) {
            return true;
        }
        return ResponseHelper::create($response, $code, $body, "application/json");
    }

	public static function Initialise(string $oauth2_resource) {
		self::$oauth2_resource = $oauth2_resource;
	}
}
