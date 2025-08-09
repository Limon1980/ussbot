П<?php

function FormChars ($p1) {
return htmlspecialchars (trim($p1), ENT_QUOTES);
}

function get_phone($text)
{

	$text = strip_tags($text);
	preg_match('/((8|\+7|Тел:|Тел: |Тел\.:|Тел\.: |Тел\.|Тел\. |Тел|Тел|тел|тел\.|тел\:|тел\.: |т|т |т\.|т\. )[^Цена]?[0-9\-\ \)\(]{9,18})/is', $text, $array);
	if (isset($array[0])) {
		$arr = $array[0];
//$unumber=implode(', ',$arr);
		$unumber = str_replace('Тел.:', '', $arr);
//echo $unumber.'<br>';
		$maspoisk = array("(", ")", "-", ",", "Тел:", "Тел", ".", ":", " ", "тел", "т", "e", "л", "Т");
		$unumber = str_replace($maspoisk, '', $unumber) * 1;

//echo $unumber.'<br>';
	if (is_numeric($unumber)) {
		$unumber = '+7' . substr($unumber, -10);
		$len = strlen($unumber);
		if ($len < 10) {
			return NULL;
		} else {
			return $unumber;
		}
	} else {
		return NULL;
	}
}
}

// function cat_phone ($text){
// global $phone;
// $text=$text;
// $pattern = '/((8|\+7|Тел:|Тел: |Тел\.:|Тел\.: |Тел\.|Тел\. |Тел|Тел|тел|тел\.|тел\:|тел\.: |т|т |т\.|т\. |Т |T)[^Цена]?[0-9\-\ \)\(]{9,18})/is';
// $text = preg_replace($pattern, " ".$phone." ", $text,1);
// return $text;
		
// }

function cat_phone($text) {
    global $phone;

    // Регулярное выражение для поиска номеров телефонов
    $pattern = '/((8|\+7|Тел:|Тел: |Тел\.:|Тел\.: |Тел\.|Тел\. |Тел|Тел|тел|тел\.|тел\:|тел\.: |т|т |т\.|т\. |Т |T)[^Цена]?[0-9\-\ \)\(]{9,18})/is';

    // Функция обратного вызова для обработки каждого найденного номера
    $callback = function ($matches) use ($phone) {
        // Получаем найденный номер
        $foundNumber = $matches[0];

        // Преобразуем найденный номер в правильный формат с помощью get_phone
        $formattedNumber = get_phone($foundNumber);

        // Если номер успешно преобразован, возвращаем его
        if ($formattedNumber !== NULL) {
            return " " . $formattedNumber . " ";
        }

        // Если номер не преобразован, возвращаем исходное значение
        return $foundNumber;
    };

    // Выполняем замену для всех совпадений с использованием callback-функции
    $text = preg_replace_callback($pattern, $callback, $text);

    return $text;
}


function sendMedia($token, $request_params)
{
			$curl = curl_init();
			$fields = http_build_query($request_params);
			curl_setopt($curl, CURLOPT_URL, 'https://api.telegram.org/bot'.$token.'/sendMediaGroup?');
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$res = curl_exec($curl);
			curl_close($curl);
}

function sendTm ($token, $method, $request_params)
{
	$curl = curl_init();
	$fields = http_build_query($request_params);
	curl_setopt($curl, CURLOPT_URL, 'https://api.telegram.org/bot'.$token.'/'.$method.'?');
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,1);
	//curl_setopt($curl, CURLOPT_TIMEOUT,2);
	$result = curl_exec($curl);
	return $result;
	//echo $result.'<br>';
	curl_close($curl);
}

function sendTm2($token, $method, $request_params)
{
    // Формирование URL-адреса запроса
    $url = 'https://api.telegram.org/bot' . $token . '/' . $method;

    // Инициализация cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Отправка запроса и получение ответа
    $result = curl_exec($ch);
    curl_close($ch);

    // Декодирование ответа JSON
    $result = json_decode($result, true);
	return $result;

    // Обработка ответа
    if ($result['ok']) {
        // Успешная отправка сообщения
        echo 'Сообщение отправлено успешно.' . PHP_EOL;
    } else {
        // Ошибка отправки сообщения
        echo 'Ошибка отправки сообщения: ' . $result['description'] . PHP_EOL;
    }
}

