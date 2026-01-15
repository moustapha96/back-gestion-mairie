# Configuration Docker, OPcache et Nginx - Résumé

## ✅ Fichiers créés

### 1. **Dockerfile**
- Image PHP 8.2-FPM avec OPcache activé
- Composer installé dans le conteneur
- Extensions PHP nécessaires (pdo_mysql, mbstring, gd, zip, opcache, etc.)
- Configuration OPcache optimisée pour la production
- Configuration PHP-FPM pour la production
- Permissions configurées automatiquement

### 2. **docker-compose.yml**
- Service PHP avec OPcache activé
- Variables d'environnement pour OPcache
- Healthcheck configuré
- Réseau Docker configuré
- Service MySQL optionnel (commenté)

### 3. **php/opcache.ini**
- Configuration OPcache détaillée
- Paramètres optimisés pour la production
- 256 MB de mémoire allouée
- 20000 fichiers maximum en cache
- Validation des timestamps désactivée en production

### 4. **nginx/backendgl.kaolackcommune.sn.conf**
Configuration Nginx renforcée avec :

#### 🔒 Sécurité
- Headers de sécurité (X-Frame-Options, CSP, HSTS, etc.)
- Masquage de la version Nginx
- Blocage des fichiers sensibles (.env, .git, config/, src/, etc.)
- Limitation de la taille des requêtes (20M)
- Timeouts configurés
- SSL/TLS renforcé (TLS 1.2 et 1.3 uniquement)
- Rate limiting (optionnel, commenté)

#### ⚡ Performance
- Compression Gzip activée
- Cache pour les assets statiques (1 an)
- HTTP/2 activé
- Optimisations FastCGI

#### 📝 Configuration Symfony
- Routing correctement configuré
- PHP-FPM sur le port 9000
- Blocage des autres fichiers .php

### 5. **.dockerignore**
- Exclusion des fichiers inutiles de l'image Docker
- Réduction de la taille de l'image

### 6. **scripts/deploy.sh**
- Script de déploiement automatisé
- Gestion des environnements (prod/dev)
- Installation des dépendances
- Configuration des permissions
- Cache Symfony

### 7. **DEPLOYMENT.md**
- Guide complet de déploiement
- Instructions détaillées
- Commandes de dépannage

## 🚀 Utilisation

### Construction et démarrage

```bash
# Construire l'image
docker-compose build

# Démarrer les conteneurs
docker-compose up -d

# Vérifier l'état
docker-compose ps
```

### Configuration Nginx

```bash
# Copier la configuration
sudo cp nginx/backendgl.kaolackcommune.sn.conf /etc/nginx/sites-available/backendgl.kaolackcommune.sn

# Créer le lien symbolique
sudo ln -s /etc/nginx/sites-available/backendgl.kaolackcommune.sn /etc/nginx/sites-enabled/

# Tester la configuration
sudo nginx -t

# Recharger Nginx
sudo systemctl reload nginx
```

### Déploiement automatisé

```bash
# Rendre le script exécutable (sur Linux)
chmod +x scripts/deploy.sh

# Exécuter le déploiement
./scripts/deploy.sh prod
```

## 📊 Vérification OPcache

```bash
# Vérifier que OPcache est activé
docker-compose exec php php -i | grep opcache

# Vérifier les statistiques OPcache
docker-compose exec php php -r "print_r(opcache_get_status());"
```

## 🔧 Configuration OPcache

Les paramètres OPcache sont configurables via les variables d'environnement dans `docker-compose.yml` :

- `PHP_OPCACHE_ENABLE=1` : Active OPcache
- `PHP_OPCACHE_MEMORY_SIZE=256` : Mémoire en MB
- `PHP_OPCACHE_MAX_ACCELERATED_FILES=20000` : Nombre max de fichiers
- `PHP_OPCACHE_VALIDATE_TIMESTAMPS=0` : Désactive la validation (production)

## ⚠️ Notes importantes

1. **Production** : `opcache.validate_timestamps=0` pour de meilleures performances
2. **Développement** : Mettre `opcache.validate_timestamps=1` pour voir les changements
3. Après chaque déploiement, redémarrer PHP-FPM pour vider OPcache :
   ```bash
   docker-compose restart php
   ```
4. Utiliser `composer install --no-dev` en production

## 🔄 Mise à jour

Pour mettre à jour l'application :

```bash
git pull
docker-compose exec php composer install --no-dev --optimize-autoloader
docker-compose exec php php bin/console cache:clear --env=prod
docker-compose exec php php bin/console cache:warmup --env=prod
docker-compose restart php
```

## 📝 Différences avec l'ancienne configuration Nginx

### Améliorations de sécurité
- ✅ Headers de sécurité ajoutés
- ✅ Blocage des fichiers sensibles renforcé
- ✅ SSL/TLS plus strict
- ✅ Masquage de la version Nginx

### Améliorations de performance
- ✅ Compression Gzip
- ✅ Cache pour les assets statiques
- ✅ Optimisations FastCGI
- ✅ HTTP/2 activé

### Améliorations de configuration
- ✅ Timeouts configurés
- ✅ Limites de taille de requête
- ✅ Configuration FastCGI détaillée
