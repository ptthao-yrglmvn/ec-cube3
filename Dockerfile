ARG TAG=5.4-apache
FROM php:${TAG}

ENV APACHE_DOCUMENT_ROOT /var/www/html

RUN cp /etc/apt/sources.list /etc/apt/sources.list.backup

COPY dockerbuild/sources.list /etc/apt/sources.list
COPY dockerbuild/99ignore-archive-check /etc/apt/apt.conf.d/99ignore-archive-check

RUN apt-get update \
  && apt-get install -y --allow-unauthenticated \
    libpng-dev \
    libjpeg-dev \
    libmcrypt-dev \
    libxml2-dev \
    libxslt1-dev \
    libpq-dev \
    git \
    postgresql postgresql-contrib \
  && rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-configure gd --with-png-dir=/usr --with-jpeg-dir=/usr \
	&& docker-php-ext-install gd

RUN docker-php-ext-install gettext
RUN docker-php-ext-install mcrypt
RUN docker-php-ext-install mysqli
RUN docker-php-ext-install mysql
RUN docker-php-ext-install pdo
RUN docker-php-ext-install pdo_mysql
RUN docker-php-ext-install pdo_pgsql
RUN docker-php-ext-install soap
RUN docker-php-ext-install sockets
RUN docker-php-ext-install xmlrpc
RUN docker-php-ext-install zip
RUN docker-php-ext-install mbstring

RUN echo "date.timezone = Asia/Tokyo" > /usr/local/etc/php/php.ini
RUN echo "log_errors = on" >> /usr/local/etc/php/php.ini
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/php.ini
RUN echo "upload_max_filesize = 100M" >> /usr/local/etc/php/php.ini
RUN echo "post_max_size = 100M" >> /usr/local/etc/php/php.ini
RUN echo "max_execution_time = 180" >> /usr/local/etc/php/php.ini

# Make Ubuntu great again! Enable SSL and mod_rewrite by default! Attach key and self sign SSL certifcate!
# It's going to be great, believe me!
COPY dockerbuild/apache.key /etc/ssl/private/apache.key
COPY dockerbuild/apache.crt /etc/ssl/certs/apache.crt
COPY dockerbuild/default-ssl.conf /etc/apache2/sites-available/default-ssl.conf

RUN mkdir -p ${APACHE_DOCUMENT_ROOT} \
  && sed -ri -e "s!/var/www!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
  && sed -ri -e "s!/var/www!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
  ;

RUN curl --insecure -o composer.phar https://getcomposer.org/download/2.2.23/composer.phar \
  && chmod +x composer.phar \
  && mv composer.phar /usr/local/bin/composer \
  && composer --version

RUN a2enmod ssl
RUN a2ensite default-ssl
RUN a2enmod rewrite headers

# see https://stackoverflow.com/questions/73294020/docker-couldnt-create-the-mpm-accept-mutex/73303983#73303983
RUN echo "Mutex posixsem" >> /etc/apache2/apache2.conf
EXPOSE 443

# Override with custom configuration settings
COPY dockerbuild/php.ini $PHP_INI_DIR/conf.d/
COPY dockerbuild/docker-php-entrypoint /usr/local/bin/
COPY dockerbuild/wait-for-postgres.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/wait-for-postgres.sh

COPY . ${APACHE_DOCUMENT_ROOT}
WORKDIR ${APACHE_DOCUMENT_ROOT}
