# Minimal PHP image for the pipeline demo. Single stage, small base.
FROM php:8.3-apache-bookworm

# SQLite PDO driver (pdo is bundled; add the sqlite driver).
RUN docker-php-ext-install pdo_sqlite

# App code.
COPY app/ /var/www/html/

# Version baked in at build time (set by CI: --build-arg APP_VERSION=...).
ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}
ENV DB_PATH=/data/todo.sqlite

# Writable data dir for the SQLite file; owned by the web user.
RUN mkdir -p /data && chown www-data:www-data /data

# Route everything through index.php.
RUN { \
  echo '<Directory /var/www/html>'; \
  echo '  RewriteEngine On'; \
  echo '  RewriteCond %{REQUEST_FILENAME} !-f'; \
  echo '  RewriteRule ^ index.php [L]'; \
  echo '</Directory>'; \
  } > /etc/apache2/conf-available/todo.conf && a2enmod rewrite && a2enconf todo

HEALTHCHECK --interval=30s --timeout=4s --start-period=20s --retries=3 \
  CMD php -r "exit(@file_get_contents('http://127.0.0.1/?action=health')?0:1);"

EXPOSE 80
