# Autoban
Service to prevent bot activity on your site.

!!! PERMANENTLY MOVED TO NEW ADDRESS: http://git.khoz.ru/Khoz/autoban.git

INSTALLATION
------------

Autoban has four components:
1. Service. Operate requests from you sites. Including autoban.go and config.json. Configure file and set rootKey in autoban.go to normal work of reloading configiration by request. Use port :8080, which set in sources. Just run.

2. MySQL base. Structure and minimal data are in mysql_create_base.sql. Creating users done with webadmin interface

3. Webadmin. Create users, keys for site, statistic and mode. Store in ./www folder. Run on you web server, which can access to your mysql base (2). Check files (./www/functions.php - function serverUIDreload, and ./www/i/safemysql.class.autoban.php - mysql connection settings).

4. Scripts runs from you websites where necessary to clean from bots. Are in folder ./service
	test_autoban_sendip.php - only send the info for capitalize the statistic
	test_autoban_checkip.php - send ip of client and wait for status - approve or denie
	test_autoban_reload.php - start once to make server reload keys information

Check the uid and your service url in these files.

All of them do not show any information in answer of web request and invisible for user.
But you can call two of them (test_autoban_sendip.php and test_autoban_checkip.php) for creating/removing cookie that you are an admin and should not be checked throught autoban service.

The scripts (test_autoban_sendip.php and test_autoban_checkip.php) should be called from php script at the next line after <?php header:
include "$_SERVER[DOCUMENT_ROOT]/test_autoban_checkip.php";

phpBB forum
If you use phpBB forum you should turn on super_global var in config file:
In /config/parameters.yml set core.disable_super_globals: false

Joomla
How to change index.php you also know, but how to change templates - see below. Don't forget to renew the cache.
Change \forum\styles\prosilver\template\overall_header.html by adding to header <!-- INCLUDEPHP /path/to/script/autoban.php -->

Call http://site/test_autoban_checkip.php?action=turnoff to set OFF the checking of you and http://site/test_autoban_checkip.php?action=turnoff to turnon checking back.



REQUIREMENTS
------------

1. Service - every system supported with golang complator.
2. MySQL server
3. Webadmin - apache or nginx with php5+
4. Scrpits - apache or nginx with php5+

QUICK START
-----------

Not so easy as you think. Gotta try a little.