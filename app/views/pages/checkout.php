<?php
// elvárt változók a controllerből:
// $items  → kosár tételek
// $total  → végösszeg
?>

<form method="post" action="index.php?page=checkout">
<main class="max-w-7xl mx-auto px-4 lg:px-8 py-8">

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- BAL OLDAL -->
    <section class="lg:col-span-2 space-y-10">

      <!-- SZÁLLÍTÁSI MÓD -->
      <div class="border rounded-xl p-6 space-y-4">
        <h2 class="text-lg font-semibold">Szállítási mód</h2>

        <label class="flex items-center gap-3">
          <input type="radio" name="delivery_method_id" value="1" checked>
          <span>Házhozszállítás</span>
        </label>

        <label class="flex items-center gap-3">
          <input type="radio" name="delivery_method_id" value="2">
          <span>Csomagautomata</span>
        </label>
      </div>

      <!-- SZÁMLÁZÁSI CÍM -->
      <div class="border rounded-xl p-6 space-y-4">
        <h2 class="text-lg font-semibold">Számlázási cím</h2>

        <input type="hidden" name="billing_city_id" id="billing_city_id">

        <input
          id="billing_postcode"
          class="w-full border rounded p-3"
          placeholder="Irányítószám"
        >

        <input
          id="billing_city_name"
          class="w-full border rounded p-3 bg-gray-100"
          placeholder="Város"
          readonly
        >

        <input
          name="billing_street"
          class="w-full border rounded p-3"
          placeholder="Utca, házszám"
        >
      </div>

      <!-- SZÁLLÍTÁSI CÍM -->
      <div class="border rounded-xl p-6 space-y-4">
        <h2 class="text-lg font-semibold">Szállítási cím</h2>

        <label class="flex items-center gap-2 text-sm">
          <input type="checkbox" id="sameAddress" checked>
          Megegyezik a számlázási címmel
        </label>

        <div id="shippingAddress" class="space-y-3 hidden">
          <input type="hidden" name="shipping_city_id" id="shipping_city_id">

          <input
            id="shipping_postcode"
            class="w-full border rounded p-3"
            placeholder="Irányítószám"
          >

          <input
            id="shipping_city_name"
            class="w-full border rounded p-3 bg-gray-100"
            placeholder="Város"
            readonly
          >

          <input
            name="shipping_street"
            class="w-full border rounded p-3"
            placeholder="Utca, házszám"
          >
        </div>
      </div>

      <!-- FIZETÉS -->
      <div class="border rounded-xl p-6 space-y-4">
        <h2 class="text-lg font-semibold">Fizetés</h2>

        <label class="flex items-center gap-3">
          <input type="radio" name="payment_method_id" value="1" checked>
          <span>Kártyás fizetés</span>
        </label>

        <label class="flex items-center gap-3">
          <input type="radio" name="payment_method_id" value="2">
          <span>Utánvét</span>
        </label>
      </div>

      <!-- FELTÉTELEK -->
      <label class="flex gap-2 text-sm">
        <input type="checkbox" required>
        Elfogadom az ÁSZF-et és az adatkezelési tájékoztatót
      </label>

    </section>

    <!-- JOBB OLDAL – KOSÁR -->
    <aside class="border rounded-xl p-6 space-y-6 sticky top-24">

      <h3 class="text-lg font-semibold">Rendelési összegzés</h3>

      <?php foreach ($items as $item): ?>
        <div class="flex gap-4">
          <img
            src="/uploads/<?= htmlspecialchars($item['image']) ?>"
            class="w-20 h-24 object-cover rounded"
            alt=""
          >
          <div class="text-sm flex-1">
            <div class="font-medium"><?= htmlspecialchars($item['name']) ?></div>
            <div class="text-gray-500">
              Méret: <?= htmlspecialchars($item['size']) ?> ·
              Mennyiség: <?= (int)$item['quantity'] ?>
            </div>
            <div class="font-semibold mt-1">
              <?= number_format($item['line_total'], 0, ',', ' ') ?> Ft
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="border-t pt-4 space-y-2 text-sm">
        <div class="flex justify-between">
          <span>Rendelési érték</span>
          <span><?= number_format($total, 0, ',', ' ') ?> Ft</span>
        </div>
        <div class="flex justify-between">
          <span>Szállítás</span>
          <span>Ingyenes</span>
        </div>
      </div>

      <div class="flex justify-between font-bold text-lg pt-4 border-t">
        <span>Fizetendő</span>
        <span><?= number_format($total, 0, ',', ' ') ?> Ft</span>
      </div>

      <button class="w-full bg-black text-white py-4 rounded-xl font-semibold">
        Megrendelés leadása
      </button>

    </aside>

  </div>
</main>
</form>


