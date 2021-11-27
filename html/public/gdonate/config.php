<?php

class Config
{
    // Ваш секретный ключ (из настроек проекта в личном кабинете cp.gdonate.ru )
    const SECRET_KEY = '67e2f34ecdcaba8def8b919c706631ba';
    // Стоимость товара в руб.
    const ITEM_PRICE = 1;

    // Таблица начисления товара, например `users`
    const TABLE_ACCOUNT = 'users';
    // Название поля из таблицы начисления товара по которому производится поиск аккаунта/счета, например `email`
    const TABLE_ACCOUNT_NAME = 'steamid64';
    // Название поля из таблицы начисления товара которое будет увеличено на колличево оплаченого товара, например `sum`, `donate`
    const TABLE_ACCOUNT_DONATE= 'money';

    // Параметры соединения с бд
    // Хост
    const DB_HOST = '80.78.253.250';
    // Имя пользователя
    const DB_USER = 'root';
    // Пароль
    const DB_PASS = '161974ad';
    // Назывние базы
    const DB_NAME = 'csgow';
}