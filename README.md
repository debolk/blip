Blip
====

"Blip refers to the dots drawn on early radars based on plan position indicator (PPI) displays." - Wikipedia

## About Blip
Blip (Bolks ledeninformatiepunt) offers a Facade-pattern for the LDAP-install of De Bolk. Blip simplifies the usage of the LDAP-install and enforces some additional business logic. All modifications of members should be done through Blip.

## Technology
Blip is a REST API based on Tonic. It uses Composer to manage its dependencies.

## License
Copyright 2013 Jakob Buis. Released under the GPLv3 (http://www.gnu.org/licenses/gpl.html).

## Installing and deployment
Installing and deploying Blip is easy. Install apache and [Composer](http://getcomposer.org/) and run 

    composer install

to install all dependencies. Copy .htaccess.sample to .htaccess and adapt as needed. Point an apache virtual host to the root directory (containing .htaccess and dispatch.php).

## Testing
Blip is tested using PHPUnit. Testcases are stored in ./test. Execute

    vendor/bin/phpunit -c test/config.xml

on the commandline to run the tests.
