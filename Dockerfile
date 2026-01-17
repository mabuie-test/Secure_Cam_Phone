# Dockerfile — Apache + PHP 8.1 (ajustado para a tua estrutura)
FROM php:8.1-apache

# Instala extensões necessárias
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql

# Definir working dir e copiar o conteúdo do teu backend
WORKDIR /var/www/html

# Ajustado para a estrutura: copia php-backend/public_html para /var/www/html
COPY php-backend/public_html/ /var/www/html/

# Cria directórios de uploads e logs e garante permissões
RUN mkdir -p /var/www/html/uploads /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs \
    && chmod -R 755 /var/www/html/uploads /var/www/html/logs

# Habilita mod_rewrite se necessário
RUN a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]
