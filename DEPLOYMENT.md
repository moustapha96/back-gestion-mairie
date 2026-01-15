# Guide de déploiement - API Platform

Ce guide explique comment déployer l'application avec Docker, OPcache activé et Nginx sécurisé.

## 📋 Prérequis

- Docker et Docker Compose installés
- Nginx installé sur le serveur
- Certificats SSL Let's Encrypt configurés

## 🚀 Déploiement

### 1. Configuration de l'environnement

Créez un fichier `.env` à la racine du projet avec les variables nécessaires :

```env
APP_ENV=prod
APP_DEBUG=0
DATABASE_URL=mysql://user:password@host:3306/database
ELECTEURS_DATABASE_URL=mysql://user:password@host:3306/database_electeurs
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase
```

### 2. Construction de l'image Docker

```bash
docker-compose build
```

### 3. Démarrage des conteneurs

```bash
docker-compose up -d
```

### 4. Configuration Nginx

Copiez la configuration Nginx renforcée :

```bash
sudo cp nginx/backendgl.kaolackcommune.sn.conf /etc/nginx/sites-available/backendgl.kaolackcommune.sn
sudo ln -s /etc/nginx/sites-available/backendgl.kaolackcommune.sn /etc/nginx/sites-enabled/
```

Testez la configuration :

```bash
sudo nginx -t
```

Rechargez Nginx :

```bash
sudo systemctl reload nginx
```

### 5. Configuration des permissions

```bash
docker-compose exec php chown -R www-data:www-data /var/www/html
docker-compose exec php chmod -R 755 /var/www/html
docker-compose exec php chmod -R 775 /var/www/html/var
```

### 6. Installation des dépendances (si nécessaire)

```bash
docker-compose exec php composer install --no-dev --optimize-autoloader
```

### 7. Cache Symfony (production)

```bash
docker-compose exec php php bin/console cache:clear --env=prod
docker-compose exec php php bin/console cache:warmup --env=prod
```

## 🔧 Configuration OPcache

OPcache est automatiquement activé dans le conteneur Docker avec les paramètres suivants :

- **Mémoire** : 256 MB
- **Fichiers max** : 20000
- **Validation timestamps** : Désactivée en production (performance optimale)

Pour modifier la configuration, éditez `php/opcache.ini` et redémarrez le conteneur :

```bash
docker-compose restart php
```

## 🔒 Sécurité Nginx

La configuration Nginx inclut :

- ✅ Headers de sécurité (X-Frame-Options, CSP, HSTS, etc.)
- ✅ Masquage de la version Nginx
- ✅ Blocage des fichiers sensibles (.env, .git, etc.)
- ✅ Limitation de la taille des requêtes
- ✅ Timeouts configurés
- ✅ Compression Gzip
- ✅ Cache pour les assets statiques
- ✅ SSL/TLS renforcé (TLS 1.2 et 1.3 uniquement)

## 📊 Monitoring

### Vérifier l'état des conteneurs

```bash
docker-compose ps
```

### Vérifier les logs

```bash
docker-compose logs -f php
```

### Vérifier OPcache

```bash
docker-compose exec php php -i | grep opcache
```

### Status PHP-FPM

Accédez à `/status` (si configuré) pour voir les statistiques PHP-FPM.

## 🔄 Mise à jour

### Mettre à jour le code

```bash
git pull
docker-compose exec php composer install --no-dev --optimize-autoloader
docker-compose exec php php bin/console cache:clear --env=prod
docker-compose exec php php bin/console cache:warmup --env=prod
docker-compose restart php
```

### Reconstruire l'image

```bash
docker-compose build --no-cache
docker-compose up -d
```

## 🐛 Dépannage

### Le conteneur ne démarre pas

```bash
docker-compose logs php
```

### OPcache ne fonctionne pas

Vérifiez que l'extension est chargée :

```bash
docker-compose exec php php -m | grep opcache
```

### Problèmes de permissions

```bash
docker-compose exec php chown -R www-data:www-data /var/www/html/var
docker-compose exec php chmod -R 775 /var/www/html/var
```

## 📝 Notes importantes

- En production, `opcache.validate_timestamps=0` pour de meilleures performances
- Pour voir les changements immédiatement en développement, mettez `opcache.validate_timestamps=1`
- Le cache OPcache doit être vidé après chaque déploiement en production
- Utilisez `composer install --no-dev` en production pour exclure les dépendances de développement
