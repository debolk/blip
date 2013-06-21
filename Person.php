<?php

require_once('BlipResource.php');

use Respect\Validation\Validator as v;

/**
 * @uri /persons
 * @provides application/json
 */
class PersonCollection extends BlipResource
{
  /**
   * @method GET
   * @return string
   */
  public function index()
  {
    return json_encode($this->ldap->find_all(), JSON_UNESCAPED_SLASHES);
  }

  /**
   * @method POST
   * @return Tonic\Response
   */
  public function create()
  {
    $candidate = json_decode($this->request->data);

    // Could not decode the data
    if ($candidate === null) {
      return new Tonic\Response(400, "Not valid JSON");
    }

    // Validation fails
    $validator = v::attribute('name', v::string()->notEmpty()->length(1,200))
                  ->attribute('id', v::string()->notEmpty()->length(1,20))
                  ->attribute('email', v::email()->notEmpty());
    try {
      $validator->assert($candidate);
    }
    catch (InvalidArgumentException $e) {
      return new Tonic\Response(400, json_encode($e->findMessages(array('name', 'id', 'email'))));
    }

    //FIXME implement method
    return new Tonic\Response(500, 'Something happens here with the data; not yet implemented');
  }
}

/**
 * @uri /persons/:id
 * @provides application/json
 */
class PersonResource extends BlipResource
{
  /**
   * @method GET
   * @return string
   */
  public function show($id)
  {
    $result = $this->ldap->find($id);
    // Result does not exist
    if ($result === null) {
      return new Tonic\Response(404, "Person not found");
    }
    
    return json_encode($result, JSON_UNESCAPED_SLASHES);
  }

  /**
   * @method PATCH
   * @return string
   */
  public function update($id)
  {
    $input = json_decode($request->data);
    return json_encode($this->ldap->update($input));
  }
}
