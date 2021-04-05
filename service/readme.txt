Scripts for work on server side:

1. test_autoban_sendip.php - only send the info for capitalize the statistic
2. test_autoban_checkip.php - send ip of client and wait for status - approve or denie
3. test_autoban_reload.php - start once to make server reload keys information

All of them do not show any information in answer of web request and invisible for user.
But you can call two of them (test_autoban_sendip.php and test_autoban_checkip.php) for creating/removing cookie that you are an admin and should not be checked throught autoban service.

Call http://site/test_autoban_checkip.php?action=turnoff to set OFF the checking for you and http://site/test_autoban_checkip.php?action=turnoff to turnon checking back.

The scripts (test_autoban_sendip.php and test_autoban_checkip.php) should be called from php script at the next line after <?php header:
include "$_SERVER[DOCUMENT_ROOT]/test_autoban_checkip.php";

phpBB forum

If you use phpBB forum you should turn on super_global var in config file:
In /config/parameters.yml set core.disable_super_globals: false


Joomla
How to change index.php you also know, but how to change templates - see below. Don't forget to renew the cache.
Change \forum\styles\prosilver\template\overall_header.html by adding to header <!-- INCLUDEPHP /path/to/script/autoban.php -->
