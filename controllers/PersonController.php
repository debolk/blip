<?php

namespace controllers;

use Helper\LdapHelper;
use Helper\MemcacheHelper;
use Helper\ResponseHelper;
use Models\PersonModel;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Valitron\Validator;

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
           return json_encode(self::callArray(PersonModel::all(), 'getBasic'), JSON_UNESCAPED_SLASHES);
        });

        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /persons/all
     * @loggedIn lid
     */
    public static function all(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('persons', function(){
            return json_encode(self::callArray(PersonModel::all(), 'sanitizeAvg'), JSON_UNESCAPED_SLASHES);
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
     * uri: /person/{uid}
     * @loggedIn lid
     */
    public static function person_uid(Request $request, Response $response, array $args) : Response {
        $uid = explode('/', $request->getUri()->getPath())[1]; //uid should always be second in path
        try {
            $result = MemcacheHelper::cache("person-$uid", function (string $uid) {
                return json_encode(PersonModel::fromUid($uid)->getBasic(), JSON_UNESCAPED_SLASHES);
            });
            return ResponseHelper::json($response, $result);
        } catch (\Exception $e) {
            return ResponseHelper::create($response, 404, $e->getMessage());
        }
    }

    /**
     * uri: /person/{uid}/all
     * @loggedIn lid
     */
    public static function person_uid_all(Request $request, Response $response, array $args) : Response {
        $uid = explode('/', $request->getUri()->getPath())[1]; //uid should always be second in path
        try {
            $result = MemcacheHelper::cache("person-$uid-all", function (string $uid) {
                return json_encode(PersonModel::fromUid($uid)->sanitizeAvg(), JSON_UNESCAPED_SLASHES);
            });
            return ResponseHelper::json($response, $result);
        } catch (\Exception $e) {
            return ResponseHelper::create($response, 404, $e->getMessage());
        }
    }
    /**
     * uri: /person/{uid}/bestuur
     * @loggedIn bestuur
     */
    public static function person_uid_bestuur(Request $request, Response $response, array $args) : Response {
        $uid = explode('/', $request->getUri()->getPath())[1]; //uid should always be second in path
        try {
            $result = MemcacheHelper::cache("person-$uid-bestuur", function (string $uid) {
                return json_encode(PersonModel::fromUid($uid), JSON_UNESCAPED_SLASHES);
            });
            return ResponseHelper::json($response, $result);
        } catch (\Exception $e) {
            return ResponseHelper::create($response, 404, $e->getMessage());
        }
    }


    /**
     * uri: /person/{uid}/photo?{width}&{height}
     * @loggedIn lid
     */
    public static function person_uid_photo(Request $request, Response $response, array $args) : Response
    {
        $width = 256;
        $height = 256;
        foreach (explode('&', $request->getUri()->getQuery()) as $param) {
            $ex = explode('=', $param);
            if ($ex[0] == 'width') $width = (int)$ex[1];
            else if ($ex[0] == 'height') $height = (int)$ex[1];
        }
        $uid = explode('/', $request->getUri()->getPath())[1]; //uid should always be second in path
        try {
            $result = MemcacheHelper::cache("person-$uid-photo", function (string $uid, Response $response, int $width, int $height) {
                return PersonModel::fromUid($uid)->getPhoto($response, $width, $height);
            });
            return $result;
        } catch (\Exception $e) {
            return ResponseHelper::create($response, 404, $e->getMessage());
        }
    }

    private static function validate($data, Response $response): Response|bool
    {
        if ($data == null) {
            return ResponseHelper::create($response, 400, "JSON invalid: {$request->getBody()}");
        }

        //validate input
        $v = new Validator($data);
        $v = self::validation_rules($v, true);
        if (!v->validate()){
            $errors = self::getValidatorErrors($v);
            return ResponseHelper::create($response, 400, "Data invalid: $errors");
        }
        return true;
    }

    /**
     * uri: POST/person
     * @loggedIn bestuur
     */
    public static function post_person(Request $request, Response $response, array $args) : Response {
        $data = self::validate(json_decode($request->getBody()), $response);

        if ($data instanceof Response) return $data;

        //create user
        $person = new PersonModel((array)$data);
        $person->save();

        //clear cache
        MemcacheHelper::flush();

        return ResponseHelper::json($response, json_encode($person->to_array(), JSON_UNESCAPED_SLASHES));
    }

    /**
     * uri: PATH/person/{uid}
     * @loggedIn bestuur
     */
    public static function patch_person_uid(Request $request, Response $response, array $args) : Response {
        $uid = explode('/', $request->getUri()->getPath())[1]; //uid should always be second in uri path
        $data = self::validate(json_decode($request->getBody()), $response);

        if ($data instanceof Response) return $data;

        //update user
        try {
            $person = PersonModel::fromUid($uid);
        } catch (\Exception $e) {
            return ResponseHelper::create($response, 404, $e->getMessage());
        }

        foreach ($data as $key => $value){
            if (in_array($key, $person->allowed)) {
                $person->$key = $value;
            }
        }

        if (!$person->save()){
            $ldap = LdapHelper::Connect();
            return ResponseHelper::create($response, 400, "LDAP error: {$ldap->lastError()}");
        }

        //clear cache
        MemcacheHelper::flush();

        return ResponseHelper::json($response, json_encode($person->to_array(), JSON_UNESCAPED_SLASHES));
    }

}