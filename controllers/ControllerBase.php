<?php

namespace Controllers;

use Helper\OAuth2Helper;
use Psr\Http\Message\UriInterface;
use Slim\App;
use Slim\Psr7\Response;
use Slim\Psr7\Request;
use Valitron\Validator;

abstract class ControllerBase {

    /**
     * @var array|string[] map from path to operator level
     */
    private static array $operatorLevels;

	/**
	 * @var array|string[] set of methods that are allowed external access
	 */
	private static array $externalAllowed;

    /**
     * Function to route all requests to this class
     *
     * @param Request $request  the Request object
     * @param Response $response the Response object
     * @param array $args the {} arguments in the requested uri
     * @return Response a Response object to be sent to the client
     */
    public abstract static function route(Request $request, Response $response, array $args): Response;

    /**
     * Formats error messages in a human-readable format
     * @param  array $array the set of error messages
     * @return string
     */
    protected static function format_errors(array $array) : string
    {
        $output = array();
        foreach ($array as $value) {
            $output[] = $value[0];
        }
        return implode(', ', $output);
    }

    protected static function getValidatorErrors(Validator $v): string
    {
        return self::format_errors($v->errors());
    }

    /**
     * Applies default rules to validate a person
     * @param  Validator $v        validator instance to use
     * @param  boolean            $required whether to require the presence of specific attributes
     * @return Validator           the validator with the extra rules
     */
    protected static function validation_rules(Validator $v, bool $required = true): \Valitron\Validator
    {
        $v->rule('email', 'email');
        $v->rule('alpha', ['initials']);
        $v->rule('dateBefore', ['dateofbirth', 'inauguration_date'], date('Y-m-d'));
        $v->rule(function($field, $value, $params, $fields){
			if (!is_string($value) and !is_numeric($value)) {
				return false;
			}

			//remove all occurences of +, -, (, ) and spaces
			$value = str_replace("+", "", (string)$value);
			$value = str_replace(" ", "", $value);
			$value = str_replace("-", "", $value);
			$value = str_replace("(", "", $value);
			$value = str_replace(")", "", $value);
			return is_numeric($value);
        }, ['phone', 'phone_emergency'])->message("{field} is not a valid phone number.");

        $v->rule('optional', ['phone', 'phone_emergency']);

        // Validate attributes exist
        if ($required) {
            $v->rule('required', ['firstname', 'surname', 'dateofbirth', 'email', 'initials', 'pronouns', 'programme', 'institution', 'address']);
        }

        return $v;
    }

    /**
     * Check if the user can access an access level resource
     * @param  string $resource can be any valid access level resource
     *                                  bekend (default), bestuur, ictcom, lid or mp3control
     * @return boolean|Response true if logged in, Response if otherwise
     */
    public static function loggedIn(Response $response, string $resource = 'bekend', string $user_id = "") : bool|Response
    {
        return OAuth2Helper::isAuthorisedFor($resource, $response, $user_id);
    }
}
