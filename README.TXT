###############################################################################
#																			  #
#             			PLAN OPERATION MIGRATION   							  #
#																			  #
#				Migration de eZ Publish 4.0.3 vers 4.1.3                      #	
#									  										  #
###############################################################################

1/ Déposer les sources dans un nouveau répertoire

	1.1/ S'assurer que les sources ont été correctement modifiées :
		Les scripts sql présents dans update/database/mysql/4.1/ doivent être 
		modifiés de façon à commenter les lignes devant être appliquées pour des
		version anterieurs d'ezpublish.
		
	1.2/ Les nouvelles extensions :
		S'assurer ques les nouvelles extensions sont présentes à savoir :
		   - ezoe (online editor)
		   - ezwt (toolbar)

2/ Copier les répertoires extension, settings et var
		
	2.1/ Extensions
		
		cp [folderOrig]/extension/gendarmerie [folderDest]/extension
		cp [folderOrig]/extension/gendarmerie_sec [folderDest]/extension
	
	2.2/ Settings
	
		cp [folderOrig]/settings/override [folderDest]/settings/
		cp [folderOrig]/settings/siteaccess [folderDest]/settings/
		
    2.3/ Var
     
        cp [folderOrig]/var [folderDest]/
        
3/ Prise en charge des nouvelles extensions
	
	3.1/ Riche Text :
		l'extension ezoe remplace l'extension ezdhtml
		
		dans le fichier "settings/override/site.ini.append.php, à la ligne 
		ActiveExtensions[]=ezdhtml remplacer ezdhtml par ezoe

	3.2/ Outil de migration :
		ajouter la ligne : ActiveExtensions[]=ezmigrationtools dans le fichier
		settings/override/site.ini.append.php

    3.3/ Tool bar :
    	remplacer  "ezwebsitetoolbar" dans la configuration 
    	modifier dans tous les fichiers de configuration site.ini.append.php
    	pour cela, on poura utiliser le script updatetoolbar 
    	
	3.4/ Vider les caches du site :
    	php bin/php/ezcache.php --clear-all --purge
    
    3.5/ Régénérer les autoloads :
    	php bin/php/ezpgenerateautoloads.php
    	
4/ Migrer le site 

	4.1/ Fichier de configuration :
		S'assurer que le fichier de configuration de l'outil de migration est 
		correct :
			extension/ezmigrationtools/settings/migration.ini
		
	4.2/ Exécuter le script de migration :
		php extension/ezmigrationtools/bin/php/ezversionmigration.php 
	
	4.3/ Exécuter le script de correction des droits :
	    vérifier la configuration des bases de données dans updatepolicies
	      puis exécuter : 
	      
			./extension/ezmigrationtools/bin/linux/updatepolicies
		
		

5/ Vider les caches du site :

	php bin/php/ezcache.php --clear-all --purge

6/ Modification des règles de réécriture
    
	Ajouter la règle de réécriture suivante :
		RewriteRule ^/var/[^/]+/cache/public/.* - [L]

7/ Mise en prod des nouvelles sources :
	
	- Faire éventuellement une sauvegarde des anciennes sources.
	- Renomer le répertoire des anciennes sources
	- Renomer le répertoires des nouvelles sources en lui donnant celui de 
	  l'ancien.
	  
8/ Accéder au site :

	1/ Test des différents accès, front, back, edition
	
	2/ Mise à jour des droits dans les différents sous sites pour la prise en 
		charge des nouvelles extensions.
		