function ImgPr($p1, $p2, $p3, $p4 ) {
	// $p1 - директория и файл исходного изображения
	// $p2 - директория и файл назначения
	// $p3 - размер изображения по горизонтали
	// $p4 - размер изображения по вертикали
$size=GetImageSize ($p1);
$src=ImageCreateFromJPEG ($p1);
$iw=$size[0];
$ih=$size[1];
if ($iw > $ih){
$koe=$iw/$p3;
$new_h=ceil ($ih/$koe);
$dst=ImageCreateTrueColor ($p3, $new_h);
ImageCopyResampled ($dst, $src, 0, 0, 0, 0, $p3, $new_h, $iw, $ih);
ImageJPEG ($dst, $p2, 90);
imagedestroy($src);
}
else{
$koe=$ih/$p4;
$new_w=ceil ($iw/$koe);
$dst=ImageCreateTrueColor ($new_w, $p4);
ImageCopyResampled ($dst, $src, 0, 0, 0, 0, $new_w, $p4, $iw, $ih);
ImageJPEG ($dst, $p2, 90);
imagedestroy($src);	
	
}
}

function cropImage($aInitialImageFilePath, $aNewImageFilePath, $aNewImageWidth, $aNewImageHeight) {
    if (($aNewImageWidth < 0) || ($aNewImageHeight < 0)) {
        return false;
    }

    // Массив с поддерживаемыми типами изображений
    $lAllowedExtensions = array(1 => "gif", 2 => "jpeg", 3 => "png"); 
    
    // Получаем размеры и тип изображения в виде числа
    list($lInitialImageWidth, $lInitialImageHeight, $lImageExtensionId) = getimagesize($aInitialImageFilePath); 
    
    if (!array_key_exists($lImageExtensionId, $lAllowedExtensions)) {
        return false;
    }
    $lImageExtension = $lAllowedExtensions[$lImageExtensionId];
    
    // Получаем название функции, соответствующую типу, для создания изображения
    $func = 'imagecreatefrom' . $lImageExtension; 
    // Создаём дескриптор исходного изображения
    $lInitialImageDescriptor = $func($aInitialImageFilePath);

    // Определяем отображаемую область
    $lCroppedImageWidth = 0;
    $lCroppedImageHeight = 0;
    $lInitialImageCroppingX = 0;
    $lInitialImageCroppingY = 0;
    if ($aNewImageWidth / $aNewImageHeight > $lInitialImageWidth / $lInitialImageHeight) {
        $lCroppedImageWidth = floor($lInitialImageWidth);
        $lCroppedImageHeight = floor($lInitialImageWidth * $aNewImageHeight / $aNewImageWidth);
        $lInitialImageCroppingY = floor(($lInitialImageHeight - $lCroppedImageHeight) / 2);
    } else {
        $lCroppedImageWidth = floor($lInitialImageHeight * $aNewImageWidth / $aNewImageHeight);
        $lCroppedImageHeight = floor($lInitialImageHeight);
        $lInitialImageCroppingX = floor(($lInitialImageWidth - $lCroppedImageWidth) / 2);
    }
    
    // Создаём дескриптор для выходного изображения
    $lNewImageDescriptor = imagecreatetruecolor($aNewImageWidth, $aNewImageHeight);
    imagecopyresampled($lNewImageDescriptor, $lInitialImageDescriptor, 0, 0, $lInitialImageCroppingX, $lInitialImageCroppingY, $aNewImageWidth, $aNewImageHeight, $lCroppedImageWidth, $lCroppedImageHeight);
    $func = 'image' . $lImageExtension;
    
    // сохраняем полученное изображение в указанный файл
    return $func($lNewImageDescriptor, $aNewImageFilePath);
}


