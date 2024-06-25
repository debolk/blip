<?php

use Slim\Psr7\Request;
use Slim\Psr7\Response;

class MemberController extends ControllerBase
{

    /**
     * uri: /members
     * @loggedIn lid
     */
    public static function index(Request $request, Response $response, array $args) : Response {

    }

    /**
     * uri: /members/all
     * @loggedIn lid
     */
    public static function members_all(Request $request, Response $response, array $args) : Response {

    }

    /**
     * uri: /members/all/bestuur
     * @loggedIn bestuur
     */
    public static function members_all_bestuur(Request $request, Response $response, array $args) : Response {

    }

    /**
     * uri: /members/current
     * @loggedIn lid
     */
    public static function members_current(Request $request, Response $response, array $args) : Response {

    }

    /**
     * uri: /members/former
     * @loggedIn lid
     */
    public static function members_former(Request $request, Response $response, array $args) : Response {

    }

    /**
     * uri: /members/candidate
     * @loggedIn lid
     */
    public static function members_candidate(Request $request, Response $response, array $args) : Response {

    }

}