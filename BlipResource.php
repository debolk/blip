<?php

require_once('LDAP.php');
require_once('OAuth2Helper.php');

class BlipResource extends Tonic\Resource
{
  /**
   * The LDAP-abstraction class we use to connect to LDAP
   */
  protected $ldap;

  /**
   * Construct a new connection to LDAP-server
   */
  public function __construct(Tonic\Application $application, Tonic\Request $request)
  {
    parent::__construct($application, $request);
    $this->ldap = new LDAP();
  }

  /**
   * Formats error messages in a human-readable format
   * @param array the set of error messages
   * @return string
   */
  protected function format_errors($array)
  {
    $output = array();
    foreach ($array as $value) {
      array_push($output, $value[0]);
    }
    return implode(', ', $output);
  }

  /**
   * Applies default rules to validate a person
   * @param Valitron\Validator $v validator instance to use
   * @param boolean $required whether to require the presence of specific attributes
   * @return Valitron\Validator the validator with the extra rules
   */
  protected function validation_rules($v, $required = true)
  {
    $v->rule('email', 'email');
    $v->rule('alpha', ['firstname', 'lastname', 'initials']);
    $v->rule('regex', 'gender', '/^[FM]$/')->message('{field} must be F or M');
    $v->rule('dateBefore', 'dateofbirth', date('Y-m-d'));

    // Validate attributes exist
    if ($required) {
      $v->rule('required', ['firstname', 'lastname', 'email', 'initials', 'gender']);
    }

    return $v;
  }

  /**
   * Only allow bestuur to access this API
   * @param  Tonic\Resource $resource
   * @return boolean
   */
  public function loggedIn($resource)
  {
    $unauthorized = OAuth2Helper::IsUnauthorized('bestuur');
    if($unauthorized)
      return $unauthorized;
    return true;
  }
}
