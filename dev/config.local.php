<?php
// Lokalne nastavitve za razvoj — uporabijo se samo, če datoteka obstaja (na InfinityFree je ni).
// Baza: SQLite datoteka. Maili: lažni SMTP strežnik (dev/fake-smtp.js), ki maile shrani v dev/mails/.

return [
    'admin_password' => 'admin',
    'db' => [
        'dsn'  => 'sqlite:' . __DIR__ . '/zreb.sqlite',
        'user' => null,
        'pass' => null,
    ],
    'mail' => [
        'host'      => 'tcp://127.0.0.1',
        'port'      => 2525,
        'user'      => '',
        'pass'      => '',
        'from'      => 'zreb@localhost',
        'from_name' => 'Novoletni žreb',
    ],
    'site_url' => 'http://localhost:8000',
];
