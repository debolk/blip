<?php

namespace controllers;

use Helper\MemcacheHelper;
use Helper\ResponseHelper;
use Models\PersonModel;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class PersonController extends ControllerBase
{

    //TODO: CHECK LOGGED IN PERMISSIONS

    /**
     * uri: /persons
     * @loggedIn lid
     * @returns Response the response object
     */
    public static function index(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('persons', function(){

           $result = array();
           foreach(PersonModel::all() as $p) {
               $result[] = $p->getBasic();
           }

           return json_encode($result, JSON_UNESCAPED_SLASHES);
        });

        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /persons/all
     * @loggedIn lid
     */
    public static function all(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('persons', function(){

            $result = array();
            foreach(PersonModel::all() as $p) {
                $result[] = $p->sanitizeAvg();
            }

            return json_encode($result, JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /persons/all/bestuur
     * @loggedIn bestuur
     */
    public static function all_bestuur(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('persons-bestuur', function(){
            return json_encode(PersonModel::all(), JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /person
     * @loggedIn bestuur
     */
    public static function person(Request $request, Response $response, array $args) : Response {

    }

    /**
     * uri: /person/{uid}
     * @loggedIn lid
     */
    public static function person_uid(Request $request, Response $response, array $args) : Response {

    }

    /**
     * uri: /person/{uid}/bestuur
     * @loggedIn bestuur
     */
    public static function person_uid_bestuur(Request $request, Response $response, array $args) : Response {

    }

    /**
     * uri: /person/{uid}/basic
     * @loggedIn lid
     */
    public static function person_uid_basic(Request $request, Response $response, array $args) : Response {

    }

    /**
     * TODO: CHECK IF PARAMETERS ARE POSSIBLE INSTEAD FOR WIDTH/HEIGHT
     * uri: /person/{uid}/photo/{width}/{height}
     * @loggedIn lid
     */
    public static function person_uid_photo(Request $request, Response $response, array $args) : Response {

    }

    /**
     * uri: PATH/person/{uid}
     * @loggedIn bestuur
     */
    public static function patch_person_uid(Request $request, Response $response, array $args) : Response {

    }

}