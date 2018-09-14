FROM php:7.2.8-fpm-alpine as development

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN set -xe \
	&& apk --update --no-cache add curl zlib-dev autoconf gcc g++ make \
	&& docker-php-ext-install sockets zip \
	&& curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer


WORKDIR /usr/deploy

RUN mkdir data \
	&& chown -R www-data:www-data data \
	&& mkdir temp \
	&& mkdir temp/phpstan-cache \
	&& chown -R www-data:www-data temp \
	&& mkdir log \
	&& chown -R www-data:www-data log \
	&& mkdir phpstan \
	&& chown -R www-data:www-data phpstan \
	&& mkdir vendor \
	&& chown -R www-data:www-data vendor

COPY ./ ./

EXPOSE 9000

USER www-data

CMD []

###########

FROM development as development-xdebug

USER root

RUN pecl install xdebug \
	&& docker-php-ext-enable xdebug

ENV XDEBUGINI_PATH=/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY [ "./docker/fpm/xdebug.ini", "/tmp/xdebug.ini" ]
RUN cat /tmp/xdebug.ini >> $XDEBUGINI_PATH
RUN echo "xdebug.remote_host="`/sbin/ip route|awk '/default/ { print $3 }'` >> $XDEBUGINI_PATH

USER www-data

###########

FROM development as production

USER root

COPY ./docker/fpm/opcache.ini /usr/local/etc/php/conf.d/10-opcache.ini

RUN mkdir /tmp/opcache \
	&& chmod 777 -R /tmp/opcache

RUN echo http://dl-2.alpinelinux.org/alpine/edge/community/ >> /etc/apk/repositories \
	&& apk --no-cache add shadow && usermod -a -G xfs www-data \
	&& composer install --prefer-dist --optimize-autoloader --classmap-authoritative --no-progress --no-interaction --no-dev \
	&& composer global require jderusse/composer-warmup --prefer-dist --no-progress --no-interaction \
	&& composer warmup-opcode app \
	&& composer clear-cache

ARG GIT_COMMIT_HASH

RUN echo $GIT_COMMIT_HASH > .gitcommit

USER www-data

VOLUME /usr/deploy
