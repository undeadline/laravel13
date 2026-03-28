# Скопировать .env для Laravel
```
cp src/.env.example src/.env
```

# Собрать образы и поднять стек
```
docker compose up -d --build
```

# Сгенерировать ключ приложения
```
docker compose exec app php artisan key:generate
```

# Запустить миграции
```
docker compose exec app php artisan migrate
```

# Запустить тесты (против testing БД)
```
docker compose exec app php artisan test
```

# Запуск с prod-окружением:
```
docker compose \
  -f docker-compose.yml \
  -f docker-compose.prod.yml \
  --env-file .env.prod \
  up -d --build
```