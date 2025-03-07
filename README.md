# Créer une API avec Symfony 6 et API Platform 3
 
### YouTube

[![Vidéo](https://i3.ytimg.com/vi/cYoNDoa4_jE/maxresdefault.jpg)](https://www.youtube.com/watch?v=cYoNDoa4_jE)



# Documentation de l'API AuthenticPage

Cette documentation détaille comment configurer et utiliser l'API AuthenticPage avec Symfony 6 et API Platform 3.

## Table des Matières
1. [Prérequis](#prérequis)
2. [Démarrage](#démarrage)
3. [Configuration de la base de données](#configuration-de-la-base-de-données)
4. [Authentification JWT](#authentification-jwt)
5. [Création d'entités avec API Platform](#creation-dentites-avec-api-platform)
6. [Extensions Doctrine](#extensions-doctrine)
7. [Installation du client HTTP](#installation-du-client-http)
8. [Suppression du certificat local](#suppression-du-certificat-local)
9. [Ressources utiles](#ressources-utiles)

---

## Prérequis
- **Symfony 6** : Assurez-vous que Symfony est installé sur votre machine. Vous pouvez l'installer avec [Symfony CLI](https://symfony.com/download).
- **Composer** : Utilisez Composer pour gérer les dépendances PHP.
- **Serveur local** : Vous pouvez utiliser `symfony serve` ou tout autre serveur compatible.
- **API Platform 3** : Préinstallé avec les outils requis pour créer des ressources RESTful.

## Démarrage
Pour lancer l'application :
```bash
symfony serve
```

## Configuration de la base de données
1. Ouvrez le fichier `.env`.
2. Remplacez la configuration existante par vos propres informations de connexion à la base de données :
   ```env
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/nom_de_la_base"
   ```
3. Exécutez les migrations pour synchroniser la base de données avec vos entités :
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

## Authentification JWT
L'authentification est gérée avec le bundle [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle).

### Installation
```bash
composer require "lexik/jwt-authentication-bundle"
```

### Génération des clés JWT
Créez un dossier `jwt` dans le répertoire `config`, puis générez les clés privée et publique :
```bash
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```

Ou utilisez directement la commande du bundle :
```bash
php bin/console lexik:jwt:generate-keypair
```

### Configuration
Ajoutez la configuration dans `config/packages/lexik_jwt_authentication.yaml`.

## Création d'entités avec API Platform
Pour créer une entité qui expose des ressources API :
```bash
php bin/console make:entity --api-resource
```
Suivez les instructions pour définir vos champs et relations.

## Extensions Doctrine
Installez les extensions Doctrine pour enrichir vos entités avec des fonctionnalités comme les timestamps ou les slugs :
```bash
composer require stof/doctrine-extensions-bundle
```
Activez les extensions dans `config/packages/stof_doctrine_extensions.yaml`.

## Installation du client HTTP
Pour les appels HTTP sortants, utilisez le package Guzzle :
```bash
composer require guzzlehttp/guzzle
```

## Suppression du certificat local
Si vous avez besoin de réinstaller ou de supprimer le certificat local de Symfony :
```bash
symfony server:ca:uninstall
```

## Ressources utiles
- [Tutoriel Symfony Security Bundle](https://www.univ-orleans.fr/iut-orleans/informatique/intra/tuto/php/symfony-securitybundle-auth.html)
- [Documentation Lexik JWT](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/2.x/Resources/doc/index.rst#id15)
- [API Platform Guide](https://api-platform.com/docs/distribution)
- Vidéo explicative : [![Vidéo](https://i3.ytimg.com/vi/cYoNDoa4_jE/maxresdefault.jpg)](https://www.youtube.com/watch?v=cYoNDoa4_jE)

---

**AuthenticPage** permet aux institutions de valider et authentifier des documents officiels (par exemple, diplômes). Pour plus d'informations, veuillez consulter [www.authenticpage.com](https://www.authenticpage.com).




## GEDMO

composer require stof/doctrine-extensions-bundle

## Connexion 
https://www.univ-orleans.fr/iut-orleans/informatique/intra/tuto/php/symfony-securitybundle-auth.html



https://www.univ-orleans.fr/iut-orleans/informatique/intra/tuto/php/symfony-securitybundle-auth.html


https://www.youtube.com/watch?v=cYoNDoa4_jE&t=2s#




composer clear-cache

php bin/console cache:clear


<!-- install certificat -->

symfony.exe server:ca:install

php bin/console doctrine:database:create
 php bin/console doctrine:schema:create  

 verifier la base de donnees : php bin/console doctrine:schema:validate


-----------------------------------------

Installer smalot/pdfparser pour extraire le contenu du PDF :
Exécute cette commande avec Composer :

 composer require smalot/pdfparser --ignore-platform-req=ext-sodium