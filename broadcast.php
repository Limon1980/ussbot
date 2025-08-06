<?php
include('/var/www/html/vendor/autoload.php');
include('/var/www/html/usssellbot/classes/db.php');
include('/var/www/html/usssellbot/func.lib.php');

use Telegram\Bot\Api;

$token = 'token';
$telegram = new Api($token);
$dbh = new Db();
$chatAdmin = 'chat_id';


// Проверяем, передан ли ID рассылки
if ($argc < 2) {
    $error = "Ошибка: ID рассылки не передан.";
    $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => $error]);
    $dbh->query("INSERT INTO broadcast_log (start_time, end_time, message, success_count, fail_count, error) VALUES (NOW(), NOW(), '', 0, 0, '$error')");
    exit;
}
$broadcast_id = $argv[1];

// Извлекаем данные рассылки
$row = $dbh->query("SELECT message, img FROM broadcast_log WHERE id = $broadcast_id");
if (!$row || !isset($row[0]['message'])) {
    $error = "Ошибка: данные рассылки для ID $broadcast_id не найдены.";
    $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => $error]);
    $dbh->query("UPDATE broadcast_log SET end_time = NOW(), error = " . $error. " WHERE id = $broadcast_id");
    exit;
}
$message = $row[0]['message'];
$img = $row[0]['img'] ?? '';

// Выбираем всех пользователей, кроме администратора
$users = $dbh->query("SELECT DISTINCT chat_id FROM bufer_baraholka_bot WHERE is_active = 1");


$success_count = 0;
$fail_count = 0;
$error_log = '';

$batch_size = 20;
$batches = array_chunk($users, $batch_size);

foreach ($batches as $batch) {
    foreach ($batch as $user) {
        $user_chat_id = $user['chat_id'];
        try {
            if ($img) {
                // Отправка с изображением
                $request_params = [
                    'chat_id' => $user_chat_id,
                    'photo' => $img,
                    'caption' => $message,
                    'parse_mode' => 'HTML',
                    'disable_notification' => true
                ];
                $response = sendTm($token, 'sendPhoto', $request_params);
            } else {
                // Отправка текстового сообщения
                $request_params = [
                    'chat_id' => $user_chat_id,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                    'disable_notification' => true
                ];
                $response = sendTm($token, 'sendMessage', $request_params);
            }

            $response_data = json_decode($response, true);
            if ($response_data['ok']) {
                $success_count++;
                // Поле is_active не изменяем при успехе
            } else {
                $fail_count++;
                $error_msg = $response_data['description'] ?? 'Unknown error';
                $error_log .= "Chat ID $user_chat_id: $error_msg\n";
                if ($response_data['error_code'] == 403) {
                    // Обновляем is_active = 0, если бот заблокирован
                    $dbh->query("UPDATE bufer_baraholka_bot SET is_active = 0 WHERE chat_id = $user_chat_id");
                }
            }
        } catch (Exception $e) {
            $fail_count++;
            $error_msg = $e->getMessage();
            $error_log .= "Chat ID $user_chat_id: $error_msg\n";
            // Обновляем is_active = 0 при исключении (например, бот заблокирован)
            $dbh->query("UPDATE bufer_baraholka_bot SET is_active = 0 WHERE chat_id = $user_chat_id");
        }

        usleep(50000); // Задержка 0.05 секунды между отправками
    }
    sleep(1); // Задержка 1 секунда после отправки батча
}
/* foreach ($users as $user) {
    $user_chat_id = $user['chat_id'];
    try {
        if ($img) {
            // Отправка с изображением
            $request_params = [
                'chat_id' => $user_chat_id,
                'photo' => $img,
                'caption' => $message,
                'parse_mode' => 'HTML',
				'disable_notification' => true
            ];
            $response = sendTm($token, 'sendPhoto', $request_params);
        } else {
            // Отправка текстового сообщения
            $request_params = [
                'chat_id' => $user_chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
				'disable_notification' => true
            ];
            $response = sendTm($token, 'sendMessage', $request_params);
        }

        $response_data = json_decode($response, true);
        if ($response_data['ok']) {
            $success_count++;
            // Поле is_active не изменяем при успехе
        } else {
			
            $fail_count++;
            $error_msg = $response_data['description'] ?? 'Unknown error';
            $error_log .= "Chat ID $user_chat_id: $error_msg\n";
            if ($response_data['error_code'] == 403) {
                // Обновляем is_active = 0, если бот заблокирован
                $dbh->query("UPDATE bufer_baraholka_bot SET is_active = 0 WHERE chat_id = $user_chat_id");
            }
        }
    } catch (Exception $e) {
        $fail_count++;
        $error_msg = $e->getMessage();
        $error_log .= "Chat ID $user_chat_id: $error_msg\n";
        // Обновляем is_active = 0 при исключении (например, бот заблокирован)
        $dbh->query("UPDATE bufer_baraholka_bot SET is_active = 0 WHERE chat_id = $user_chat_id");
    }

    // sleep(1); // Задержка для соблюдения лимитов Telegram
	usleep( 5 * 100000 );
} */

// Сохраняем результаты рассылки
$error_log = $error_log ? $error_log : 'NULL';
$dbh->query("UPDATE broadcast_log SET end_time = NOW(), success_count = $success_count, fail_count = $fail_count, error = '$error_log' WHERE id = $broadcast_id");

// Отправляем отчет администратору
$report = "Рассылка (ID: $broadcast_id) завершена!\nУспешно: $success_count\nНе удалось: $fail_count";
if ($img) {
    $report .= "\nРассылка включала изображение.";
}
if ($error_log != 'NULL') {
    $report .= "\nПодробности ошибок записаны в лог.";
}
$telegram->sendMessage([
    'chat_id' => $chatAdmin,
    'text' => $report,
    'parse_mode' => 'HTML'
]);
?>