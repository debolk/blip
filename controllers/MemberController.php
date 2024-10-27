<?php

namespace Controllers;

use Helper\MemcacheHelper;
use Helper\ResponseHelper;
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
        '/members' => 'bekend',
        '/members/all' => 'lid',
        '/members/current' => 'bekend',
        '/members/former' => 'lid',
        '/members/candidate' => 'bekend'
    );

    public static function route(Request $request, Response $response, array $args) : Response
    {
        $path = $request->getUri()->getPath();

        if ( in_array('uid', $args)) {
            $path = str_replace($args['uid'], 'uid', $path);
        } else if (str_contains($path, 'photo')) {
            $path = '/person/uid/photo';
        }
        $auth = self::loggedIn($response, self::$operatorLevels[$path]);

        if ($auth){
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
            $people = LdapOUnit::peopleInUnits(array(
                LdapOUnit::getPersonOUnits()['lid'],
                LdapOUnit::getPersonOUnits()['kandidaatlid'],
                LdapOUnit::getPersonOUnits()['oud lid'],
                LdapOUnit::getPersonOUnits()['lid van verdienste'],
                LdapOUnit::getPersonOUnits()['erelid'],
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
                $people = LdapOUnit::peopleInUnits(array(
                    LdapOUnit::getPersonOUnits()['lid'],
                    LdapOUnit::getPersonOUnits()['kandidaatlid'],
                    LdapOUnit::getPersonOUnits()['oud lid'],
                    LdapOUnit::getPersonOUnits()['lid van verdienste'],
                    LdapOUnit::getPersonOUnits()['erelid'],
                ), 'sanitize');
                return json_encode($people, JSON_UNESCAPED_SLASHES);
            });
        } else {
            $result = MemcacheHelper::cache('members-bestuur', function(){
                $people = LdapOUnit::peopleInUnits(array(
                    LdapOUnit::getPersonOUnits()['lid'],
                    LdapOUnit::getPersonOUnits()['kandidaatlid'],
                    LdapOUnit::getPersonOUnits()['oud lid'],
                    LdapOUnit::getPersonOUnits()['lid van verdienste'],
                    LdapOUnit::getPersonOUnits()['erelid'],
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
            $people = LdapOUnit::peopleInUnits(array(
                LdapOUnit::getPersonOUnits()['lid'],
                LdapOUnit::getPersonOUnits()['kandidaatlid'],
                LdapOUnit::getPersonOUnits()['lid van verdienste'],
                LdapOUnit::getPersonOUnits()['erelid'],
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
            $people = LdapOUnit::peopleInUnits(array(
                LdapOUnit::getPersonOUnits()['oud lid'],
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
            $people = LdapOUnit::peopleInUnits(array(
                LdapOUnit::getPersonOUnits()['kandidaatlid'],
            ), 'basic');
            return json_encode($people, JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

}