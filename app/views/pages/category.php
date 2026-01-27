<?php
$gender = $_GET['gender'] ?? null;
$type   = $_GET['type'] ?? null;

/*
    FAKE ADAT – úgy kezeljük, mintha DB-ből jönne
*/
$products = [
    [
        "name" => "Oversize póló",
        "gender" => "male",
        "type" => "ruhazat"
    ],
    [
        "name" => "Slim fit ing",
        "gender" => "male",
        "type" => "ruhazat"
    ],
    [
        "name" => "Női crop top",
        "gender" => "female",
        "type" => "ruhazat"
    ],
    [
        "name" => "Női sportcipő",
        "gender" => "female",
        "type" => "cipok"
    ],
    [
        "name" => "Férfi sneaker",
        "gender" => "male",
        "type" => "cipok"
    ],
];
?>

<div class="max-w-7xl mx-auto px-6 py-20">

    <!-- OLDAL CÍM -->
    <h1 class="text-4xl font-bold mb-8">
        <?= $gender === 'male' ? 'Férfi' : 'Női' ?>
        <?= $type ? ' – ' . ucfirst($type) : '' ?>
    </h1>

    <!-- TERMÉKEK -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

        <?php
        $found = false;

        foreach ($products as $product):
            if ($product['gender'] === $gender &&
                (!$type || $product['type'] === $type)
            ):
                $found = true;
        ?>

            <div class="bg-white border p-6">
                <h2 class="font-semibold text-lg">
                    <?= $product['name']; ?>
                </h2>
                <p class="text-sm text-gray-500">
                    <?= ucfirst($product['gender']); ?> /
                    <?= ucfirst($product['type']); ?>
                </p>
            </div>

        <?php
            endif;
        endforeach;
        ?>

        <?php if (!$found): ?>
            <p class="text-gray-500 col-span-full">
                Nincs találat ebben a kategóriában.
            </p>
        <?php endif; ?>

    </div>

</div>
