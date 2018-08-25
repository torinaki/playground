FROM php:7.2.8-fpm-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN set -xe \
	&& apk --update --no-cache add curl \
	&& docker-php-ext-install sockets \
	&& curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

WORKDIR /usr/deploy

COPY ["./composer.json", "./composer.lock", "./"]

RUN echo -e \
	"zend_extension=opcache.so\n" \
	"opcache.enable=0\n" \
	"opcache.enable_cli=1\n" \
	"opcache.memory_consumption=128\n" \
	"opcache.validate_timestamps=0\n" \
	"opcache.interned_strings_buffer=16\n" \
	"opcache.max_accelerated_files=10000\n" \
	"opcache.file_cache='/tmp/opcache'\n" \
	"opcache.file_cache_only=0\n" \
	"opcache.file_update_protection=0\n" > /usr/local/etc/php/conf.d/10-opcache.ini \
	&& mkdir /tmp/opcache \
		&& composer install --prefer-dist --no-progress --optimize-autoloader --no-interaction --no-dev \
		&& composer global require jderusse/composer-warmup --prefer-dist --no-progress --optimize-autoloader --no-interaction \
		&& composer clear-cache \
		&& chmod 777 -R /tmp/opcache

RUN mkdir temp
RUN mkdir log

COPY ./app ./app/

COPY ./data/versions.json ./data/versions.json

COPY ./phpstan ./phpstan/

COPY ./app/config/config.env.neon ./app/config/config.local.neon

COPY ./www ./www/

ARG GIT_COMMIT_HASH

RUN echo $GIT_COMMIT_HASH > .gitcommit

RUN composer warmup-opcode

RUN chown -R www-data:www-data /usr/deploy/data
RUN chown -R www-data:www-data /usr/deploy/temp
RUN chown -R www-data:www-data /usr/deploy/log

VOLUME /usr/deploy

EXPOSE 9000
