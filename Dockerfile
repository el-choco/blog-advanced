# Base image
FROM php:8.2-apache

# System packages
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    zip \
    unzip \
    git \
    curl \
    msmtp \
    msmtp-mta \
    ca-certificates \
 && rm -rf /var/lib/apt/lists/*

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
    pdo \
    pdo_mysql \
    mysqli \
    gd \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath

# Apache modules
RUN a2enmod rewrite headers

# MSMTP configuration (template committed as msmtprc.example)
COPY msmtprc.example /etc/msmtprc
RUN chmod 600 /etc/msmtprc

# PHP sendmail path -> msmtp
RUN echo "sendmail_path = /usr/bin/msmtp -t" > /usr/local/etc/php/conf.d/sendmail.ini

# Workdir and permissions
WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

EXPOSE 80