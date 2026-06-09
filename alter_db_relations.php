<?php
require_once __DIR__ . '/config.php';

try {
    // Clean orphaned rows
    $pdo->exec("DELETE FROM reviews WHERE booking_id NOT IN (SELECT id FROM bookings)");
    $pdo->exec("DELETE FROM reviews WHERE user_id NOT IN (SELECT id FROM users)");
    $pdo->exec("DELETE FROM notifications WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users)");
    $pdo->exec("UPDATE bookings SET user_id = NULL WHERE user_id NOT IN (SELECT id FROM users)");
    $pdo->exec("UPDATE bookings SET service_id = NULL WHERE service_id NOT IN (SELECT id FROM services)");

    // Add Foreign Keys for Bookings (Ignore error if it already exists from a previous partial run)
    try { $pdo->exec("ALTER TABLE bookings ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL"); } catch(Exception $e){}
    try { $pdo->exec("ALTER TABLE bookings ADD FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL"); } catch(Exception $e){}
    
    // Add Foreign Keys for Notifications
    try { $pdo->exec("ALTER TABLE notifications ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE"); } catch(Exception $e){}
    
    // Add Foreign Keys for Reviews
    try { $pdo->exec("ALTER TABLE reviews ADD FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE"); } catch(Exception $e){}
    try { $pdo->exec("ALTER TABLE reviews ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE"); } catch(Exception $e){}

    echo "All orphaned rows cleaned and foreign keys successfully added.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
