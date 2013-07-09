<?php

require_once('LDAP.php');

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
}