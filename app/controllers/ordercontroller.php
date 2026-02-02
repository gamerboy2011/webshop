<?php

class OrderController
{
    /* =========================
       CHECKOUT OLDAL (GET)
       ========================= */
    public function showCheckout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['cart'])) {
            die('A kosár üres.');
        }

        // a kosár megjelenítését a Model készíti elő
        $data = ordermodel::getCartSummary($_SESSION['cart']);

        $items = $data['items'];
        $total = $data['total'];

        require __DIR__ . '/../views/pages/checkout.php';
    }

    /* =========================
       RENDELÉS MENTÉS (POST)
       ========================= */
    public function checkout(): void
    {
        global $pdo;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['cart'])) {
            die('A kosár üres.');
        }

        /* ===== ALAP ADATOK ===== */
        $paymentMethodId  = (int)($_POST['payment_method_id'] ?? 0);
        $deliveryMethodId = (int)($_POST['delivery_method_id'] ?? 0);
        $pickupPointId    = $_POST['pickup_point_id'] ?? null;

        if ($paymentMethodId <= 0 || $deliveryMethodId <= 0) {
            die('Fizetési vagy szállítási mód nincs kiválasztva.');
        }

        /* ===== SZÁMLÁZÁSI CÍM ===== */
        $billingCityId = (int)($_POST['billing_city_id'] ?? 0);
        $billingStreet = trim($_POST['billing_street'] ?? '');

        if ($billingCityId <= 0 || $billingStreet === '') {
            die('A számlázási cím hiányos.');
        }

        /* ===== SZÁLLÍTÁSI CÍM ===== */
        if ($deliveryMethodId === 1) {
            $shippingCityId = (int)($_POST['shipping_city_id'] ?? 0);
            $shippingStreet = trim($_POST['shipping_street'] ?? '');

            if ($shippingCityId <= 0 || $shippingStreet === '') {
                die('A szállítási cím hiányos.');
            }
        } else {
            $shippingCityId = null;
            $shippingStreet = null;
        }

        /* ===== FIX USER (VIZSGA) ===== */
        $userId = 1;

        $pdo->beginTransaction();

        try {
            /* ===== ORDER ===== */
            $orderId = OrderModel::createOrder([
                'user_id'            => $userId,
                'payment_method_id'  => $paymentMethodId,
                'delivery_method_id' => $deliveryMethodId,
                'pickup_point_id'    => $pickupPointId,
                'billing_city_id'    => $billingCityId,
                'billing_street'     => $billingStreet,
                'shipping_city_id'   => $shippingCityId,
                'shipping_street'    => $shippingStreet
            ], $pdo);

            /* ===== ORDER TÉTELEK ===== */
            foreach ($_SESSION['cart'] as $item) {
                OrderModel::addOrderItem(
                    $orderId,
                    $item,
                    $pdo
                );
            }

            $pdo->commit();
            unset($_SESSION['cart']);

            header('Location: index.php?page=home');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            die('Hiba történt: ' . $e->getMessage());
        }
    }
}