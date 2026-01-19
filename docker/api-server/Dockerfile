FROM php:8.2-apache

# Metadados
LABEL maintainer="28Fácil <contato@28facil.com.br>"
LABEL description="API Server para gerenciamento de API Keys"

# Instalar dependências
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Apache
RUN a2enmod rewrite headers
COPY ./apache-config.conf /etc/apache2/sites-available/000-default.conf

# Definir diretório de trabalho
WORKDIR /var/www/html

# Copiar aplicação
COPY ./app /var/www/html

# Instalar dependências do Composer (se usar Laravel)
# RUN composer install --no-dev --optimize-autoloader

# Permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Expor porta
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]
