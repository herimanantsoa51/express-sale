# Dockerfile
FROM postgres:15

# Copier le dump dans le dossier d'initialisation de PostgreSQL
COPY full_dump.sql /docker-entrypoint-initdb.d/
