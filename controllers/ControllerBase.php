<?php

use Helper\OAuth2Helper;
use Slim\Psr7\Response;
use Slim\Psr7\Request;

class ControllerBase {
    /**
     * The LDAP-abstraction class we use to connect to LDAP
     */
    protected $ldap;
    protected \Slim\App $application;

    /**
     * Construct a new connection to LDAP-server
     */
    public function __construct(Slim\App $application)
    {
        $this->application = $application;
    }

    /**
     * Formats error messages in a human-readable format
     * @param  array $array the set of error messages
     * @return string
     */
    protected function format_errors(array $array) : string
    {
        $output = array();
        foreach ($array as $value) {
            array_push($output, $value[0]);
        }
        return implode(', ', $output);
    }

    /**
     * Applies default rules to validate a person
     * @param  Valitron\Validator $v        validator instance to use
     * @param  boolean            $required whether to require the presence of specific attributes
     * @return Valitron\Validator           the validator with the extra rules
     */
    protected function validation_rules(\Valitron\Validator $v, bool $required = true): \Valitron\Validator
    {
        $v->rule('email', 'email');
        $v->rule('alpha', ['initials']);
        $v->rule('dateBefore', 'dateofbirth', date('Y-m-d'));

        // Validate attributes exist
        if ($required) {
            $v->rule('required', ['firstname', 'lastname', 'email', 'initials', 'pronouns']);
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
}