// Функция проверки лимита объявлений (2 объявления в день с 14:00 UTC)
function checkAdLimit($dbh, $chat_id) {
    // Получаем начало текущего дня с 14:00 UTC
    $today_start = new DateTime();
    $today_start->setTimezone(new DateTimeZone('UTC'));
    $today_start->setTime(14, 0, 0);
    
    // Если текущее время меньше 14:00, берем предыдущий день
    $now = new DateTime('now', new DateTimeZone('UTC'));
    if ($now->format('H:i') < '14:00') {
        $today_start->modify('-1 day');
    }
    
    $start_time = $today_start->format('Y-m-d H:i:s');
    
    // Проверяем количество объявлений с начала дня
    try {
        $result = $dbh->query("SELECT COUNT(*) as count FROM base_baraholka WHERE chat_id = $chat_id AND date >= '$start_time' AND post = 1");
        return $result[0]['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

// Функция проверки оплаченных объявлений
function checkPaidAds($dbh, $chat_id) {
    // Получаем начало текущего дня с 14:00 UTC
    $today_start = new DateTime();
    $today_start->setTimezone(new DateTimeZone('UTC'));
    $today_start->setTime(14, 0, 0);
    
    // Если текущее время меньше 14:00, берем предыдущий день
    $now = new DateTime('now', new DateTimeZone('UTC'));
    if ($now->format('H:i') < '14:00') {
        $today_start->modify('-1 day');
    }
    
    $start_time = $today_start->format('Y-m-d H:i:s');
    
    // Проверяем количество оплаченных объявлений с начала дня
    try {
        $result = $dbh->query("SELECT COUNT(*) as count FROM paid_ads WHERE chat_id = $chat_id AND created_at >= '$start_time'");
        return $result[0]['count'] ?? 0;
    } catch (Exception $e) {
        return 0;
    }
}

// Функция создания кнопки оплаты
function createPaymentButton($IdPhoto) {
    return [
        'inline_keyboard' => [
            [
                ['text' => '💰 Оплатить размещение (50 руб)', 'callback_data' => 'pay_' . $IdPhoto]
            ]
        ]
    ];
}

function publishAd($dbh, $telegram, $token, $chat_id, $IdPhoto, $chatAdmin)
{
    // Проверяем, оплачено ли текущее объявление
    try {
        $current_ad_paid = $dbh->query("SELECT * FROM paid_ads WHERE ad_id = $IdPhoto");
        $is_current_paid = !empty($current_ad_paid);
    } catch (Exception $e) {
        $is_current_paid = false;
    }
    
    // Если объявление не оплачено, проверяем лимит
    if (!$is_current_paid) {
        $ads_count = checkAdLimit($dbh, $chat_id);
        
        // Если превышен лимит
        if ($ads_count >= 2) {
            $reply = "❌ <b>Лимит объявлений исчерпан!</b>\n\n";
            $reply .= "Вы можете подавать не более <b>2 объявлений в сутки</b> (с 14:00 по UTC).\n";
            $reply .= "Сегодня вы уже подали: <b>{$ads_count} объявления</b>\n\n";
            $reply .= "Завтра лимит обновится в 14:00 UTC.\n\n";
            $reply .= "Или вы можете оплатить размещение объявления сейчас:";
            
            $reply_markup = json_encode(createPaymentButton($IdPhoto));
            
            $telegram->sendMessage([
                'chat_id' => $chat_id,
                'text' => $reply,
                'parse_mode' => 'HTML',
                'reply_markup' => $reply_markup
            ]);
            
            return false; // Не публикуем объявление
        }
    }
    
    // Берем из базы объявление с модерацией 0
    $Row = $dbh->query("SELECT text, phone, username FROM base_baraholka WHERE chat_id = $chat_id AND moder = 0 AND id=$IdPhoto");
    $phone = $Row[0]['phone'];
    $text = $Row[0]['text'];
    $nick = $Row[0]['username'] ? '@' . $Row[0]['username'] : '';
    $RowIdBase = $dbh->query("SELECT photo_id FROM base_photo_baraholka WHERE id_base=$IdPhoto");
	
	// поиск в тексте @username
	if (preg_match_all('/@(\w+)/', $text, $matches)) {
		  // $matches[0] - содержит все совпадения
		  // $matches[1] - содержит все захваченные группы (в нашем случае - имена после @)
		  foreach ($matches[1] as $username) {
			// echo "Найден никнейм: " . $username . PHP_EOL;
			$nick = '';
		  }
		} else {
		  // echo "Никнеймы не найдены";
		}
    if ($RowIdBase) {
		 $media = '';
        foreach ($RowIdBase as $key) {
            // $media .= '{"type":"photo","media":"' . $key['photo_id'] . '"}, ';
			$photo_file = [
						'type' => 'photo',
						'media' => $key['photo_id'],
						'caption' => '',
						'parse_mode' => 'HTML'
					];
			 
			 $media_arr[] = $photo_file;
        }
		
		$cnt = count($media_arr);
		$media_arr[$cnt - 1]['caption'] = $text.' '.$nick;
        // $len = mb_strlen($media);
        // $media = mb_substr($media, 0, $len - 3) . ',"caption":"' . $text . ' ' . $nick . '","parse_mode":"HTML"}';

        // $request_params = [
            // 'chat_id' => $chatAdmin,
            // 'media' => '[' . $media . ']'
        // ];
		$request_params = [
							'chat_id' => $chatAdmin,
							'media' => json_encode($media_arr),
							'parse_mode' => 'HTML'
							];	 
        sendMedia($token, $request_params);
    }
	
		

    // Используем уже проверенный статус оплаты
    $is_paid = $is_current_paid;
    
    $reply = "Объявление отправлено на модерацию и скоро будет опубликовано. \nЗапустите бота вновь командой\n /start";
    $reply_markup = json_encode(['remove_keyboard' => true]);
    
    // Уведомление админу
    $admin_message = 'Объявление от пользователя ' . $nick . ' чат ID ' . $chat_id;
    if ($is_paid) {
        $admin_message = '💰 <b>ОПЛАЧЕННОЕ ОБЪЯВЛЕНИЕ</b> 💰' . "\n" . $admin_message;
    }
    
    $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => $admin_message, 'parse_mode' => 'HTML']);
    if (!$RowIdBase) {
        $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => $text, 'parse_mode' => 'HTML']);
    }
    $res_id = $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => "Опубликовать\n/post" . $IdPhoto."\n" ]);
	// $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => "JSON" .$res_id['message_id']."\n" ]);
	$mes_id = $res_id['message_id']*1;
	$dbh->query("INSERT INTO usselbot_message  VALUES (NULL, $IdPhoto, $mes_id, 'post')");
	
    $res_id = $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => "Добавить в очередь\n/addlist" . $IdPhoto."\n" ]);
	$mes_id = $res_id['message_id']*1;
	$dbh->query("INSERT INTO usselbot_message  VALUES (NULL, $IdPhoto, $mes_id, 'addlist')");
	
    $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => "Редактировать\n/edit" . $IdPhoto."\n" ]);
    $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => "Ответить\n/reply" . $IdPhoto."\n"]);
    $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => "Удалить\n/delete" . $IdPhoto."\n"]);
	// Регулярное выражение для поиска URL
		$pattern = '/https?:\/\/.*/i';

		if (preg_match($pattern, $text, $matches)) {
		$res_id = $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => "Ссылка pay\n/linkpay" . $IdPhoto."\n"]);	
		$mes_id = $res_id['message_id']*1;
		$dbh->query("INSERT INTO usselbot_message  VALUES (NULL, $IdPhoto, $mes_id, 'linkpay')");
		}

    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup]);
    $dbh->query("UPDATE base_baraholka SET moder=1 WHERE chat_id=$chat_id");
    $comand = '';
    $dbh->query("UPDATE bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chat_id");
    
    return true;
}

