<?php
include('classes/db.php');

$dbh = new Db();

// SQL для создания таблицы оплаченных объявлений
$sql = "CREATE TABLE IF NOT EXISTS paid_ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT NOT NULL,
    chat_id BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ad_id (ad_id),
    INDEX idx_chat_id (chat_id),
    INDEX idx_created_at (created_at)
)";

try {
    $dbh->query($sql);
    echo "Таблица paid_ads создана успешно\n";
} catch (Exception $e) {
    echo "Ошибка создания таблицы: " . $e->getMessage() . "\n";
}

?>