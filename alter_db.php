<?php
require_once __DIR__ . '/config.php';

try {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN promo_id INT NULL AFTER user_id");
    $pdo->exec("ALTER TABLE bookings ADD FOREIGN KEY (promo_id) REFERENCES promos(id) ON DELETE SET NULL");
    echo "Successfully altered table.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
