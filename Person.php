<?php

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException;

/**
 * @uri /persons
 * @provides application/json
 */
class PersonsCollection extends Resource
{
    /**
     * @method GET
     */
    public function index()
    {
        return json_encode(array());
    }

    /**
     * @method POST
     */
    public function create()
    {
        return json_encode(true);
    }
}

/**
 * @uri /persons/:id
 * @provides application/json
 */
class Person extends Resource
{
    /**
     * @method GET
     */
    public function show($id)
    {
        return json_encode(new stdClass());
    }

    /**
     * @method PATCH
     */
    public function update($id)
    {
        return json_encode(true);
    }
}