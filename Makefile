# Makefile for local development shortcuts

.PHONY: up seed test

up:
	composer install
	@if [ ! -f .env ]; then cp .env.example .env; fi
	php artisan key:generate --force
	php artisan migrate --force
	npm install
	npm run dev

seed:
	php artisan migrate:fresh --seed

test:
	php artisan test --without-tty
