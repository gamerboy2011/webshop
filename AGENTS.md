# AGENTS.md

Ez a fájl iránymutatást ad a WARP (warp.dev) számára a repositoryban való munkához.

## Projekt áttekintés

YoursyWear - Magyar e-kereskedelmi webshop egyéni PHP MVC architektúrával, XAMPP-on fut (Apache + MySQL). A kódbázis magyar nyelvet használ a megjegyzésekhez és a felhasználói felület szövegeihez.

## Fejlesztői környezet

### Előfeltételek
- XAMPP PHP 8.0+ és MySQL
- Adatbázis: `webshop` localhost-on (root, jelszó nélkül)

### Alkalmazás indítása
Indítsd el a XAMPP Apache és MySQL szolgáltatásokat, majd nyisd meg: `http://localhost/webshop/`

### Adatbázis
- Konfiguráció: `app/config/database.php`
- PDO használata előkészített utasításokkal
- Táblák: `product`, `users`, `orders`, `order_item`, `stock`, `city`, `vendor`, `gender`, `color`, `size`, `product_type`, `product_subtype`, `product_img`

## Architektúra

### Kérés folyamata
1. `.htaccess` átírja az összes kérést az `index.php`-ra
2. `index.php` inicializálja a session-t, betölti az autoloadert, include-olja a `router.php`-t
3. `router.php` elemzi az URL-t és beállítja a `$_GET['page']`-et
4. POST kérések az action alapú switch-el kezelve az `index.php`-ban
5. GET kérések betöltik a nézeteket az `app/views/pages/` mappából

### URL Útvonalak (`router.php`)
- `/noi/{kategoria}` és `/ferfi/{kategoria}` → Kategória oldal
- `/termek/{id}` → Termék oldal
- `/kosar` → Kosár oldal
- `/akcio` → Akciós termékek
- `/ujdonsagok` → Új termékek
- `?q={kereses}` → Keresési eredmények

### MVC Struktúra
```
app/
├── controllers/    # Kérések kezelése, modellek hívása, nézetek betöltése
├── models/         # Adatbázis lekérdezések PDO-val (konstruktor injectálás)
├── views/
│   ├── layouts/    # head.php, menu.php, footer.php
│   ├── pages/      # Fő oldal sablonok
│   └── components/ # Újrafelhasználható komponensek (404.php, 500.php)
├── config/         # database.php
├── library/        # customfunctions.php (CSRF, session, redirect)
└── api/            # JSON végpontok (postcode.php)
```

### Fontos minták

**Controller példányosítás:**
- Néhány controller globális `$pdo`-t használ
- Néhány `PDO $pdo`-t kap konstruktoron keresztül (AuthController, ActivationController)

**Model minta:**
- Mindig `PDO` injectálás konstruktoron keresztül
- Visszatérési típus: `array`, `array|false`, vagy `?array`

**CSRF védelem:**
- Minden POST űrlapnak tartalmaznia kell: `<?= csrf_field() ?>`
- Token ellenőrzés az `index.php`-ban minden POST action előtt

**Session kosár:**
- Kosár tárolva: `$_SESSION['cart']`
- Elemek: `['product_id' => int, 'size_id' => int, 'quantity' => int]`

**POST Actionok:**
POST kérések rejtett `action` mezőt használnak: `login`, `register`, `cart_add`, `cart_update`, `cart_remove`, `checkout`, `logout`

### Frontend
- Tailwind CSS CDN-en keresztül (`@tailwindcss/browser@4`)
- Font Awesome ikonok
- Nincs build lépés szükséges

## Fontos konvenciók

- Fájl elnevezés: controllerek/modellek kisbetűsek (pl. `productcontroller.php`, `productmodel.php`)
- Osztály elnevezés: PascalCase (pl. `ProductController`, `ProductModel`)
- Az autoloader ellenőrzi az eredeti és kisbetűs fájlneveket is
- Magyar felhasználói felület: hibaüzenetek, címkék és megjegyzések magyarul
- Jelszó szabályok: 6-13 karakter, tartalmaznia kell kisbetűt, nagybetűt és számot
