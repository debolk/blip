<?php

// Load Tonic
require_once('vendor/autoload.php');

$app = new Tonic\Application(array('load' => array('helper/*.php', 'mailer/NewPerson.php', 'models/LdapObject.php', 'models/*.php', 'Person.php', 'Member.php', 'Photo.php')));
$request = new Tonic\Request();

$resource = $app->getResource($request);
$response = $resource->exec();
$response->AccessControlAllowOrigin = '*';
$response->AccessControlAllowMethods = "GET, POST, PUT, PATCH";
if ($response->ContentType == "text/html") {
    $response->ContentType = 'application/json';
}
$response->output();
