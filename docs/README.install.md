sudo dpkg -i docker*.deb
sudo apt --fix-broken install -y
sudo systemctl enable docker
sudo systemctl start docker
sudo systemctl status docker


# 1️⃣ Créer le network (si tu veux que les conteneurs communiquent par noms)
docker network create express-network

# 2️⃣ Charger les images
docker load -i express-sale-all-images.tar

# 3️⃣ Lancer la base de données
docker run -d \
  --name express_db_custom \
  --network express-network \
  -e POSTGRES_USER=express_sale_user \
  -e POSTGRES_PASSWORD=express_sale_mdp \
  -e POSTGRES_DB=express_sale_db \
  express-sale-postgres

# 4️⃣ Lancer le backend
docker run -d \
  --name express_backend \
  --network express-network \
  -p 8000:8000 \
  express-sale-backend

# 5️⃣ Lancer le frontend
docker run -d \
  --name express_frontend \
  --network express-network \
  -p 3000:80 \
  express-sale-frontend
