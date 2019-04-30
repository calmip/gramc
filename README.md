
INSTALLATION DE gramc sur une Debian ou dérivés
===============================================

mysql
----- 

**ATTENTION**
- Ne fonctionne pas avec mysql 5.7 !
- Validé avec mariadb 10.0

php
---
- version minimale 5.5
- fonctionne en 7.0

~~~~
apt-get install libapache2-mod-php5 php7.0-intl php-symphony-yaml

pecl install yaml
service apache2 restart
apt-get install imagemagick
~~~~

Installer `wkhtmltopd` depuis https://wkhtmltopdf.org (disponible en .deb)

configuration apache2:
----
~~~~
a2enmod rewrite
<Location url-de-gramc>
   Options None
   <IfModule mod_rewrite.c>
      Options -MultiViews
      RewriteEngine On
      RewriteCond %{REQUEST_FILENAME} !-f
      # En mode dev: authentification "bidon" et quelques affichages de debug
      RewriteRule ^(.*)$ app_dev.php [QSA,L]
      # En mode prod: authentification Shibboleth SEULEMENT
      # RewriteRule ^(.*)$ app.php [QSA,L]
   </IfModule>
</location>
~~~~

Répertoire data:
-----

C'est dans ce répertoire que vont se retrouver:
- Les documents téléversés: rapports d'activité, ficher projet
- Les figures téléversées
- Le modèles de rapport d'activité


Créer le répertoire data:
~~~~
tar xfz data-dist.tar.gz
~~~~

