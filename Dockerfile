# Minimal PHP image for the pipeline demo.
FROM php:8.3-apache-bookworm

# Required for pdo_sqlite compilation
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    sqlite3 \
 && rm -rf /var/lib/apt/lists/*

# SQLite PDO driver
RUN docker-php-ext-install pdo_sqlite

# App code.
COPY app/ /var/www/html/

# Version baked in at build time.
ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}
ENV DB_PATH=/data/todo.sqlite

# Writable data dir for SQLite.
RUN mkdir -p /data && chown www-data:www-data /data

# Apache rewrite rules.
RUN { \
  echo '<Directory /var/www/html>'; \
  echo '  RewriteEngine On'; \
  echo '  RewriteCond %{REQUEST_FILENAME} !-f'; \
  echo '  RewriteRule ^ index.php [L]'; \
  echo '</Directory>'; \
  } > /etc/apache2/conf-available/todo.conf \
  && a2enmod rewrite \
  && a2enconf todo

HEALTHCHECK --interval=30s --timeout=4s --start-period=20s --retries=3 \
  CMD php -r "exit(@file_get_contents('http://127.0.0.1/?action=health')?0:1);"

EXPOSE 80
