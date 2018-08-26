FROM php:7.2.8-fpm-alpine

ENV COMPOSER_ALLOW_SUPERUSER=1
ARG GIT_COMMIT_HASH

RUN set -xe \
	&& echo $GIT_COMMIT_HASH > .gitcommit \
	&& apk --update --no-cache add curl \
	&& docker-php-ext-install sockets \
	&& curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer

WORKDIR /usr/deploy

COPY ["./composer.json", "./composer.lock", "./"]

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
		&& mkdir temp \
		&& mkdir log \
		&& composer install --prefer-dist --no-progress --no-interaction --no-dev --no-autoloader \
		&& composer clear-cache \
		&& chmod 777 -R /tmp/opcache

COPY ./data/versions.json ./data/versions.json

COPY ./phpstan ./phpstan/

COPY ./app/config/config.env.neon ./app/config/config.local.neon

COPY ./www ./www/

COPY ./app ./app/

RUN composer dump-autoload --optimize --no-dev --no-interaction \
	&& composer global require jderusse/composer-warmup --prefer-dist --no-progress --no-interaction \
	&& composer warmup-opcode app \
	&& chown -R www-data:www-data /usr/deploy/data \
	&& chown -R www-data:www-data /usr/deploy/temp \
	&& chown -R www-data:www-data /usr/deploy/log

VOLUME /usr/deploy

EXPOSE 9000
