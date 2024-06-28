<?php

// Load Slim
require_once('vendor/autoload.php');

$app = Slim\Factory\AppFactory::create();

//get(url, function);
//post(url, function);
//put(url, function);
//patch(url, function);

//basic info = uid, href, name, email, avg_email, photo_visible, membership

//auths:
//bestuur: members of beheer or bestuur
//ictcom: members of beheer, bestuur or ictcom
//lid: members of the society (does not include candidate-members)
//bekend: members and candidate-members of the society
$app->get('/persons', 'controllers\PersonController::route'); //return all persons with basic info
$app->get('/persons/all', 'controllers\PersonController::route'); //return all persons with all information, ex avg
$app->post('/person', 'controllers\PersonController::route'); //create new person
$app->get('/person/{uid}', 'controllers\PersonController::route'); //return person with basic info
$app->get('/person/{uid}/all', 'controllers\PersonController::route'); //return person with alll info ex avg
$app->get('/person/{uid}/photo/{width}/{height}', 'controllers\PersonController::route'); //return persons profile picture
$app->patch('/person/{uid}/update', 'controllers\PersonController::route'); //update person information
$app->get('/members', 'controllers\MemberController::route'); //return all members with basic info
$app->get('/members/all', 'controllers\MemberController::route'); //return all members with all info ex avg
$app->get('/members/current', 'controllers\MemberController::route'); //return all current members with basic info
$app->get('/members/former', 'controllers\MemberController::route'); //return all former members with basic info
$app->get('/members/candidate', 'controllers\MemberController::route'); //return all candidate members with basic info

$app->run();