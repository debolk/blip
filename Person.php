<?php

require_once('BlipResource.php');

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
    $v = new Valitron\Validator($candidate);
    $v->rule('required', ['firstname', 'lastname', 'email', 'initials', 'gender']);
    $v->rule('email', 'email');
    $v->rule('alpha', ['firstname', 'lastname_prefix', 'lastname', 'initials']);
    $v->rule('regex', 'gender', '/^[FM]$/')->message('{field} must be F or M');
    $v->rule('numeric', ['phone', 'mobile', 'phone_parents']);
    if (!$v->validate()) {
      return new Tonic\Response(400, 'Validation failed: '.$this->format_errors($v->errors()));
    }

    // Create the user
    return new Tonic\Response(200, json_encode($this->ldap->create($candidate), JSON_UNESCAPED_SLASHES));
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
   * @return Tonic\Response
   */
  public function update($id)
  {
    $candidate = json_decode($this->request->data);

    // Could not decode the data
    if ($candidate === null) {
      return new Tonic\Response(400, "Not valid JSON");
    }

    // Validation fails
    $validator = v::attribute('name', v::string()->length(1,200))
                  ->attribute('uid', v::string()->length(1,20))
                  ->attribute('email', v::email());
    try {
      $validator->assert($candidate);
    }
    catch (InvalidArgumentException $e) {
      return new Tonic\Response(400, json_encode($e->findMessages(array('name', 'id', 'email'))));
    }

    $this->ldap->update($candidate);
    return new Tonic\Response(200);
  }
}
