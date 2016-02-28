For up-to-date documentation & implementation status:
http://appzio.com/

To run the basic tests:
1) comment out the constants from top of the tests/basic.php
2) run this from the command line:
phpunit --stderr --bootstrap tests/bootstrap.php tests/basic.php


# Test setup 1
Use the provided keys within the basic.php (uncomment first), they connect with app.appzio.com server and tests should run without any errors

# Test setup 2
You can create a new app at appzio.com from the included zip-file and configuring the library to use the api keys of the new app. 


# Running the test
If everything works ok, you should be seeing a this kind of output:

phpunit --stderr --bootstrap tests/bootstrap.php tests/basic.php
PHPUnit 4.7.7 by Sebastian Bergmann and contributors.

.........

Time: 7.39 seconds, Memory: 12.00Mb

OK (9 tests, 27 assertions)


# Errors
If something went wrong, it would look something like this:

Time: 25.93 seconds, Memory: 12.00Mb

There were 2 errors:

1) PHPSDKTestCase::testFbId
Undefined property: stdClass::$msg

/Users/trailo/dev/rest-bootstrap-php/tests/tests.php:245

2) PHPSDKTestCase::testFbToken
Undefined property: stdClass::$msg

/Users/trailo/dev/rest-bootstrap-php/tests/tests.php:258

FAILURES!
Tests: 13, Assertions: 35, Errors: 2.


# Troubleshooting

Errors can be caused by:
- incorrect test app configuration (see the attached app template)
- api bugs
- incorrect test setups
- api incompatibility (shouldn't happen)

The test time is a good indication whether everything works as it should. Naturally the speed of your environment affects a lot. Here are couple indicative times for the current version:

Local development environment, debug enabled, with caching:
Time: 7.39 seconds, Memory: 12.00Mb

App server, optimum connectivity, server debug disabled, with caching:
Time: 21.11 seconds, Memory: 12.25Mb
