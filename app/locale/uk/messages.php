<?php

return [
    'error_authentication_required'         => 'Автентифікація обов\'язкова.',
    'error_authentication_failure'          => 'Невірний email або пароль.',
    'error_authentication_exception'        => 'Неможливо автентифікувати. Будь ласка, спробуйте ще раз пізніше.',
    'error_unknown_currency'                => 'Неможливо знайти валюту.',
    'error_loading_default_currency'        => 'Неможливо завантажити валюту за замовчуванням.',
    'error_password_confirmation_not_match' => 'Підтвердження паролю не збігається.',
    'error_wallet_does_not_exists'          => 'Один чи більше гаманець більше не існує.',
    'error_nick_name_claimed'               => 'Нікнейм вже зайнято.',
    'error_value_is_not_unique'             => 'Значення має бути унікальним.',

    'email_confirmation_confirm_failure' => 'Неможливо підтвердити ваш email.',
    'email_confirmation_ok'              => 'Ваш email було підтверджено.',
    'email_confirmation_resend_failure'  => 'Помилка під час надсилання повідомлення з новим посиланням для підтвердження email.',
    'email_confirmation_resend_ok'       => 'Повідомлення з новим посиланням для підтвердження email надіслано.',

    'forgot_password_send_failure'  => 'Неможливо скинути пароль.',
    'forgot_password_sent'          => 'Повідомлення з посиланням для скидання паролю надіслано.',
    'forgot_password_reset_failure' => 'Неможливо скинути пароль.',
    'forgot_password_reset_ok'      => 'Ваш пароль успішно змінено.',

    'user_register_exception' => 'Неможливо зареєструвати нового користувача. Будь ласка, спробуйте ще раз пізніше.',
    'nick_name_register_free' => 'Нікнейм вільний для реєстрації',

    'password_change_exception' => 'Неможливо оновити пароль. Будь ласка, спробуйте ще раз пізніше.',
    'password_change_ok'        => 'Пароль успішно змінено.',

    'profile_photo_update_empty'     => 'Неможливо зберегти завантажене зображення. Будь ласка, спробуйте ще раз пізніше.',
    'profile_photo_update_exception' => 'Неможливо оновити фото профілю. Будь ласка, спробуйте ще раз пізніше.',
    'profile_photo_update_ok'        => 'Фото профілю успішно змінено.',

    'profile_nick_name_free'          => 'Нікнейм вільний до використання',
    'profile_update_locale_exception' => 'Неможливо оновити мову інтерфейсу. Будь ласка, спробуйте ще раз пізніше.',
    'profile_update_basic_exception'  => 'Неможливо оновити дані профілю. Будь ласка, спробуйте ще раз пізніше.',

    'tag_create_exception' => 'Неможливо створити новий тег. Будь ласка, спробуйте ще раз пізніше.',
    'tag_update_exception' => 'Неможливо оновити тег. Будь ласка, спробуйте ще раз пізніше.',
    'tag_delete_exception' => 'Неможливо видалити тег. Будь ласка, спробуйте ще раз пізніше.',

    'charge_create_exception' => 'Неможливо створити витрату. Будь ласка, спробуйте ще раз пізніше.',
    'charge_update_exception' => 'Неможливо оновити витрату. Будь ласка, спробуйте ще раз пізніше.',
    'charge_delete_exception' => 'Неможливо видалити витрату. Будь ласка, спробуйте ще раз пізніше.',

    'wallet_activate_exception'   => 'Неможливо активувати гаманець. Будь ласка, спробуйте ще раз пізніше.',
    'wallet_disable_exception'    => 'Неможливо вимкнути гаманець. Будь ласка, спробуйте ще раз пізніше.',
    'wallet_archive_exception'    => 'Неможливо архівувати гаманець. Будь ласка, спробуйте ще раз пізніше.',
    'wallet_un_archive_exception' => 'Неможливо розархівувати гаманець. Будь ласка, спробуйте ще раз пізніше.',
    'wallet_share_exception'      => 'Неможливо поділитись гаманцем з користувачем. Будь ласка, спробуйте ще раз пізніше.',
    'wallet_revoke_exception'     => 'Неможливо припинити ділитись гаманцем з користувачем. Будь ласка, спробуйте ще раз пізніше.',
    'wallet_revoke_owner'         => 'Неможливо припинити ділитись гаманцем. Ви єдиний учасник. Видаліть гаманець, якщо він більше не потрібен.',
    'wallet_revoke_owner_error'   => 'Ви єдиний власник гаманця.',
    'wallet_create_exception'     => 'Неможливо створити новий гаманець. Будь ласка, спробуйте ще раз пізніше.',
    'wallet_update_exception'     => 'Неможливо оновити гаманець. Будь ласка, спробуйте ще раз пізніше.',
    'wallet_delete_exception'     => 'Неможливо видалити гаманець. Будь ласка, спробуйте ще раз пізніше.',

    'password_verify_error' => 'Невірний пароль.',
    'unique_verify_error'   => 'Значення має бути унікальним.',

    'email_confirmation_account_already_confirmed' => 'Ви вже підтвердили вашу email адресу.',
    'email_confirmation_throttled'                 => 'Останнє повідомлення для підтвердження email адреси було надіслано менше ніж %d секунд тому.',
    'email_confirmation_invalid_token'             => 'Некоректний токен підтвердження.',
    'email_confirmation_expired'                   => 'Термін дії посилання вичерпано.',
    'email_confirmation_invalid_user'              => 'Неможливо визначити власника посилання.',

    'forgot_password_invalid_user' => 'Неможливо знайти користувача.',
    'forgot_password_throttled'    => 'Останній запит скидання паролю створено менше ніж %d секунд тому.',
    'forgot_password_invalid_code' => 'Некоректний код для скидання паролю.',
    'forgot_password_expired'      => 'Термін дії посилання вичерпано.',
    'forgot_password_missing_user' => 'Неможливо визначити власника посилання.',

    'test_mail_subject' => 'Тестове повідомлення',
    'test_mail_hello'   => 'Вітаємо, ',
    'test_mail_line_1'  => 'Це тестове повідомлення.',
    'test_mail_line_2'  => 'Відправка пошти працює справно.',
    'test_mail_footer'  => 'Вдалого дня.',

    'forgot_password_mail_subject' => 'Скидання Вашого Паролю',
    'forgot_password_mail_hello'   => 'Вітаємо',
    'forgot_password_mail_line_1'  => 'Хтось намагається відновити доступ до вашого профілю на',
    'forgot_password_mail_line_2'  => 'Натисніть на посилання нижче, щоб продовжити процедуру відновлення доступу:',
    'forgot_password_mail_footer'  => 'Якщо це не ви, можете проігнорувати дане повідомлення. В будь якому разі, ваш профіль у безпеці.',

    'email_confirmation_mail_subject' => 'Підтвердіть Вашу Email Адресу',
    'email_confirmation_mail_hello'   => 'Вітаємо',
    'email_confirmation_mail_line_1'  => 'Вашу Email адресу використано для реєстрації на',
    'email_confirmation_mail_line_2'  => 'Натисніть на посилання нижче, щоб підтвердити реєстрацію:',
    'email_confirmation_mail_footer'  => 'Якщо це не ви, можете проігнорувати дане повідомлення.',

    'wallet_share_mail_subject'                                => 'Запрошення До Гаманця',
    'wallet_share_mail_hello'                                  => 'Вітаємо',
    'wallet_share_mail_line_invited'                           => 'повілився(лась) з вами спільним гаманцем',
    'wallet_share_mail_line_invited_to'                        => 'на',
    'wallet_share_mail_line_2'                                 => 'Тепер ви можете користуватись ним разом.',
    'wallet_share_mail_footer'                                 => 'Якщо це призначалось не вам, можете проігнорувати дане повідомлення.',

    // validations
    'The condition `{method}` was not met.'                    => 'Умова `{method}` не виконалась.',
    'Should be true.'                                          => 'Має бути увімкнутим.',
    'Should be false.'                                         => 'Має бути вимкнутим.',
    'File does not exists.'                                    => 'Файл не існує.',
    'File not received, please try again.'                     => 'Файл не отримано, будь ласка, спробуйте ще раз.',
    'File exceeds the maximum file size of {1}KB.'             => 'Розмір файлу перевищив {1}KB.',
    'File has an invalid file format.'                         => 'Файл має некоректний формат.',
    'Should be a date in the future.'                          => 'Очукується дата в майбутньому.',
    'Should be a date in the past.'                            => 'Очікується дата в минулому.',
    'Not a valid date.'                                        => 'Дата некоректна.',
    'Value should match the specified date format {1}.'        => 'Значення має відповідати формату дати {1}.',
    'Not a valid timezone.'                                    => 'Некоректний часовий пояс.',
    'Value {1} should come before value {2}.'                  => 'Значення {1} має бути перед значенням {2}.',
    'Value {1} should come after value {2}.'                   => 'Значення {1} має бути після значення {2}.',
    'Value does not match required pattern.'                   => 'Значення не відповідає заданому шаблону.',
    'Enter text shorter or equal to {1}.'                      => 'Введіть текст коротший або рівний довжині {1}.',
    'Text must be longer or equal to {1}.'                     => 'Текст має бути довший або рівний довжині {1}.',
    'Text length must be exactly equal to {1}.'                => 'Довжина тексту має бути рівною {1}.',
    'Text length should be in range of {1}-{2}.'               => 'Довжина тексту має бути між значеннями {1}-{2}.',
    'String value should be empty'                             => 'Стрічка має бути пустою',
    'String value should not be empty'                         => 'Стрічка не має бути пустою',
    'This value is required.'                                  => 'Це значення обов\'язкове.',
    'Not a valid boolean.'                                     => 'Некоректне булеве значення.',
    'Not a valid datetime.'                                    => 'Некоректна дата та час.',
    'Must be a valid email address.'                           => 'Має бути коректна email адреса.',
    'Must be a valid URL address.'                             => 'Має бути коректна URL адреса.',
    'Your value should be in range of {1}-{2}.'                => 'Ваше значення має бути в діапазоні значень {1}-{2}.',
    'Your value should be equal to or higher than {1}.'        => 'Ваше значення має бути рівним або більшим за {1}.',
    'Your value should be equal to or lower than {1}.'         => 'Ваше значення має бути рівним або меншим за {1}.',
    'Please enter valid card number.'                          => 'Будь ласка, введіть коректний номер карти.',
    'Fields {1} and {2} do not match.'                         => 'Поля {1} та {2} не збігаються.',
    'Image format not supported.'                              => 'Формат зображення не підтримується.',
    'Image format not supported (allowed JPEG, PNG or GIF).'   => 'Формат зображення не підтримується (підтримується JPEG, PNG або GIF).',
    'Image size should not exceed {1}x{2}px.'                  => 'Розмір зображення не повинен перевищувати {1}x{2}px.',
    'The image dimensions should be at least {1}x{2}px.'       => 'Вимір зображення має бути принаймні {1}x{2}px.',
    'Number of elements must be exactly {1}.'                  => 'Кількість елементів має бути {1}.',
    'Number of elements must be equal to or greater than {1}.' => 'Кількість елементів має бути рівним або більше ніж {1}.',
    'Number of elements must be equal to or less than {1}.'    => 'Кількість елементів має бути рівним або менше ніж {1}.',
    'Number of elements must be between {1} and {2}.'          => 'Кількість елементів має бути в діапазоні між {1} та {2}.',
    'Array is not list'                                        => 'Масив не є списком',
    'Array is not associative'                                 => 'Масив не є асоціативним',
    'Unexpected array value'                                   => 'Неочікуване значення масиву',
    'The condition `{name}` was not met.'                      => 'Умова `{name}` не виконалась.',
];
