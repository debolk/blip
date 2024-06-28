<?php

// Load Slim
require_once('vendor/autoload.php');

$app = Slim\Factory\AppFactory::create();

//get(url, function);
//post(url, function);
//put(url, function);
//patch(url, function);

//basic info = uid, href, name, email, avg_email, photo_visible

//auths:
//bestuur: members of beheer or bestuur
//ictcom: members of beheer, bestuur or ictcom
//lid: members of the society (does not include candidate-members)
//bekend: members and candidate-members of the society
$app->get('/persons', array($cont, 'processRequest')); //return all persons with basic info
$app->get('/persons/all', array($cont, 'processRequest')); //return all persons with all information, ex avg
$app->post('/person', array($cont, 'processRequest')); //create new person
$app->get('/person/{uid}', array($cont, 'processRequest')); //return person with basic info
$app->get('/person/{uid}/all', array($cont, 'processRequest')); //return person with alll info ex avg
$app->get('/person/{uid}/photo/{width}/{height}', array($cont, 'processRequest')); //return persons profile picture
$app->patch('/person/{uid}/update', array($cont, 'processRequest')); //update person information
$app->get('/members', array($cont, 'processRequest')); //return all members with basic info
$app->get('/members/all', array($cont, 'processRequest')); //return all members with all info ex avg
$app->get('/members/current', array($cont, 'processRequest')); //return all current members with basic info
$app->get('/members/former', array($cont, 'processRequest')); //return all former members with basic info
$app->get('/members/candidate', array($cont, 'processRequest')); //return all candidate members with basic info

$app->run();