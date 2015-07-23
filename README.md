For up-to-date documentation & implementation status:
http://appzio.com/

To run the phpunit:
phpunit --stderr --bootstrap tests/bootstrap.php tests/tests.php

# Test setup 1
Use the provided keys within the tests.php, they connect with Finnish server and tests should run with two Facebook errors only

# Test setup 2
You can create a new app at appzio.com from the included zip-file and configuring the library to use the api keys of the new app. Note that you need to create, configure and add keys for Facebook connection to work.


If everything works ok, you should be seeing a this kind of output:



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



If you haven't configured the Facebook api & inputted a valid token, you would be seeing these errors. Any other errors can be caused by:
- incorrect test app configuration (see the attached app template)
- api bugs
- incorrect test setups
- api incompatibility (shouldn't happen)

The test time is a good indication whether everything works as it should. Naturally the speed of your environment affects a lot. Here are couple indicative times for the current version:

Local development environment, debug enabled, no caching:
Time: 25.93 seconds

Finnish server, optimum connectivity, server debug enabled:
Time: 16.19 seconds

Finnish server, optimum connectivity, server debug disabled:
Time: 8.31 seconds
