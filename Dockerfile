FROM debian:stable-slim

LABEL maintainer Maxime Car <https://github.com/Maxime-Car>

RUN apt-get update && apt-get install -y \
    curl \
    net-tools \
    apt-transport-https \
    gnupg \
    nano \
    nginx \
    openssl \
    php7.3-fpm \
    php7.3-memcached \
    php7.3-ldap \
    php7.3-gd \
    php7.3-curl

RUN rm -rf /var/lib/apt/lists/* \
      && apt-get clean -y \
      && apt-get autoremove -y

COPY initFiles/init.sh /

COPY initFiles/default /etc/nginx/sites-available/default

COPY html /var/www/html

RUN mkdir /var/www/html/uploads; chmod 755 /var/www/html/uploads; chown www-data:www-data /var/www/html/uploads

CMD ["bash", "/init.sh"]