// Функция публикации только текста объявления
function publishTextOnly($dbh, $telegram, $chat_id, $IdPhoto, $chatAdmin)
{
    $Row = $dbh->query("SELECT text, phone, username FROM base_baraholka WHERE chat_id = $chat_id AND moder = 0 AND id=$IdPhoto");
    $phone = $Row[0]['phone'];
    $text = strip_tags($Row[0]['text']);
    $nick = $Row[0]['username'] ? '@' . $Row[0]['username'] : '';

    $reply = "Объявление отправлено на модерацию и скоро будет опубликовано. \nЗапустите бота вновь командойn /start";
    $reply_markup = json_encode(['remove_keyboard' => true]);
    $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => 'Объявление от пользователя ' . $nick . ' чат ID ' . $chat_id]);
    $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => $text, 'parse_mode' => 'HTML']);
    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup]);
    $dbh->query("UPDATE base_baraholka SET moder=1 WHERE chat_id=$chat_id");
    $comand = '';
    $dbh->query("UPDATE bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chat_id");
}


function formatMessage(?string $message, array $entities): ?string {
    if ($message === null) {
        return null;
    }

    // Шаблоны для форматирования сущностей
    $html = [
        'bold' => '<strong>%s</strong>',
        'italic' => '<i>%s</i>',
        'code' => '<code>%s</code>',
        'pre' => '<pre>%s</pre>',
        'strike' => '<strike>%s</strike>',
        'underline' => '<u>%s</u>',
        'blockquote' => '<blockquote>%s</blockquote>',
        'text_link' => '<a href="%s" target="_blank" rel="nofollow">%s</a>',
        'mention' => '<a href="tg://resolve?domain=%s" rel="nofollow">%s</a>',
        'url' => '<a href="%s" target="_blank" rel="nofollow">%s</a>',
    ];

    // Конвертируем текст в UTF-16LE для работы с позициями
    $messageUtf16 = mb_convert_encoding($message, 'UTF-16LE', 'UTF-8');

    // Сортируем сущности по offset (начинаем с начала текста)
    usort($entities, function ($a, $b) {
        return $a['offset'] <=> $b['offset'];
    });

    foreach ($entities as $key => &$entity) {
        if (!isset($html[$entity['type']])) {
            continue; // Пропускаем неизвестные типы сущностей
        }

        // Вычисляем позицию и длину в байтах (UTF-16 использует 2 байта на символ)
        $start = $entity['offset'] * 2;
        $length = $entity['length'] * 2;

        // Извлекаем подстроку в UTF-16
        $text = substr($messageUtf16, $start, $length);
        $textUtf8 = mb_convert_encoding($text, 'UTF-8', 'UTF-16LE');

        // Форматируем текст в зависимости от типа сущности
        switch ($entity['type']) {
            case 'text_link':
                $url = $entity['url'] ?? $textUtf8;
                $htmlText = sprintf($html[$entity['type']], htmlspecialchars($url, ENT_QUOTES), $textUtf8);
                break;
            case 'mention':
                $mention = ltrim($textUtf8, '@');
                $htmlText = sprintf($html[$entity['type']], htmlspecialchars($mention, ENT_QUOTES), $textUtf8);
                break;
            case 'url':
                $url = $textUtf8;
                $htmlText = sprintf($html[$entity['type']], htmlspecialchars($url, ENT_QUOTES), $textUtf8);
                break;
            default:
                $htmlText = sprintf($html[$entity['type']], $textUtf8);
                break;
        }

        // Заменяем часть строки в UTF-16
        $htmlTextUtf16 = mb_convert_encoding($htmlText, 'UTF-16LE', 'UTF-8');
        $messageUtf16 = substr_replace($messageUtf16, $htmlTextUtf16, $start, $length);

        // Рассчитываем изменение длины
        $originalLength = $length; // Исходная длина в байтах
        $newLength = strlen($htmlTextUtf16); // Новая длина в байтах

        // Корректируем offset всех последующих сущностей
        foreach ($entities as $nextKey => &$nextEntity) {
            if ($nextKey <= $key) {
                continue; // Пропускаем уже обработанные сущности
            }
            if ($nextEntity['offset'] < ($entity['offset'] + ($entity['length'] / 2))) {
                // Если сущность частично пересекается, увеличиваем её offset на длину открывающего тега
                $openTag = preg_replace('~(\>).*<\/.*$~', '$1', $htmlText);
                $openTagLength = mb_strlen(mb_convert_encoding($openTag, 'UTF-16LE', 'UTF-8')) / 2;
                $nextEntity['offset'] += $openTagLength;
            } else {
                // Если сущность находится после текущей, увеличиваем её offset на разницу длин
                $difference = ($newLength - $originalLength) / 2;
                $nextEntity['offset'] += $difference;
            }
        }
        unset($nextEntity);
    }
    unset($entity);

    // Конвертируем обратно в UTF-8
    $result = mb_convert_encoding($messageUtf16, 'UTF-8', 'UTF-16LE');
    return $result;
}

