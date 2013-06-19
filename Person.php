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
   * @return string
   */
  public function create()
  {
    return 'not implemented';
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
    return json_encode($this->ldap->find($id), JSON_UNESCAPED_SLASHES);
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
