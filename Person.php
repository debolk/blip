<?php

use Tonic\Resource,
    Tonic\Response,
    Tonic\ConditionException;

/**
 * The obligitory Hello World example
 *
 * @uri /persons
 */
class Person extends Resource
{
    /**
     * Say Hello World
     *
     * @method GET
     * @param  str $name
     * @return Response
     */
    public function sayHello($name = 'World')
    {
        return new Response(Response::OK, 'Hello '.htmlspecialchars(ucwords($name)));
    }
}
