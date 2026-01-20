#!/bin/bash

# Script de déploiement pour API Platform
# Usage: ./scripts/deploy.sh [environment]

set -e

ENVIRONMENT=${1:-prod}
APP_DIR="/var/www/gestion-demande"

echo "🚀 Déploiement de l'application en mode $ENVIRONMENT..."

# Vérifier que Docker est installé
if ! command -v docker &> /dev/null; then
    echo "❌ Docker n'est pas installé"
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose n'est pas installé"
    exit 1
fi

# Aller dans le répertoire de l'application
cd "$APP_DIR" || exit 1

echo "📦 Construction de l'image Docker..."
docker-compose build

echo "🔄 Arrêt des conteneurs existants..."
docker-compose down

echo "▶️  Démarrage des conteneurs..."
docker-compose up -d

echo "⏳ Attente du démarrage de PHP-FPM..."
sleep 5

echo "📥 Installation des dépendances Composer..."
if [ "$ENVIRONMENT" = "prod" ]; then
    docker-compose exec -T php composer install --no-dev --optimize-autoloader --no-interaction
else
    docker-compose exec -T php composer install --optimize-autoloader --no-interaction
fi

echo "🔧 Configuration des permissions..."
docker-compose exec -T php chown -R www-data:www-data /var/www/html
docker-compose exec -T php chmod -R 755 /var/www/html
docker-compose exec -T php chmod -R 775 /var/www/html/var

echo "🗑️  Vidage du cache Symfony..."
docker-compose exec -T php php bin/console cache:clear --env=$ENVIRONMENT --no-debug

echo "🔥 Préchargement du cache..."
docker-compose exec -T php php bin/console cache:warmup --env=$ENVIRONMENT --no-debug

echo "🔄 Redémarrage de PHP-FPM pour activer OPcache..."
docker-compose restart php

echo "✅ Déploiement terminé avec succès!"

echo ""
echo "📊 Vérification de l'état des conteneurs:"
docker-compose ps

echo ""
echo "🔍 Vérification d'OPcache:"
docker-compose exec -T php php -i | grep -E "opcache.enable|opcache.memory_consumption" || true
