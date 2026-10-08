<?php
/**
 * Migration: Create `orders` table
 * Run once: http://localhost/SIA/database/migrate.php
 */
require __DIR__ . '/connection.php';

$sql = "
CREATE TABLE IF NOT EXISTS `orders` (
    `order_id`    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `buyer_id`    INT UNSIGNED NOT NULL,
    `product_id`  INT UNSIGNED NOT NULL,
    `quantity`    DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(12,2) NOT NULL,
    `status`      ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_buyer`   (`buyer_id`),
    INDEX `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $conn->exec($sql);
    echo "&#10003; <strong>orders</strong> table created (or already exists).<br>";
    echo "<a href='../login.php'>&rarr; Go to Login</a>";
} catch (PDOException $e) {
    echo "&#10007; Error: " . htmlspecialchars($e->getMessage());
}
?>
