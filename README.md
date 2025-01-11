# Document flow in AMO CRM

Веб-сервер Laravel для обработки данных созданных документов внутри AMO CRM.


## Установка проекта

Перед установкой проекта убедитесь, что у вас установлен `nodejs` и `php` версией не ниже `7.4`.

- Загрузите проект с GitHub
```bash
git clone https://github.com/electromystyle/document-flow-amo-crm.git
```

- Установите зависимости из `npm` и `composer`
```bash
npm install && npm run dev
```
```bash
composer install
```

- Создайте и настройте файл `.env`

- Запустите веб-сервер
```bash
php artisan serve
```


## Первичная задача заказчика

Реализовать запись данных (номер документа, дата документа, название компании и др.) в таблицу Google Sheet из созданных документов в AMO CRM через плагин стороннего разработчика.
[Пример таблицы в Google Sheet](https://docs.google.com/spreadsheets/d/1GGbqIx72pwRhjaxtm_S2IGwUbh8oxVWXPHAv_AMbIL4/edit?usp=sharing)


## Заказчик

Компания Gosproverka, г. Москва


## Лицензия

MIT License
