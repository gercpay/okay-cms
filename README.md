# Модуль GercPay для OkayCMS

Creator: [Mustpay](https://mustpay.tech)<br>
Tags: OkayCMS, GercPay, payment, payment gateway, credit card, Visa, MasterCard, Apple Pay, Google Pay<br>
Requires at least: OkayCMS 4.0<br>
License: GNU GPL v3.0<br>
License URI: [License](https://opensource.org/licenses/GPL-3.0)

Цей модуль для **OkayCMS** дозволить вам приймати платежі за допомогою платіжної системи **GercPay**.

## Встановлення

1. Завантажте останню версію платіжного модуля.

2. Розпакуйте архів на сервері в каталог із встановленою **OkayCMS**. Приклад шляху для файлів модуля:
   `{OkayCMS_root}/Okay/Modules/OkayCMS/Gercpay`.

4. За потреби встановіть права доступу до файлів модуля.

5. Зайдіть як адміністратор у **OkayCMS** та в розділі *«Модулі → Мої модулі»* активуйте модуль **GercPay**.

6. У розділі *«Налаштування сайту → Методи оплати»* натисніть кнопку **Додати спосіб оплати**.

7. З випадаючого меню виберіть **OkayCMS/Gercpay**, валюта - **Гривні**.

8. Заповніть поля модуля даними отриманими від платіжної системи:
   - *Ідентифікатор торговця (Merchant ID)*;
   - *Секретний ключ (Secret Key)*.

9. Встановіть логотип платіжної системи. Для цього можна використовувати файл:
   `{OkayCMS_root}/Okay/Modules/OkayCMS/Gercpay/Backend/design/images/gercpay.png`.

10. Позначте способи доставки, які можна буде сплатити за допомогою платіжного методу.

11. Переконайтеся, що перемикач **Активність** увімкнено та збережіть налаштування.

Модуль готовий до роботи.

*Модуль протестований для роботи з OkayCMS 4.0.5 та PHP 7.2.*
