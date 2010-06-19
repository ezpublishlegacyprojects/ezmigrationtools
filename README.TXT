################################################################################																			  #									       #	
#             		eZ Migration Tools 2		  		       #
#									       #
#		Helps performing eZ Publish website migration		       #	
#									       #
#			   Version 2.0					       #
#			Author : Jean-Luc Chassaing			       #
################################################################################

This extension has been written to help performing eZ Publish version upgrade
in mulitdatabase configurations. It can also be used for single database
websites.

1/ What does it doo

Four actions are performed to execute the complet upgrade.
	1st - get the database list and their siteaccess ;
	2nd - perform a dump of all found databases ;
	3rd - apply all the sql scripts on all the databases ;
	4th - apply all the php scripts on all the siteaccesses.

2/ how to do the job

When you use this extension you must have prepared your website for the upgrade.
It means that you must have downloaded the eZ Publish version you want your
website to be upgraded to.

Then you must have uncompressed it and copied all your specific extensions to 
the extension folder dooing so for your desings in the design folder and all 
your settings overrides and siteaccesses.

To be clear, you must have done all what is described in the upbgrading howto 
found on the ezwebsite. This extension can be used to apply all the sql and php
scripts. 

3/ installation

You must put the extension in your extension folder. Be sure to ceek the 
"ezmigrationtools" extension directory name. then add the extension to your
settings/override/site.ini.append.php file. by adding the line :
	ActiveExtension[]=ezmigrationtools

then upgrade the autolad :
	php bin/php/ezpgenerateautoloads.php

4/ configuration

The settings/migration.ini file is made to configure all the version migration
scripts. Be sure that your version is configured. Feel free to set up the 
version migration you want to perform.

5/ running

To execute the migration you must execute :

php extension/ezmigrationtools.php

you can run -h to get some help.

thie -d option runs the upgrade in debug mode, all the exection lines will be 
displayed but not executed.

the -x option prevents the database dump to be performed.

Once you've lunched the script, an interactive menu will drive you true the 
upgrading phases.
