# PHPStan Playground

[![Build Status](https://travis-ci.org/phpstan/playground.svg?branch=master)](https://travis-ci.org/phpstan/playground)

## How to run the project locally

1. Install Docker.
2. Copy `.env.template` to `.env`.
3. Run `docker-compose up`.
4. Connect to the FPM container using `docker exec -ti XXX sh` (where XXX is the container ID obtained by `docker ps`).
5. Run `php bin/cli.php versions:refresh`. Repeat if necessary, it should complete in a couple of minutes.
6. Access http://localhost:8082 in your browser.

![screenshot](https://user-images.githubusercontent.com/175109/28476683-2bb8a37a-6e51-11e7-9e24-459467fdfc18.png)
