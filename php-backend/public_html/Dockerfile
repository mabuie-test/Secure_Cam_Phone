# php-backend/public_html/Dockerfile
FROM php:8.1-apache

# instalar extensões necessárias (PDO MySQL etc.)
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git libpng-dev libonig-dev libxml2-dev \
  && docker-php-ext-install pdo pdo_mysql \
  && rm -rf /var/lib/apt/lists/*

# definir diretório de trabalho (público do Apache)
WORKDIR /var/www/html

# copiar todo o conteúdo desta pasta (public_html) para /var/www/html
COPY . /var/www/html/

# criar dirs de uploads e logs e dar permissões seguras
RUN mkdir -p /var/www/html/uploads /var/www/html/logs \
  && chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs \
  && chmod -R 755 /var/www/html/uploads /var/www/html/logs

# habilitar rewrite (se usa .htaccess)
RUN a2enmod rewrite

EXPOSE 80

# start apache (com o user www-data configurado)
CMD ["apache2-foreground"]
