<?php

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException;

/**
 * @accepts application/json
 * @provides application/json
 */
class Person extends Resource
{
    private $ldap;

    public function __construct()
    {
      $options = array(
        'host'              => getenv('LDAP_HOST'),
        'username'          => getenv('LDAP_USERNAME'),
        'password'          => getenv('LDAP_PASSWORD'),
        'bindRequiresDn'    => getenv('LDAP_BINDREQUIRESDN'),
        'accountDomainName' => getenv('LDAP_ACCOUNTDOMAINNAME'),
        'baseDN'            => getenv('LDAP_BASEDN'),
      );
      $server = new Zend\Ldap\Ldap($options);
      $this->ldap = new LDAP($server);
    }

    /**
     * @method GET
     * @url /persons
     * @return string
     */
    public function index()
    {
        return json_encode($this->ldap->find_all());
    }

    /**
     * @method POST
     * @url /persons
     * @return string
     */
    public function create()
    {
        $input = json_decode($request->data);
        return json_encode($this->ldap->create($input));
    }
    /**
     * @method GET
     * @url /persons/:id
     * @return string
     */
    public function show($id)
    {
        return json_encode($this->ldap->find($id));
    }

    /**
     * @method PATCH
     * @url /persons/:id
     * @return string
     */
    public function update($id)
    {
        $input = json_decode($request->data);
        return json_encode($this->ldap->update($input));
    }
}
