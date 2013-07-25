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
   * @loggedIn lid
   * @return string
   */
  public function index()
  {
    return Helper\Memcache::cache('persons_all', function(){
      return json_encode(Models\Person::all(), JSON_UNESCAPED_SLASHES);
    });
  }

  /**
   * @method POST
   * @loggedIn bestuur
   * @return Tonic\Response
   */
  public function create()
  {
    $candidate = json_decode($this->request->data);

    // Could not decode the data
    if ($candidate === null) {
      return new Tonic\Response(400, "Not valid JSON");
    }

    // Validate the input
    $v = new Valitron\Validator($candidate);
    $v = $this->validation_rules($v, true);
    if (!$v->validate()) {
      return new Tonic\Response(400, 'Validation failed: '.$this->format_errors($v->errors()));
    }

    // Create the user
    $person = new Models\Person((array)$candidate);
    $person->save();
    return new Tonic\Response(200, json_encode($person->to_array(), JSON_UNESCAPED_SLASHES));
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
   * @loggedIn lid
   * @return string
   */
  public function show($uid)
  {
    $result = Models\Person::fromUid($uid);
    // Result does not exist
    if ($result === null) {
      return new Tonic\Response(404, "Person not found");
    }

    return json_encode($result, JSON_UNESCAPED_SLASHES);
  }

	/**
	 * @method OPTIONS
	 * @loggedIn lid
	 * @return string
	 */
	public function options($uid)
	{
		$model = Models\Person::fromUid($uid);

		// Result does not exist
		if ($model === null)
      return new Tonic\Response(404, "Person not found");

		$result = new Tonic\Response(200, "");
		$result->allow = "GET,HEAD,PATCH";
		return $result;
	}

  /**
   * @method PATCH
   * @loggedIn bestuur
   * @return Tonic\Response
   */
  public function update($uid)
  {
    $candidate = json_decode($this->request->data);

    // Could not decode the data
    if ($candidate === null) {
      return new Tonic\Response(400, "Not valid JSON");
    }

    // Validate the input
    $v = new Valitron\Validator($candidate);
    $v = $this->validation_rules($v, false);
    if (!$v->validate()) {
      return new Tonic\Response(400, 'Validation failed: '.$this->format_errors($v->errors()));
    }

    // Update the user
    $person = Models\Person::fromUid($uid);

    foreach($candidate as $key => $value)
    {
      if(in_array($key, $person->allowed))
        $person->$key = $value;
    }

    if(!$person->save())
		{
			$ldap = \Helper\LdapHelper::connect();
			return new Tonic\Response(400, 'Ldap error: ' . $ldap->lastError());
		}

    return new Tonic\Response(200, json_encode($person->to_array(), JSON_UNESCAPED_SLASHES));
  }
}
