# AGENTS.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Project Overview

YoursyWear - Hungarian e-commerce webshop built with vanilla PHP MVC, MySQL (PDO), and Tailwind CSS (CDN). Runs on XAMPP with Apache + MySQL.

**Language**: All UI, comments, and error messages are in Hungarian.

## Running the Application

Start XAMPP (Apache + MySQL), then access via `http://localhost/webshop/`

The database name is `webshop` with MySQL credentials: root / (no password)

## Architecture

**Entry Point**: `index.php` - bootstraps app, handles POST routing, renders page layout

**Routing**: `router.php` - maps clean URLs to `$_GET['page']` values
- `/termek/123` → product.php with `$_GET['id']`
- `/kategoria/noi` → category.php with `$_GET['category']`
- `/kosar` → cart.php
- Direct file mapping: `/login` → app/views/pages/login.php

**MVC Structure** (`app/`):
- `controllers/` - Business logic, receive `$pdo` via constructor
- `models/` - Data access using PDO prepared statements
- `views/pages/` - Page templates
- `views/layouts/` - Shared layout components (head.php, menu.php, footer.php)
- `views/components/` - Reusable UI components
- `config/database.php` - PDO connection
- `library/customfunctions.php` - Helper functions (CSRF, redirect, session)
- `api/` - JSON endpoints (e.g., postcode.php)

## Key Patterns

**POST Actions**: Dispatched via `action` field in index.php switch statement:
- `login`, `register` → AuthController
- `cart_add`, `cart_update`, `cart_remove` → CartController
- `checkout` → OrderController

**CSRF Protection**: All forms must include `<?= csrf_field(); ?>` and submit via POST

**Session Data**:
- Cart: `$_SESSION['cart']` (array of product_id, size_id, quantity)
- Auth: `$_SESSION['user_id']`, `$_SESSION['logged_in']`, `$_SESSION['username']`

**Model Pattern**: Models receive PDO in constructor, use prepared statements:
```php path=null start=null
class SomeModel {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
}
```

**Controller instantiation**: Controllers needing DB access receive `$pdo`:
```php path=null start=null
(new AuthController($pdo))->handle();
```

## Database Schema (Key Tables)

- `users` - user_id, username, email, password_hash, role_id, is_active, activation_token
- `product` - product_id, name, description, price, vendor_id, subtype_id, gender_id, color_id, is_active
- `product_img` - product_id, src, position
- `product_type` / `product_subtype` - Category hierarchy
- `size` / `stock` - Size definitions and inventory (stock.quantity)
- `favorites` - user_id, product_id
- `city` - postcode, city_id, city_name (for address lookup)

## Styling

- Tailwind CSS v4 via CDN (`<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4">`)
- Font Awesome for icons
- Custom CSS in `assets/css/hero.css`
