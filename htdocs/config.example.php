<?php
// ⚙️ NASTAVITVE — izpolni pred nalaganjem na InfinityFree (glej README.md).

return [
    // Geslo za /admin.html
    'admin_password' => 'admin',

    // MySQL baza: podatke najdeš v InfinityFree → Control Panel → MySQL Databases
    'db' => [
        'dsn'  => 'mysql:host=sqlXXX.infinityfree.com;dbname=if0_XXXXXXXX_zreb;charset=utf8mb4',
        'user' => 'if0_XXXXXXXX',
        'pass' => 'tvoje-geslo-za-bazo',
    ],

    // Gmail za pošiljanje mailov.
    // 'pass' NI tvoje navadno geslo, ampak "App password" (Google račun → Varnost → Gesla za aplikacije).
    'mail' => [
        'host'      => 'ssl://smtp.gmail.com',
        'port'      => 465,
        'user'      => 'tvoj.mail@gmail.com',
        'pass'      => 'xxxx xxxx xxxx xxxx',
        'from_name' => 'Novoletni žreb',
    ],

    // Naslov tvoje strani (za povezavo v mailih)
    'site_url' => 'https://tvoja-stran.great-site.net',
];
