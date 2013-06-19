Blip
====

"Blip refers to the dots drawn on early radars based on plan position indicator (PPI) displays." - Wikipedia

## About Blip
Blip (Bolks ledeninformatiepunt) offers a Facade-pattern for the LDAP-install of De Bolk. Blip simplifies the usage of the LDAP-install and enforces some additional business logic. All modifications of members should be done through Blip.

## Technology
Blip is a REST API based on [Tonic](http://peej.github.io/tonic/). It uses Composer to manage its dependencies.

## License
Copyright 2013 Jakob Buis. Released under the [GNU General Public License version 3](http://www.gnu.org/licenses/gpl.html).

## Installing and deployment
Installing and deploying Blip is easy.

1. Install Apache and PHP5. Blip depends on PHP 5.4.7 or later and PHP's LDAP extensions installed.
1. Install [Composer](http://getcomposer.org/).
1. Run `composer install` to install all dependencies.
1. Copy .htaccess.example to .htaccess and adapt as needed.
1. Point an apache virtual host to the root directory (containing .htaccess and dispatch.php).

## Testing
Blip is tested using PHPUnit. Testcases are stored in ./test. Execute `vendor/bin/phpunit --colors test` on the command line to run the tests.