// Функция обработки оплаты объявления
function processPayment($dbh, $telegram, $chat_id, $ad_id) {
    // Проверяем, не оплачено ли уже объявление
    try {
        $existing = $dbh->query("SELECT * FROM paid_ads WHERE ad_id = $ad_id");
        if (!empty($existing)) {
            return false; // Уже оплачено
        }
        
        // Добавляем запись об оплаченном объявлении
        $dbh->query("INSERT INTO paid_ads (ad_id, chat_id, created_at) VALUES ($ad_id, $chat_id, NOW())");
    } catch (Exception $e) {
        return false;
    }
    
    // Отправляем сообщение пользователю
    $reply = "✅ <b>Спасибо за оплату!</b>\n\n";
    $reply .= "Ваше объявление будет опубликовано вне очереди.\n";
    $reply .= "Администратор получил уведомление о том, что объявление оплачено.";
    
    $telegram->sendMessage([
        'chat_id' => $chat_id,
        'text' => $reply,
        'parse_mode' => 'HTML'
    ]);
    
    return true;
}

// Функция создания SQL для создания таблиц
function createTables($dbh) {
    // Создаем таблицу для оплаченных объявлений
    $sql1 = "CREATE TABLE IF NOT EXISTS paid_ads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ad_id INT NOT NULL,
        chat_id BIGINT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ad_id (ad_id),
        INDEX idx_chat_id (chat_id),
        INDEX idx_created_at (created_at)
    )";
    
    try {
        $dbh->query($sql1);
        return true;
    } catch (Exception $e) {
        return false;
    }
}


