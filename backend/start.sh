#!/bin/bash

# Détecter l'IP automatiquement
export SERVER_IP=$(hostname -I | awk '{print $1}')
export SERVER_URL="http://${SERVER_IP}:8000"

echo "🚀 Serveur démarré sur: $SERVER_URL"
echo "📱 Accès client: http://${SERVER_IP}:3000"

# Démarrer le serveur
php artisan serve --host=0.0.0.0 --port=8000