<?php

namespace controllers;

use Helper\OAuth2Helper;
use Slim\App;
use Slim\Psr7\Response;
use Slim\Psr7\Request;
use Valitron\Validator;

class ControllerBase {
    /**
     * The LDAP-abstraction class we use to connect to LDAP
     */
    protected $ldap;
    protected App $application;

    /**
     * Construct a new connection to LDAP-server
     */
    public function __construct(App $application)
    {
        $this->application = $application;
    }

    /**
     * Formats error messages in a human-readable format
     * @param  array $array the set of error messages
     * @return string
     */
    protected static function format_errors(array $array) : string
    {
        $output = array();
        foreach ($array as $value) {
            array_push($output, $value[0]);
        }
        return implode(', ', $output);
    }

    protected static function getValidatorErrors(Validator $v){
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
        $v->rule('dateBefore', 'dateofbirth', date('Y-m-d'));
        $v->rule('numeric', ['phone', 'phone_parent']);

        $v->rule('optional', ['phone', 'phone_parent']);

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
    public function loggedIn(Response $response, string $resource = 'bekend') : bool|Response
    {
        return OAuth2Helper::isAuthorisedFor($resource, $response);
    }

    /**
     * Mostly a placeholder function
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function processRequest(Request $request, Response $response, array $args) {
        return $response;
    }

    protected static function callArray(array $arr, \MethodCallback $func) : array {
        $result = array();
        foreach($arr as $a) {
            $result[] = call_user_func([$a, $func]);
        }
        return $result;
    }
}
