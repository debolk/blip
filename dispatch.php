<?php

// Load Slim
use controllers\PersonController;
use Helper\LdapHelper;
use Helper\MemcacheHelper;
use Helper\OAuth2Helper;
use Mailer\NewPerson;
use Models\PersonModel;

require_once('vendor/autoload.php');
$config = require_once('config.php');

$app = Slim\Factory\AppFactory::create();

LdapHelper::Initialise($config['LDAP_HOST'], $config['LDAP_BASEDN'], $config['LDAP_USERNAME'], $config['LDAP_PASSWORD']);
MemcacheHelper::Initialise($config['MEMCACHE_HOST'], $config['MEMCACHE_PORT'], $config['MEMCACHE_EXPIRY']);
OAuth2Helper::Initialise($config['OAUTH2_RESOURCE'], $config['BASE_URL']);
if ($config['DEBUG']) OAuth2Helper::initialiseDebug($config['DEBUG_ACCESSTOKEN']);
PersonModel::Initialise($config['BASE_URL']);
NewPerson::Initialise($config['MAIL_FROM']);


//auths:
//bestuur: members of beheer or bestuur
//ictcom: members of beheer, bestuur or ictcom
//lid: members of the society (does not include candidate-members)
//bekend: members and candidate-members of the society

$app->get('/persons', 'Controllers\PersonController::route'); //return all persons with basic info
$app->get('/persons/all', 'Controllers\PersonController::route'); //return all persons with all information, ex avg
$app->post('/person', 'Controllers\PersonController::route'); //create new person
$app->get('/person/{uid}', 'Controllers\PersonController::route'); //return person with basic info
$app->get('/person/{uid}/all', 'Controllers\PersonController::route'); //return person with all info ex avg
$app->get('/person/{uid}/photo', 'Controllers\PersonController::route'); //return persons profile picture
$app->patch('/person/{uid}/update', 'Controllers\PersonController::route'); //update person information
$app->get('/members', 'Controllers\MemberController::route'); //return all members with basic info
$app->get('/members/all', 'Controllers\MemberController::route'); //return all members with all info ex avg
$app->get('/members/current', 'Controllers\MemberController::route'); //return all current members with basic info
$app->get('/members/former', 'Controllers\MemberController::route'); //return all former members with basic info
$app->get('/members/candidate', 'Controllers\MemberController::route'); //return all candidate members with basic info

$app->options('/persons', 'Controllers\PersonController::route');
$app->options('/persons/all', 'Controllers\PersonController::route');
$app->options('/person', 'Controllers\PersonController::route');
$app->options('/person/{uid}', 'Controllers\PersonController::route');
$app->options('/person/{uid}/all', 'Controllers\PersonController::route');
$app->options('/person/{uid}/photo', 'Controllers\PersonController::route');
$app->options('/person/{uid}/update', 'Controllers\PersonController::route');
$app->options('/members', 'Controllers\MemberController::route');
$app->options('/members/all', 'Controllers\MemberController::route');
$app->options('/members/current', 'Controllers\MemberController::route');
$app->options('/members/former', 'Controllers\MemberController::route');
$app->options('/members/candidate', 'Controllers\MemberController::route');


$app->run();