FROM php:8.3-fpm-bookworm

# Changer le miroir APT (Bookworm)
RUN sed -i 's|deb.debian.org|ftp.fr.debian.org|g' \
    /etc/apt/sources.list.d/debian.sources

RUN apt-get update && apt-get install -y \
    libpq-dev \
    wkhtmltopdf \
    fontconfig \
    fonts-dejavu \
    zip \
    unzip \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

COPY . .
COPY start.sh /start.sh
RUN chmod +x /start.sh

# 👇 AJOUTEZ CES LIGNES
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
USER www-data

EXPOSE 8000
CMD ["/start.sh"]
