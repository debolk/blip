<?php

namespace controllers;

use Helper\MemcacheHelper;
use Helper\ResponseHelper;
use Models\LdapGroup;
use Models\PersonModel;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class MemberController extends ControllerBase
{

    /**
     * uri: /members
     * @loggedIn lid
     */
    public static function index(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('members', function(){
            $people = LdapGroup::peopleInGroups(array(
                LdapGroup::getPersonGroups()['lid'],
                LdapGroup::getPersonGroups()['kandidaatlid'],
                LdapGroup::getPersonGroups()['oud lid'],
                LdapGroup::getPersonGroups()['lid van verdienste'],
                LdapGroup::getPersonGroups()['erelid'],
            ));
            return json_encode(self::callArray($people, 'getBasic'), JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /members/all
     * @loggedIn lid
     */
    public static function members_all(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('members', function(){
            $people = LdapGroup::peopleInGroups(array(
                LdapGroup::getPersonGroups()['lid'],
                LdapGroup::getPersonGroups()['kandidaatlid'],
                LdapGroup::getPersonGroups()['oud lid'],
                LdapGroup::getPersonGroups()['lid van verdienste'],
                LdapGroup::getPersonGroups()['erelid'],
            ));
            return json_encode(self::callArray($people, 'sanitizeAvg'), JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /members/all/bestuur
     * @loggedIn bestuur
     */
    public static function members_all_bestuur(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('members', function(){
            $people = LdapGroup::peopleInGroups(array(
                LdapGroup::getPersonGroups()['lid'],
                LdapGroup::getPersonGroups()['kandidaatlid'],
                LdapGroup::getPersonGroups()['oud lid'],
                LdapGroup::getPersonGroups()['lid van verdienste'],
                LdapGroup::getPersonGroups()['erelid'],
            ));
            return json_encode($people, JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /members/current
     * @loggedIn lid
     */
    public static function members_current(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('members', function(){
            $people = LdapGroup::peopleInGroups(array(
                LdapGroup::getPersonGroups()['lid'],
                LdapGroup::getPersonGroups()['kandidaatlid'],
                LdapGroup::getPersonGroups()['lid van verdienste'],
                LdapGroup::getPersonGroups()['erelid'],
            ));
            return json_encode(self::callArray($people, 'getBasic'), JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /members/former
     * @loggedIn lid
     */
    public static function members_former(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('members', function(){
            $people = LdapGroup::peopleInGroups(array(
                LdapGroup::getPersonGroups()['oud lid'],
            ));
            return json_encode(self::callArray($people, 'getBasic'), JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

    /**
     * uri: /members/candidate
     * @loggedIn lid
     */
    public static function members_candidate(Request $request, Response $response, array $args) : Response {
        $result = MemcacheHelper::cache('members', function(){
            $people = LdapGroup::peopleInGroups(array(
                LdapGroup::getPersonGroups()['kandidaatlid'],
            ));
            return json_encode(self::callArray($people, 'getBasic'), JSON_UNESCAPED_SLASHES);
        });
        return ResponseHelper::json($response, $result);
    }

}