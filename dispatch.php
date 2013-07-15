<?php

// Load Tonic
require_once('vendor/autoload.php');

$app = new Tonic\Application(array('load' => array('helper/*.php', 'models/LdapObject.php', 'models/*.php', 'Person.php', 'Member.php', 'Photo.php')));
$request = new Tonic\Request();

$resource = $app->getResource($request);
$response = $resource->exec();
$response->AccessControlAllowOrigin = '*';
if($response->ContentType == "text/html")
  $response->ContentType = 'application/json';
$response->output();
