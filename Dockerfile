# Dockerfile — Apache + PHP 8.1
FROM php:8.1-apache

# Instala dependências e extensões necessárias
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql

# Copia código para o diretório público do Apache
WORKDIR /var/www/html
COPY htdocs/ /var/www/html/

# Cria directórios de uploads e logs e garante permissões
RUN mkdir -p /var/www/html/uploads /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs \
    && chmod -R 755 /var/www/html/uploads /var/www/html/logs

# Habilita mod_rewrite (se precisares)
RUN a2enmod rewrite

# Expor porta HTTP (Render mapeia para o PORT)
EXPOSE 80

# Comando default (o Render define a variável PORT no ambiente)
CMD ["apache2-foreground"]
