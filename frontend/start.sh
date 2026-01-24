#!/bin/bash

export SERVER_IP=$(hostname -I | awk '{print $1}')
export VITE_API_URL="http://${SERVER_IP}:8000/api"

echo "🚀 Frontend lancé"
echo "🌐 API URL = $VITE_API_URL"
echo "📱 Accès réseau : http://${SERVER_IP}:3000"

npm run dev -- --host
