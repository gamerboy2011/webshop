<?php
$orderId = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$submitted = isset($_GET['submitted']);

// Ellenőrizzük, hogy valid rendelés-e
$order = null;
$alreadyRated = false;
if ($orderId) {
    $stmt = $pdo->prepare("SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.order_id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    // Ellenőrizzük, hogy már értékelték-e
    if ($order) {
        try {
            $stmt = $pdo->prepare("SELECT rating FROM order_ratings WHERE order_id = ?");
            $stmt->execute([$orderId]);
            $existingRating = $stmt->fetch();
            if ($existingRating) {
                $alreadyRated = true;
                $submitted = true; // Mutassuk a köszönő üzenetet
            }
        } catch (PDOException $e) {
            // Tábla még nem létezik - nem gond
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Értékelés - YoursyWear</title>
    <link rel="stylesheet" href="/webshop/public/css/style.css">
    <style>
        .rating-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .rating-container h1 {
            margin-bottom: 10px;
        }
        .rating-container .order-num {
            color: #666;
            margin-bottom: 30px;
        }
        .stars {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 30px 0;
        }
        .stars input {
            display: none;
        }
        .stars label {
            font-size: 50px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }
        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label {
            color: #ffc107;
        }
        .stars:hover label {
            color: #ddd;
        }
        .stars label:hover,
        .stars label:hover ~ label {
            color: #ffc107;
        }
        /* Reverse order for proper hover effect */
        .stars {
            flex-direction: row-reverse;
            justify-content: center;
        }
        textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: 10px;
            font-size: 16px;
            resize: vertical;
            min-height: 120px;
            margin: 20px 0;
        }
        textarea:focus {
            outline: none;
            border-color: #000;
        }
        .submit-btn {
            background: #000;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .submit-btn:hover {
            background: #333;
            transform: translateY(-2px);
        }
        .success-message {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            padding: 30px;
            border-radius: 15px;
            margin: 30px 0;
        }
        .success-message h2 {
            color: #2e7d32;
            margin: 0 0 10px 0;
        }
        .error-message {
            background: #ffebee;
            border: 2px solid #f44336;
            padding: 30px;
            border-radius: 15px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover {
            color: #000;
        }
    </style>
</head>
<body style="background: #f5f5f5; min-height: 100vh; padding: 20px;">
    <div class="rating-container">
        <a href="/webshop/" style="text-decoration: none; color: #000;">
            <h1>YoursyWear</h1>
        </a>
        
        <?php if ($submitted): ?>
            <div class="success-message">
                <h2>✅ Köszönjük az értékelést!</h2>
                <p>Véleményed sokat segít nekünk a fejlődésben.</p>
            </div>
            <a href="/webshop/" class="back-link">← Vissza a főoldalra</a>
            
        <?php elseif (!$order): ?>
            <div class="error-message">
                <h2>❌ Rendelés nem található</h2>
                <p>Ez a link érvénytelen vagy lejárt.</p>
            </div>
            <a href="/webshop/" class="back-link">← Vissza a főoldalra</a>
            
        <?php else: ?>
            <p class="order-num">Rendelés: #<?= $orderId ?></p>
            
            <h2>Mennyire voltál elégedett?</h2>
            <p style="color: #666;">Kérjük, értékeld a vásárlási élményedet!</p>
            
            <form action="/webshop/api/submit-rating.php" method="POST">
                <input type="hidden" name="order_id" value="<?= $orderId ?>">
                
                <div class="stars">
                    <input type="radio" name="rating" value="5" id="star5" required>
                    <label for="star5">★</label>
                    <input type="radio" name="rating" value="4" id="star4">
                    <label for="star4">★</label>
                    <input type="radio" name="rating" value="3" id="star3">
                    <label for="star3">★</label>
                    <input type="radio" name="rating" value="2" id="star2">
                    <label for="star2">★</label>
                    <input type="radio" name="rating" value="1" id="star1">
                    <label for="star1">★</label>
                </div>
                
                <textarea name="comment" placeholder="Írd le véleményed (opcionális)..."></textarea>
                
                <button type="submit" class="submit-btn">Értékelés küldése</button>
            </form>
            
            <a href="/webshop/" class="back-link">← Vissza a főoldalra</a>
        <?php endif; ?>
    </div>
</body>
</html>
