# 🎉 Novoletni modni žreb

Spletna stran za žurko: vsak se prijavi, žreb mu dodeli osebo, ki jo mora obleči, rezultat pa dobi na mail.

Vse, kar se naloži na strežnik, je v mapi **`htdocs/`** (PHP + MySQL, brez dodatnih knjižnic).

## Objava na InfinityFree — korak za korakom

### 1. Račun in stran
1. Registriraj se na [infinityfree.com](https://www.infinityfree.com).
2. **Create Account** → izberi brezplačno poddomeno (npr. `silvestrovo-zreb.great-site.net`).
3. Počakaj nekaj minut, da se stran aktivira.

### 2. Baza podatkov
1. V **Control Panel** → **MySQL Databases** ustvari bazo (npr. `zreb`).
2. Zapiši si: **MySQL Hostname** (npr. `sql123.infinityfree.com`), **ime baze** (npr. `if0_12345678_zreb`), **uporabniško ime** (`if0_12345678`) in **geslo** (to je geslo tvojega hosting računa, vidiš ga v Account → Show password).
3. Tabel ni treba ustvarjati ročno — naredijo se same ob prvem obisku.

### 3. Gmail geslo za aplikacijo
1. V Google računu vklopi **preverjanje v dveh korakih** (če ga še nimaš).
2. Pojdi na [myaccount.google.com/apppasswords](https://myaccount.google.com/apppasswords), ustvari geslo (ime npr. „Žreb").
3. Dobiš 16 znakov (`abcd efgh ijkl mnop`) — to je geslo za `config.php`, **ne** tvoje navadno geslo.

### 4. Izpolni `htdocs/config.php`
Če datoteke ni (npr. po kloniranju iz gita), skopiraj `config.example.php` v `config.php`.
`config.php` je v `.gitignore`, da gesla ne pridejo v git.

- `admin_password` — geslo za admin stran (ne `admin`!)
- `db` — podatki iz koraka 2: `dsn` zamenjaj z `mysql:host=HOSTNAME;dbname=IME_BAZE;charset=utf8mb4`
- `mail` — tvoj Gmail in geslo za aplikacijo iz koraka 3
- `site_url` — naslov tvoje strani

### 5. Naloži datoteke
1. Control Panel → **Online File Manager** → odpri mapo `htdocs`.
2. Izbriši privzete datoteke (`index2.html` ipd.).
3. Naloži **vse** datoteke iz lokalne mape `htdocs/` (tudi `.htaccess`).

### 6. Preizkusi
1. Odpri `https://tvoja-stran.great-site.net/admin.html` in se prijavi.
2. Klikni **🧪 Pošlji testni mail sebi** — mail mora priti (preveri tudi vsiljeno pošto).
3. Na prvi strani se prijavi s svojim mailom → dobiti moraš potrditev.
4. Ko vse deluje, pošlji povezavo prijateljem. 🎉

## Kako deluje

1. Udeleženec vpiše ime, mail, velikost (S–XXL), spol in neobvezno opombo → dobi potrditveni mail s **skrivno kodo**.
2. Admin vidi število prijav, razrez po velikosti/spolu, seznam in status mailov.
3. Ko admin klikne **Izvedi žreb**, se prijave zaprejo, vsak dobi drugo osebo (nihče sebe) in vsem se pošlje mail.
   Maili gredo v paketih po 5, admin stran sproti kaže napredek. Neuspele lahko pošlješ ponovno.
4. Rezultat je s kodo vedno viden tudi na prvi strani — za primer, da mail ne pride.
5. Po žurki v adminu klikni **Izbriši vse podatke**.

## 👃 Mini igra: Spopad z nosom

Samostojna igra v [`minigame/index.html`](minigame/index.html) — odpri v brskalniku.
Izberi Zrezka ali Korenčka in premagaj velikanski nos Nosferatu.
Za vključitev v stran jo skopiraj v `htdocs/` (npr. `igra.html`) in dodaj povezavo.

## Opombe

- Gmail dovoli ~500 mailov na dan — za žurko več kot dovolj.
- `config.php` vsebuje gesla — ne deli ga in ga ne nalagaj na GitHub.
- Če mail ne pride: preveri vsiljeno pošto, nato v adminu poglej napako pri udeležencu.
