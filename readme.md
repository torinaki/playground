# PHPStan Playground

## How to Install

1. Create empty file `app/config/config.local.neon`
2. Make directories `data`, `log`, `phpstan` and `temp` writable.
3. Run `bin/refresh-versions.php`. The initial run should take couple of minutes.

## How to Run

1. Run `php -S localhost:8111 -t www`
2. Open : [http://localhost:8111/](http://localhost:8111/)

![screenshot](https://user-images.githubusercontent.com/175109/28476683-2bb8a37a-6e51-11e7-9e24-459467fdfc18.png)

## Troubleshooting

Working on latte template ? You probably need to remove the cache directory : `rm -rf temp/cache/*`
