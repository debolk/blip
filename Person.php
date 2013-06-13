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
    /**
     * @method GET
     * @url /persons
     */
    public function index()
    {
        return json_encode(array());
    }

    /**
     * @method POST
     * @url /persons
     */
    public function create()
    {
        return json_encode(true);
    }
    /**
     * @method GET
     * @url /persons/:id
     */
    public function show($id)
    {
        return json_encode(new stdClass());
    }

    /**
     * @method PATCH
     * @url /persons/:id
     */
    public function update($id)
    {
        return json_encode(true);
    }
}
