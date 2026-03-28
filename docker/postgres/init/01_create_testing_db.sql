-- Создаём отдельную БД для тестов
-- Этот скрипт выполняется автоматически при первом старте контейнера

CREATE DATABASE laravel_testing
    WITH
    OWNER = laravel
    ENCODING = 'UTF8'
    LC_COLLATE = 'en_US.utf8'
    LC_LOCALE = 'en_US.utf8'
    TEMPLATE = template0;

GRANT ALL PRIVILEGES ON DATABASE laravel_testing TO laravel;