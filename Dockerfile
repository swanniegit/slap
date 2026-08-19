# SLAP Baby Designs — production image.
#
# Coolify routes to port 80 for PHP images. Anything listening on 3000 is
# unreachable: the proxy health check never passes and the deploy rolls back.

FROM php:8.2-apache

# .htaccess is where every security header, cache rule, redirect and compression
# directive lives. Apache does NOT error on directives whose module is missing —
# it silently ignores <IfModule> blocks and errors only on bare Header/Expires.
# Without these four the site still serves, just unprotected and uncompressed,
# which is the worst kind of failure because nothing looks broken.
# filter backs mod_deflate's output filtering.
RUN apt-get update && apt-get install -y --no-install-recommends curl unzip git && \
    a2enmod rewrite headers expires deflate filter && \
    curl -sS https://getcomposer.org/installer \
      | php -- --install-dir=/usr/local/bin --filename=composer && \
    rm -rf /var/lib/apt/lists/*

# System-level PHP config and server hardening. These are applied via conf.d and
# a2enconf so they cannot be undone by an .htaccess edit.
COPY docker/php-prod.ini         /usr/local/etc/php/conf.d/zz-prod.ini
COPY docker/apache-security.conf /etc/apache2/conf-available/zz-security.conf
RUN a2enconf zz-security

COPY . /var/www/html/

# PHPMailer is the only dependency. lib/enquiry.php requires it lazily, so a
# missing vendor/ degrades to "enquiry saved to disk, not emailed" rather than a
# fatal — but the image must still install it or every enquiry goes unnoticed.
RUN cd /var/www/html && \
    composer install --no-dev --no-interaction --optimize-autoloader

# docker/ has to remain in the build context for the two COPYs above, so
# `COPY . /var/www/html/` unavoidably drags it into the public webroot. Delete
# it here rather than trust the .htaccess deny to be the only thing standing
# between the internet and the server config.
RUN rm -rf /var/www/html/docker

# SLAP_ENQUIRY_LOG is /data/enquiries.jsonl — an absolute path OUTSIDE the
# webroot, so the enquiry record can never be fetched over HTTP. It is the
# system of record (email is only the notification), so it must be a volume:
# without this a redeploy discards every enquiry received since the last one.
RUN mkdir -p /data && chown -R www-data:www-data /data && chmod 755 /data
VOLUME ["/data"]

EXPOSE 80

# Hits the real front page through Apache, so it fails if PHP is broken and not
# merely if the port is open. --fail turns a 5xx into a non-zero exit.
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl --fail --silent --output /dev/null http://localhost/ || exit 1
