<?php
require_once __DIR__ . '/config.php';

try {
    $pdo->exec("ALTER TABLE bookings ADD FOREIGN KEY (promo_id) REFERENCES promos(id) ON DELETE SET NULL");
    echo "Successfully linked table bookings to promos.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
