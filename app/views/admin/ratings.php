<?php
// Értékelések lekérdezése
try {
    $stmt = $pdo->query("
        SELECT r.*, o.order_id, u.username, u.email
        FROM order_ratings r
        JOIN orders o ON r.order_id = o.order_id
        JOIN users u ON o.user_id = u.user_id
        ORDER BY r.created_at DESC
    ");
    $ratings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Tábla még nem létezik
    $ratings = [];
}

// Statisztikák
$totalRatings = count($ratings);
$avgRating = $totalRatings > 0 ? array_sum(array_column($ratings, 'rating')) / $totalRatings : 0;
$ratingCounts = array_count_values(array_column($ratings, 'rating'));
?>

<!-- Statisztikák -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Összes értékelés</p>
                <p class="text-3xl font-bold text-gray-800"><?= $totalRatings ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="las la-star text-2xl text-blue-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Átlag értékelés</p>
                <p class="text-3xl font-bold text-gray-800"><?= number_format($avgRating, 1) ?> <span class="text-yellow-500">★</span></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="las la-chart-line text-2xl text-yellow-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">5 csillagos</p>
                <p class="text-3xl font-bold text-green-600"><?= $ratingCounts[5] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="las la-smile-beam text-2xl text-green-600"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">1-2 csillagos</p>
                <p class="text-3xl font-bold text-red-600"><?= ($ratingCounts[1] ?? 0) + ($ratingCounts[2] ?? 0) ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                <i class="las la-frown text-2xl text-red-600"></i>
            </div>
        </div>
    </div>
</div>

<!-- Értékelések lista -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b">
        <h2 class="text-lg font-semibold">Értékelések</h2>
    </div>
    
    <?php if (empty($ratings)): ?>
        <div class="p-12 text-center text-gray-500">
            <i class="las la-star text-6xl mb-4 text-gray-300"></i>
            <p>Még nincsenek értékelések</p>
        </div>
    <?php else: ?>
        <div class="divide-y">
            <?php foreach ($ratings as $rating): ?>
                <div class="p-6 hover:bg-gray-50">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-4 mb-2">
                                <!-- Csillagok -->
                                <div class="text-yellow-500 text-xl">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?= $i <= $rating['rating'] ? '★' : '☆' ?>
                                    <?php endfor; ?>
                                </div>
                                
                                <!-- Rendelés szám -->
                                <span class="text-sm text-gray-500">
                                    Rendelés #<?= $rating['order_id'] ?>
                                </span>
                            </div>
                            
                            <!-- Felhasználó -->
                            <div class="text-sm text-gray-600 mb-2">
                                <i class="las la-user mr-1"></i>
                                <?= htmlspecialchars($rating['username']) ?>
                                <span class="text-gray-400 ml-2"><?= htmlspecialchars($rating['email']) ?></span>
                            </div>
                            
                            <!-- Vélemény -->
                            <?php if (!empty($rating['comment'])): ?>
                                <div class="bg-gray-50 rounded-lg p-4 mt-3">
                                    <p class="text-gray-700 italic">"<?= htmlspecialchars($rating['comment']) ?>"</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Dátum -->
                        <div class="text-right text-sm text-gray-500">
                            <?= date('Y.m.d H:i', strtotime($rating['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