Le répertoire data:
- *ne doit pas* être exporté par apache (cf. ci-dessus)
- peut appartenir à root
- Les sous-répertoires data/* *doivent* appartenir à `www-data` (ou le user qui exécute apache)
- www-data doit pouvoir écrire dedans:
  ~~~~
     chown root.root data
     chown -R www-data.www-data data/*
  ~~~~

Répertoires nécessaires à symfony:
---

`www-data` doit avoir le droit d'écrire dans certains répertoires:
~~~~
mkdir var/cache var/logs var/sessions
chown www-data.www-data var/cache var/logs var/sessions
~~~~

Configuration, personnalisation:
----

**Fichier parameters.yml:**
~~~~
cd app/config
cp parameters.yml.dist parameters.yml
~~~~

Editer le fichier et paramétrer l'application:
- Au moins les paramètres de connexion à la base de données
- Aussi le nom du mésocentre, quelques url, etc.
- Et pour finir les idp "préférés" (dépend des établissements à proximité du mésocentre)

**Bannière:**
Déposer votre fichier de bannière dans `web/icones/banniere.png` (cf. `banniere.png.dist` pour un modèle)

Base de données:
----

**Installation d'une base de donnees déjà en exploitation sur une instance de développement:**
~~~~
cd gramc
sudo rm -r var/sessions/* var/cache/* var/logs/*
cd reprise
php drop-db-recharge-pour-debug.php un-dump-de-la-bd.sql
cd ..
sudo rm -r var/sessions/* var/cache/* var/logs/*
~~~~

**ATTENTION**: Cette commande recharge la base de données, et CHANGE les adresses mail afin de s'assurer que les notifications ne sont pas envoyées réellement aux utilisateurs (utilisé pour le devt ou les tests)

**Installation d'une base de donnees vide:**
~~~~
cd gramc
sudo rm -r var/sessions/* var/cache/* var/logs/*
cd reprise
php drop-db-recharge-pour-debug.php gramc2.sql.dist
cd ..
sudo rm -r var/sessions/* var/cache/* var/logs/*
~~~~

Configuration du mail:
----

**Le serveur doit être capable d'envoyer des mails:**
  - Par exemple `postfix` fonctionne très bien avec gramc
  - Ou encore `ssmtp` (les mails sont envoyés, jamais reçus)

Fin de la configuration:
----

- Se connecter à gramc avec un navigateur: cliquer sur `connection (dbg)`
  **ATTENTION**: `app_dev.php` doit être activée dans la configuration apache ci-dessus
- Utilisateur = `admin`

Il reste maintenant à:
- Ajouter des utilisateurs administrateurs
- Ajouter des utilisateurs experts et les connecter avec des thématiques
- Ajouter des utilisateurs présidents
- Ajouter des laboratoires
- Configurer shibboleth (voir ci-dessous)


- Dans la configuration apache, remplacer app_dev.php par app.php (cf. ci-dessus)
- On peut maintenant supprimer le user admin admin et le laboratoire GRAMC qui ne servent plus à rien

CONFIGURATION DE SHIBBOLETH:
----

- Installer quelques paquets supplémentaires:
  ~~~~
  apt-get install libapache2-mod-shib2 liblog4shib1v5 libshibsp-plugins libshibsp7 shibboleth-sp2-common shibboleth-sp2-utils
  ~~~~
- Configuration apache Ajouter dans la section VirtualHost de gramc2:
  ~~~~
  # important pour pouvoir utiliser d'autres techniques d'authentification (cf. pour git)
  ShibCompatValidUser On

  <Location "url-de-gramc/login">
       AuthType shibboleth
       ShibRequestSetting requireSession 1
       ShibRequestSetting applicationId default
       Require shibboleth
  </Location>
  ~~~~
- Redémarrer apache:
  ~~~~
  systemctl restart apache2
  ~~~~

OU EST LE CODE DE GRAMC ?
=========================
gramc2 est une application symfony, il repose donc sur le patron de conception MVC. Les principaux répertoires sont les suivants:

        src/AppBundle                   Le code php de l'application
        src/AppBundle/Controller        Tous les contrôleurs (les points d'entrée de chaque requête)
        src/AppBundle/Entity            Les objets permettant de communiquer avec la base de données en utilisant l'ORM Doctrine 
                                        (un objet par table, un membre par champ)
        src/AppBundle/Form              Les formulaires (correspondent aux entités)
        src/AppBundle/Repository        quelques fonctions non standards d'accès à la base de données
        src/AppBundle/Workflow          Les workflows de l'application 
                                        (changement d'états des objets Projet, Version, Rallonge)
        src/AppBundle/Utils             Des trucs bien utiles
        src/AppBundle/DataFixtures      Mise à jour de la base de données lors des changements de version
        src/XXX                         Le code php "extérieur" utilisé par gramc2


        app/Resources/views             Les vues, c'est-à-dire tous les affichages, écrits en html/twig
        app/Resources/default           Les vues de la page d'accueil, de l'aide, etc., et les bouts de code utilisés partout (base.html.twig etc)
        appResources/xxx                Les vues correspondant aux principaux écrans - Voir dans les controleurs pour savoir quelle vue est utilisée à quel moment

        app/config/config.yml           Fichier de paramètres, ne doit pas être normalement édité
        app/config/parameters.yml       Fichier de paramètres, doit être édité à l'installation de gramc

        web                             Accessible directement par apache2
        web/icones                      Les icones (png)
        web/js                          Le code javascript
        web/rm                          Les modèles de rapports d'activité à télécharger

        var                             le cache, les sessions php, les fichiers log
                                        Il faut **SUPPRIMER** le cache lors des mises à jour, sinon la mise à jour n'est pas correcte !

        vendor                          Le code de symfony
        bin/console                     L'application ligne de commande de symfony, utile lors des mises à jour ou des rechargements de base de donnée

        reprise                         Le code permettant de recharger la base de données, soit pour initialiser gramc, soit pour installer une copie de la production (pour test et debug par exemple)

COMMENT MODIFIER LE CODE ?
----
- Editer dans:
  - `src/AppBundle`
  - `app/Resources/views`
  - `web`
- Pour comprendre ce qui se passe:
        `Functions::debugMessage(__METHOD__ .":" . __LINE__ . " bla bla " . $variable );`
        La sortie se trouve dans le journal (Ecrans d'administration)
- OU
        Les logs apache2
- OU
        Les logs symfony: dans var/log
- Pour savoir quel contrôleur est appelé (et accéder au code), regarder le bandeau de Symfony en bas de page (mode Debug seulement)

COMMENT ACCEDER AUX PARAMETRES:
----
- En php -> if ( AppBundle::hasParameter('un_parametre')) $un_parametre = AppBundle::getParameter('un_parametre');
- En twig -> {% if un_parametre != null %} ... {{ un_parametre }} .... {% endif %}

COMMENT AJOUTER UN PARAMETRE DANS parameter.yml:
----
- Ajouter le paramètre aux fichiers parameters.yml, parameters.yml.dist, ET config.yml

COMMENT FAIRE DES ACTIONS EN FONCTION DU ROLE:
----
- En php -> if( AppBundle::isGranted('ROLE_ADMIN') )
            les rôles sont décrits ici: src/AppBundle/Entity/Individu.php
- En twig -> {% if is_granted('ROLE_ADMIN') %} ... {% endif %}

COMMENT SAVOIR SI ON EST CONNECTE:
----
- En php -> if (AppBundle::isGranted( 'IS_AUTHENTICATED_FULLY' ) ) ...;
- En twig -> {% if is_granted('IS_AUTHENTICATED_FULLY') %} ... {% endif %}

emmanuel.courcelle@inp-toulouse.fr

