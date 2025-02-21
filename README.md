Blip
====

"Blip refers to the dots drawn on early radars based on plan position indicator (PPI) displays." - Wikipedia

## About Blip
Blip (Bolks ledeninformatiepunt) offers a Facade-pattern for the LDAP-install of De Bolk. Blip simplifies the usage of the LDAP-install and enforces some additional business logic. All modifications of members should be done through Blip.

## Technology
Blip is a REST API based on the Slim4 framework. It uses Composer to manage its dependencies.

## License
Copyright 2013 Jakob Buis. Released under the [GNU General Public License version 3](http://www.gnu.org/licenses/gpl.html).

## Installing and deployment
Installing and deploying Blip is easy.

1. Install Apache or Nginx and PHP8.4. Blip depends on PHP 8.4 or later and PHP's LDAP extension.
1. Install PHP memcache extension and memcached. Due to LDAP's idiosyncrasies, Blip will be unuseably slow without it.
1. Execute `php ./composer.phar install --no-dev --optimize-autoloader` in the project root to install all dependencies using [Composer](http://getcomposer.org/).
1. Setup nginx or apache2 for use with Blip.

## Testing
Blip is tested using PHPUnit. Testcases are stored in ./test. Execute `vendor/bin/phpunit --colors test` on the command line to run the tests.

## Debugging
You can setup a debug access token which is always valid.
Do not use this in a production server!

E.g.:
curl -X GET "https://blip.example.org/person/{uid}/all?access_token=debugaccess"
curl -X POST -H "Content-Type: application/json" -d '{"key":"value"}' https://blip.example.org/person?access_token=debugaccess
curl -X PATCH -H "Content-Type: application/json" -d '{"key":"value"}' https://blip.example.org/person/{uid}/update?access_token=debugaccess