<?php
/**
 * Migration: Create `orders` and `messages` tables
 * Run once: http://localhost/SIA/database/migrate.php
 */
require __DIR__ . '/connection.php';

$sqlOrders = "
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

$sqlMessages = "
CREATE TABLE IF NOT EXISTS `messages` (
    `message_id`  INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `sender_id`   INT(11) NOT NULL,
    `receiver_id` INT(11) NOT NULL,
    `post_id`     INT(11) DEFAULT NULL,
    `message`     TEXT NOT NULL,
    `is_read`     TINYINT(1) NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_sender`   (`sender_id`),
    INDEX `idx_receiver` (`receiver_id`),
    INDEX `idx_post`     (`post_id`),
    INDEX `idx_receiver_unread` (`receiver_id`, `is_read`),
    CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_messages_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_messages_post` FOREIGN KEY (`post_id`) REFERENCES `post` (`post_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    $conn->exec($sqlOrders);
    echo "&#10003; <strong>orders</strong> table created (or already exists).<br>";
    $conn->exec($sqlMessages);
    echo "&#10003; <strong>messages</strong> table created (or already exists).<br>";
    echo "<a href='../login.php'>&rarr; Go to Login</a>";
} catch (PDOException $e) {
    echo "&#10007; Error: " . htmlspecialchars($e->getMessage());
}
?>
