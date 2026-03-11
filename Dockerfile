FROM php:8.2-apache

# Copiar código do projeto para o diretório do Apache
COPY . /var/www/html/

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html

# Expor porta
EXPOSE 80

# Apache já inicia automaticamente com a imagem base
