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
        'GET/persons' => 'lid',
        'GET/persons/all' => 'lid',
        'POST/person' => 'bestuur',
        'GET/person/uid' => 'bekend',
        'GET/person/uid/all' => 'lid',
        'GET/person/uid/photo' => 'bekend',
        'PATCH/person/uid/update' => 'bestuur',
	    'DELETE/person/uid' => 'bestuur',
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

		//evaluate if external access is allowed.
	    if ( !OAuth2Helper::isAccessInternal($request->getUri()) and !self::allowed_externally($path) ){
			return ResponseHelper::create($response, 403, "This resource is not available externally");
	    }

		if ($request->getMethod() !== "OPTIONS") $auth = self::loggedIn($response, self::$operatorLevels[$request->getMethod() . $path]);
		else $auth = true;

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
	                if ($request->getMethod() == "OPTIONS") return ResponseHelper::option($response, 'GET,DELETE');
					else if ($request->getMethod() == "DELETE") return self::delete_person_uid($request, $response, $args);
					return self::get_person_uid($request, $response, $args);

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
	private static function delete_person_uid(Request $request, Response $response, array $args): Response {
		$uid = $args['uid'];
		$person = PersonModel::fromUid($uid);

		if ($person !== null && $person->delete()) {
			MemcacheHelper::flush();
			return ResponseHelper::create($response, 200, $uid . ' deleted from LDAP successfully');
		}
		return ResponseHelper::create($response, 500, "Could not delete " . $uid . " from LDAP");
	}

    /**
     * uri: /person/{uid}
     */
    private static function get_person_uid(Request $request, Response $response, array $args) : Response {
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
        $v = self::validation_rules($v, false);
        if (!$v->validate()){
            $errors = self::getValidatorErrors($v);
			syslog(LOG_ERR, $errors);;
            return ResponseHelper::create($response, 400, "Data invalid: $errors");
        }

        return true;
    }

    /**
     * uri: POST/person
     */
    private static function post_person(Request $request, Response $response, array $args) : Response {
        $data = self::transform_incoming_data($request->getBody());
		$valid = self::validate($data, $response);

        if ($valid instanceof Response) return $valid;

        //create user
        $person = new PersonModel($data);

		if (!$person->save(true)) {
			$ldap = LdapHelper::Connect();
			return ResponseHelper::create($response, 500, $ldap->lastError());
		}

        //clear cache
        MemcacheHelper::flush();

        return ResponseHelper::json($response, json_encode($person->to_array(), JSON_UNESCAPED_SLASHES));
    }

    /**
     * uri: PATCH/person/{uid}/update
     */
    private static function patch_person_uid(Request $request, Response $response, array $args) : Response {
        $uid = $args['uid'];
		$data = self::transform_incoming_data($request->getBody());
        $valid = self::validate($data, $response);

        if ($valid instanceof Response) return $valid;

        //update user
        try {
            $person = PersonModel::fromUid($uid);
        } catch (\Exception $e) {
            return ResponseHelper::create($response, 404, $e->getMessage());
        }

        foreach ($data as $key => $value){
            if (in_array($key, PersonModel::$allowed)) {
                $person->__set($key, $value);
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

	/**
	 * Treat incoming data so it's uniform.
	 * @param string $incoming
	 * @return array
	 */
	private static function transform_incoming_data(string $incoming) : array {
		$data = json_decode($incoming);

		while (gettype($data) !== "array") {
			if (gettype($data) === "string") {
				$data = json_decode($data);
				syslog(LOG_DEBUG, $incoming . ' json_decoded to a string first.');
				continue;
			}
			$new_data = array();
			foreach($data as $key => $value){
				$new_data[$key] = $value;
			}
			$data = $new_data;
		}
		return $data;
	}
}