//============== РЕФАКТОРИНГ: ОБРАБОТЧИКИ КОМАНД ==============

function handleStartCommand($telegram, $chat_id) {
    $reply_markup = \Telegram\Bot\Keyboard\Keyboard::make()
        ->setResizeKeyboard(true)
        ->setOneTimeKeyboard(false)
        ->row([
            \Telegram\Bot\Keyboard\Keyboard::button('Предложить объявление'),
        ]);

    $telegram->sendMessage([
        'chat_id' => $chat_id,
        'text' => "Добро пожаловать в бота!\nЗдесь вы можете подать объявление на\nканал @uss_baraholka\n1 шаг: нажмите кнопку 'Предложить объявление'",
        'reply_markup' => $reply_markup
    ]);
}

function handleOfferAdCommand($telegram, $chat_id, $dbh) {
    $reply = "2 шаг: Набирите на клавиатуре <b> ТОЛЬКО ТЕКСТ</b> объявления. Отправьте боту в ленту боковой стрелкой справа.\n<b>ФОТО можно будет добавить позже, через кнопку Добавить фото.</b>\nПример:\nПродается кровать деревянная \nЦена 1000 руб \nТел 89991234567";
    $reply_markup = \Telegram\Bot\Keyboard\Keyboard::remove(['selective' => false]);

    $telegram->sendMessage([
        'chat_id' => $chat_id,
        'parse_mode' => 'HTML',
        'text' => $reply,
        'reply_markup' => $reply_markup
    ]);

    $comand = '/adds';
    $Row = $dbh->query("SELECT comand FROM bufer_baraholka_bot WHERE chat_id = $chat_id");
    if (!$Row) {
        $dbh->query("INSERT INTO bufer_baraholka_bot VALUES (NULL, $chat_id, '$comand', TRUE)");
    } else {
        $dbh->query("UPDATE bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chat_id");
    }
}

