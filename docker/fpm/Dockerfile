FROM php:7.2.8-fpm-alpine as development

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN set -xe \
	&& apk --update --no-cache add curl zlib-dev \
	&& docker-php-ext-install sockets zip \
	&& curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

WORKDIR /usr/deploy

RUN chown -R www-data:www-data /usr/deploy

USER www-data:www-data

RUN mkdir data \
	&& mkdir temp \
	&& mkdir temp/phpstan-cache \
	&& mkdir log \
	&& mkdir phpstan

COPY ["./composer.json", "./composer.lock", "./"]

RUN composer install --prefer-dist --no-progress --no-interaction --dev

COPY ./ ./

EXPOSE 9000

CMD []

FROM development as production

USER root:root

RUN echo -e \
	"zend_extension=opcache.so\n" \
	"opcache.enable=1\n" \
	"opcache.enable_cli=1\n" \
	"opcache.memory_consumption=128\n" \
	"opcache.validate_timestamps=0\n" \
	"opcache.interned_strings_buffer=16\n" \
	"opcache.max_accelerated_files=10000\n" \
	"opcache.file_cache='/tmp/opcache'\n" \
	"opcache.file_cache_only=0\n" \
	"opcache.file_update_protection=0\n" > /usr/local/etc/php/conf.d/10-opcache.ini \
	&& mkdir /tmp/opcache \
	&& chmod 777 -R /tmp/opcache

RUN echo http://dl-2.alpinelinux.org/alpine/edge/community/ >> /etc/apk/repositories
RUN apk --no-cache add shadow && usermod -a -G xfs www-data

USER www-data:www-data

RUN composer install --prefer-dist --optimize-autoloader --classmap-authoritative --no-progress --no-interaction --no-dev \
	&& composer global require jderusse/composer-warmup --prefer-dist --no-progress --no-interaction \
	&& composer warmup-opcode app \
	&& composer clear-cache

ARG GIT_COMMIT_HASH

RUN echo $GIT_COMMIT_HASH > .gitcommit

VOLUME /usr/deploy
