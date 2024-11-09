# Utiliser l'image PHP avec Apache
FROM php:8.2-apache

# Évite un message d'erreur en console
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && docker-php-ext-install pdo pdo_mysql

# Installer Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configurer le répertoire de travail
WORKDIR /var/www/html

# Copier le code source
COPY . .

# Exécuter composer install seulement au démarrage du conteneur
RUN git config --global --add safe.directory /var/www/html

# Donner les bons droits
RUN chown -R www-data:www-data /var/www/html

# Exposer le port
EXPOSE 80

# Démarrer le conteneur avec une commande personnalisée
CMD bash -c "until nc -z petscare-users 3306; do sleep 2; done && composer install && apache2-foreground"
