<?php
// ===== SESSION YÖNETİMİ =====

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('vvr_normalize_cart_items')) {
    function vvr_normalize_cart_items($cart)
    {
        $normalized = [];

        if (!is_array($cart)) {
            return $normalized;
        }

        foreach ($cart as $key => $item) {
            if (is_array($item) && isset($item['id'])) {
                $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                if ($quantity < 1) {
                    $quantity = 1;
                }

                $normalized[] = [
                    'id' => (string)$item['id'],
                    'quantity' => $quantity,
                ];
                continue;
            }

            if (is_scalar($item) && is_numeric($key)) {
                $quantity = (int)$item;
                if ($quantity < 1) {
                    $quantity = 1;
                }

                $normalized[] = [
                    'id' => (string)$key,
                    'quantity' => $quantity,
                ];
            }
        }

        return array_values($normalized);
    }
}

if (!function_exists('vvr_normalize_favorites')) {
    function vvr_normalize_favorites($favorites)
    {
        $normalized = [];

        if (!is_array($favorites)) {
            return $normalized;
        }

        foreach ($favorites as $favorite) {
            if (is_scalar($favorite) && $favorite !== '') {
                $normalized[] = (string)$favorite;
            }
        }

        return array_values(array_unique($normalized));
    }
}

if (!function_exists('vvr_cart_total')) {
    function vvr_cart_total($cart)
    {
        $total = 0;

        foreach (vvr_normalize_cart_items($cart) as $item) {
            $total += (int)($item['quantity'] ?? 0);
        }

        return $total;
    }
}

if (!function_exists('vvr_sync_session_state')) {
    function vvr_sync_session_state()
    {
        if (!isset($_SESSION['favoriler']) || !is_array($_SESSION['favoriler'])) {
            $_SESSION['favoriler'] = [];
        }

        if (!isset($_SESSION['sepet']) || !is_array($_SESSION['sepet'])) {
            $_SESSION['sepet'] = [];
        }

        $_SESSION['favoriler'] = vvr_normalize_favorites($_SESSION['favoriler']);
        $_SESSION['sepet'] = vvr_normalize_cart_items($_SESSION['sepet']);
    }
}

vvr_sync_session_state();