function handleNewAdText($dbh, $telegram, $chat_id, $text, $entities, $photo_caption_entities, $name) {
    if ($entities) {
        $text = formatMessage($text, $entities);
    }
    if ($photo_caption_entities) {
        $text = formatMessage($text, $photo_caption_entities);
    }

    $text = cat_phone($text) . chr(10);
    $len = mb_strlen($text);

    $Row = $dbh->query("SELECT moder, post FROM base_baraholka WHERE chat_id = $chat_id AND moder = 0 AND post = 0");
    if (!$Row) {
        $phone = (get_phone($text)) ?: '';
        $dbh->query("INSERT INTO base_baraholka (chat_id, username, text, phone) VALUES ($chat_id, '$name', '$text', '$phone')");
    } else {
		$dbh->query("UPDATE base_baraholka SET text = '$text' WHERE chat_id = $chat_id AND moder = 0 AND post = 0");
	}


    if ($len > 970) {
        $reply = "Текст вашего объявления слишком большой. Сократите текст с помощью ИИ или начните снова.";
        $reply_markup = \Telegram\Bot\Keyboard\Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(false)
            ->row([\Telegram\Bot\Keyboard\Keyboard::inlineButton(['text' => 'Сократить с ИИ'])])
            ->row([\Telegram\Bot\Keyboard\Keyboard::inlineButton(['text' => 'Удалить объявление'])]);

        $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup]);
    } else {
        $comand = '/stepone';
        $dbh->query("UPDATE bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chat_id");
        
        $reply = "3 шаг: нажмите кнопку ДОБАВИТЬ ФОТО или КНОПКУ С ДЕЙСТВИЕМ!";
        $reply_markup = \Telegram\Bot\Keyboard\Keyboard::make()
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(false)
            ->row([
                \Telegram\Bot\Keyboard\Keyboard::inlineButton(['text' => 'Опубликовать']),
                \Telegram\Bot\Keyboard\Keyboard::inlineButton(['text' => 'Посмотреть'])
            ])
            ->row([
                \Telegram\Bot\Keyboard\Keyboard::inlineButton(['text' => 'Удалить объявление']),
                \Telegram\Bot\Keyboard\Keyboard::inlineButton(['text' => 'Добавить фото'])
            ]);

        $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup]);
    }
}

function handleDeleteAdCommand($dbh, $telegram, $chat_id, $IdPhoto) {
    if ($IdPhoto) {
        $dbh->query("DELETE FROM base_photo_baraholka WHERE id_base=$IdPhoto");
        $dbh->query("DELETE FROM base_baraholka WHERE id=$IdPhoto");
        $dbh->query("UPDATE bufer_baraholka_bot SET comand='' WHERE chat_id=$chat_id");
    }
    $reply = "Объявление удалено. Запустите бота вновь командой\n/start";
    $reply_markup = json_encode(['remove_keyboard' => true]);
    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup]);
}

function handleViewAdCommand($dbh, $telegram, $token, $chat_id, $IdPhoto) {
    $Row = $dbh->query("SELECT text, phone, username FROM base_baraholka WHERE chat_id = $chat_id AND moder = 0 AND id=$IdPhoto");
    $text = $Row[0]['text'];
    $nick = $Row[0]['username'] ? '@' . $Row[0]['username'] : '';
    if (str_contains($text, '@')) {
        $nick = '';
    }

    $RowIdBase = $dbh->query("SELECT photo_id FROM base_photo_baraholka WHERE id_base=$IdPhoto");

    if ($RowIdBase) {
        $media_arr = [];
        foreach ($RowIdBase as $key) {
            $media_arr[] = ['type' => 'photo', 'media' => $key['photo_id'], 'caption' => '', 'parse_mode' => 'HTML'];
        }
        $cnt = count($media_arr);
        if ($cnt > 0) {
            $media_arr[$cnt - 1]['caption'] = $text . ' ' . $nick;
        }
        
        $request_params = ['chat_id' => $chat_id, 'media' => json_encode($media_arr), 'parse_mode' => 'HTML'];
        sendMedia($token, $request_params);
    } else {
        $telegram->sendMessage(['chat_id' => $chat_id, 'text' => $text . ' ' . $nick, 'parse_mode' => 'HTML']);
    }

    $telegram->sendMessage(['chat_id' => $chat_id, 'text' => '6 шаг: нажмите кнопку ОПУБЛИКОВАТЬ для отправки объявления на модерацию']);
}


?>