<?php

// Setup
$I = new WebGuy($scenario);

// GET /persons 
$I->wantTo('validate an user exists');
$I->amOnPage('/persons?access_token=verysecret');
$I->see('Jakob');
