<?php

namespace Controllers;

use Helper\LdapHelper;
use Helper\MemcacheHelper;
use Helper\OAuth2Helper;
use Helper\ResponseHelper;
use Models\PersonModel;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Valitron\Validator;

class PersonController extends ControllerBase
{

    /**
     * @var array|string[] map from path to operator level
     */
    private static array $operatorLevels = array(
        '/persons' => 'lid',
        '/persons/all' => 'lid',
        '/person' => 'bestuur',
        '/person/uid' => 'bekend',
        '/person/uid/all' => 'lid',
        '/person/uid/photo' => 'bekend',
        '/person/uid/update' => 'bestuur',
    );

	/**
	 * @var array|string[] set of methods that are allowed external access
	 */
	private static array $externalAllowed = array(
		'/persons',
		'/person/{uid}',
		'/person/{uid}/photo'
	);

    public static function route(Request $request, Response $response, array $args) : Response
    {
        $path = $request->getUri()->getPath();


        if ( count($args) == 1 ) {
            $path = str_replace($args['uid'], 'uid', $path);
        }
//		else if (str_contains($path, 'photo')) {
//            $path = '/person/uid/photo';
//        }

	    //evaluate if external access is allowed.
	    if ( !OAuth2Helper::isAccessInternal($request->getUri()) and !self::allowed_externally($path) ){
			return ResponseHelper::create($response, 403, "This resource is not available externally");
	    }


		$auth = self::loggedIn($response, self::$operatorLevels[$path]);

        if ( is_bool($auth) ){
            switch ($path) {
                case '/persons':
					if ($request->getMethod() == "OPTIONS") return ResponseHelper::option($response, 'GET');
					return self::index($request, $response, $args);

                case '/persons/all':
	                if ($request->getMethod() == "OPTIONS") return ResponseHelper::option($response, 'GET');
					return self::all($request, $response, $args);

                case '/person':
	                if ($request->getMethod() == "OPTIONS") return ResponseHelper::option($response, 'POST');
					return self::post_person($request, $response, $args);

                case '/person/uid':
	                if ($request->getMethod() == "OPTIONS") return ResponseHelper::option($response, 'GET');
					return self::person_uid($request, $response, $args);

                case '/person/uid/all':
	                if ($request->getMethod() == "OPTIONS") return ResponseHelper::option($response, 'GET');
					return self::person_uid_all($request, $response, $args);

                case '/person/uid/photo':
	                if ($request->getMethod() == "OPTIONS") return ResponseHelper::option($response, 'GET');
					return self::person_uid_photo($request, $response, $args);

                case '/person/uid/update':
	                if ($request->getMethod() == "OPTIONS") return ResponseHelper::option($response, 'PATCH');
					return self::patch_person_uid($request, $response, $args);
            }
        } else {
            return $auth;
        }
        return ResponseHelper::create($response, 404, "Path not found: $path");
    }

	/**
     * uri: /persons
     */
    private static function index(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('persons', function(){
           return json_encode(PersonModel::all('basic'), JSON_UNESCAPED_SLASHES);
        });

        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /persons/all
     */
    private static function all(Request $request, Response $response, array $args) : Response {
		$auth = self::loggedIn(new Response(), 'bestuur');

        if ($auth instanceof Response) {
            $result = MemcacheHelper::cache('persons', function(){
                return json_encode(PersonModel::all('sanitize'), JSON_UNESCAPED_SLASHES);
            });
        }
        else {
            $result = MemcacheHelper::cache('persons-bestuur', function(){
                return json_encode(PersonModel::all(), JSON_UNESCAPED_SLASHES);
            });
        }
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /person/{uid}
     */
    private static function person_uid(Request $request, Response $response, array $args) : Response {
        $uid = $args['uid'];
        try {
            $result = MemcacheHelper::cache("person-$uid", function ($uid) {
                return json_encode(PersonModel::fromUid($uid[0])->getBasic(), JSON_UNESCAPED_SLASHES);
            }, $uid);
            return ResponseHelper::json($response, $result);
        } catch (\Exception $e) {
            return ResponseHelper::create($response, 404, $e->getMessage());
        }
    }

    /**
     * uri: /person/{uid}/all
     */
    private static function person_uid_all(Request $request, Response $response, array $args) : Response {
        $uid = $args['uid'];
        try {

            if ( self::loggedIn(new Response(), 'bestuur') instanceof Response ) {
                $result = MemcacheHelper::cache("person-$uid-all", function ($uid) {
                    return json_encode(PersonModel::fromUid($uid[0])->sanitizeAvg(), JSON_UNESCAPED_SLASHES);
                }, $uid);
            } else {
                $result = MemcacheHelper::cache("person-$uid-bestuur", function ($uid) {
                    return json_encode(PersonModel::fromUid($uid[0]), JSON_UNESCAPED_SLASHES);
                }, $uid);
            }
            return ResponseHelper::json($response, $result);
        } catch (\Exception $e) {
            return ResponseHelper::create($response, 404, $e->getMessage());
        }
    }

    /**
     * uri: /person/{uid}/photo
     */
    private static function person_uid_photo(Request $request, Response $response, array $args) : Response
    {
        $uid = $args['uid'];
        try {
	        return ResponseHelper::data($response, MemcacheHelper::cache("person-$uid-photo", function ($args) {
		        return PersonModel::fromUid($args[0])->getPhoto();
	        }, $uid, $response), 'image/jpeg');
        } catch (\Exception $e) {
            return ResponseHelper::create($response, 404, $e->getMessage());
        }
    }

    private static function validate($data, Response $response): Response|bool
    {
        if ($data == null) {
            return ResponseHelper::create($response, 400, "JSON invalid");
        }

        //validate input
        $v = new Validator($data);
        $v = self::validation_rules($v, true);
        if (!$v->validate()){
            $errors = self::getValidatorErrors($v);
            return ResponseHelper::create($response, 400, "Data invalid: $errors");
        }
        return true;
    }

    /**
     * uri: POST/person
     */
    private static function post_person(Request $request, Response $response, array $args) : Response {
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
     * uri: PATCH/person/{uid}/update
     */
    private static function patch_person_uid(Request $request, Response $response, array $args) : Response {
        $uid = $args['uid'];
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