# php-backend/public_html/Dockerfile
FROM php:8.1-apache

# instalar dependências necessárias e libs para PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip unzip git \
    libpng-dev libonig-dev libxml2-dev \
  && rm -rf /var/lib/apt/lists/*

# instalar extensões PHP: pgsql, pdo_pgsql, pdo_mysql (se precisar)
RUN docker-php-ext-install pgsql pdo_pgsql pdo pdo_mysql

# definir diretório de trabalho (público do Apache)
WORKDIR /var/www/html

# copiar todo o conteúdo desta pasta (public_html) para /var/www/html
COPY . /var/www/html/

# criar dirs de uploads e logs e dar permissões seguras
RUN mkdir -p /var/www/html/uploads /var/www/html/logs \
  && chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs \
  && chmod -R 755 /var/www/html/uploads /var/www/html/logs

# habilitar rewrite
RUN a2enmod rewrite

EXPOSE 80

# start apache
CMD ["apache2-foreground"]
