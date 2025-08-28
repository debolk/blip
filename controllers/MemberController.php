<?php

namespace Controllers;

use Helper\MemcacheHelper;
use Helper\OAuth2Helper;
use Helper\ResponseHelper;
use Models\LdapGroup;
use Models\LdapOUnit;
use Models\PersonModel;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class MemberController extends ControllerBase
{

    /**
     * @var array|string[] map from path to operator level
     */
    private static array $operatorLevels = array(
        'GET/members' => 'bekend',
        'GET/members/all' => 'lid',
        'GET/members/current' => 'bekend',
        'GET/members/former' => 'lid',
        'GET/members/candidate' => 'bekend'
    );

	private static array $externalAllowed = array(
		'/members',
		'/members/current',
		'/members/candidate'
	);

    public static function route(Request $request, Response $response, array $args) : Response
    {
        $path = $request->getUri()->getPath();

        if ( in_array('uid', $args)) {
            $path = str_replace($args['uid'], 'uid', $path);
        } else if (str_contains($path, 'photo')) {
            $path = '/person/uid/photo';
        }

	    //evaluate if external access is allowed.
	    if ( !OAuth2Helper::isAccessInternal($request->getUri()) and !in_array($path, self::$externalAllowed)){
			if ( $request->getMethod() == "OPTIONS") return ResponseHelper::option($response, "GET");
		    return ResponseHelper::create($response, 403, "This resource is not available externally");
	    }

	    if ($request->getMethod() !== "OPTIONS") $auth = self::loggedIn($response, self::$operatorLevels[$request->getMethod() . $path]);
		else $auth = true;

        if ( is_bool($auth) ){
	        if ($request->getMethod() == "OPTIONS") return ResponseHelper::option($response, 'GET');
            switch ($path) {
                case '/members': return self::index($request, $response, $args);
                case '/members/all': return self::members_all($request, $response, $args);
                case '/members/current': return self::members_current($request, $response, $args);
                case '/members/former': return self::members_former($request, $response, $args);
                case '/members/candidate': return self::members_candidate($request, $response, $args);
            }
        } else {
            return $auth;
        }
        return ResponseHelper::create($response, 404, "Path not found: $path");
    }

    /**
     * uri: /members
     */
    public static function index(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('members', function(){
            $people = LdapGroup::peopleInGroups(array(
	            PersonModel::$groupIds['member'],
	            PersonModel::$groupIds['candidate_member'],
	            PersonModel::$groupIds['former_member'],
	            PersonModel::$groupIds['member_of_merit'],
	            PersonModel::$groupIds['honorary_member'],
            ), 'basic');
            return json_encode($people, JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /members/all
     */
    public static function members_all(Request $request, Response $response, array $args) : Response {
        if ( self::loggedIn(new Response(), 'bestuur') instanceof Response ) {
            $result = MemcacheHelper::cache('members', function(){
                $people = LdapGroup::peopleInGroups(array(
                    PersonModel::$groupIds['member'],
                    PersonModel::$groupIds['candidate_member'],
                    PersonModel::$groupIds['former_member'],
                    PersonModel::$groupIds['member_of_merit'],
                    PersonModel::$groupIds['honorary_member'],
                ), 'sanitize');
                return json_encode($people, JSON_UNESCAPED_SLASHES);
            });
        } else {
            $result = MemcacheHelper::cache('members-bestuur', function(){
                $people = LdapGroup::peopleInGroups(array(
                    PersonModel::$groupIds['member'],
                    PersonModel::$groupIds['candidate_member'],
                    PersonModel::$groupIds['former_member'],
                    PersonModel::$groupIds['member_of_merit'],
                    PersonModel::$groupIds['honorary_member'],
                ));
                return json_encode($people, JSON_UNESCAPED_SLASHES);
            });
        }
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /members/current
     */
    public static function members_current(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('members', function(){
            $people = LdapGroup::peopleInGroups(array(
                PersonModel::$groupIds['member'],
                PersonModel::$groupIds['candidate_member'],
                PersonModel::$groupIds['member_of_merit'],
                PersonModel::$groupIds['honorary_member'],
            ), 'basic');
            return json_encode($people, JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /members/former
     */
    public static function members_former(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('members', function(){
            $people = LdapGroup::peopleInGroups(array(
                PersonModel::$groupIds['former_member'],
            ), 'basic');
            return json_encode($people, JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /members/candidate
     */
    public static function members_candidate(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('members', function(){
            $people = LdapGroup::peopleInGroups(array(
                PersonModel::$groupIds['candidate_member'],
            ), 'basic');
            return json_encode($people, JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

}
