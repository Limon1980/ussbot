<?php

include('../vendor/autoload.php');
include ('classes/db.php');
include ('func.lib.php');

 use Telegram\Bot\Api; 
	//use Telegram\Bot\Keyboard;
    //use Telegram\Bot\Actions;
    use Telegram\Bot\Commands\Command;
    use Telegram\Bot\Objects\CallbackQuery;
	//use Telegram;
	//use Telegram\Bot\Commands\Command;
	use Telegram\Bot\Keyboard\Keyboard;

	

	$token = 'token';
    $telegram = new Api($token); //Устанавливаем токен, полученный у BotFather
	$dbh = new Db();

	
	
    $result = $telegram -> getWebhookUpdates(); //Передаем в переменную $result полную информацию о сообщении пользователя
    $text = $result["message"]["text"]; //Текст сообщения
    $chat_id = $result["message"]["chat"]["id"]; //Уникальный идентификатор пользователя
    $name = ($result["message"]["from"]["username"])?: ''; //Юзернейм пользователя
	$fname = ($result["message"]["from"]["first_name"])?: '';
	$lname = ($result["message"]["from"]["last_name"])?: '';
	$photo = $result['message']['photo'][2]['file_id']?:$result['message']['document']['file_id'];
	$video = $result['message']['video']['file_id'];
	$reply_message = $result["message"]["reply_to_message"]["text"];
	$entities = $result['message']['entities'];
	$photo_caption_entities = $result['message']['caption_entities'];
	$phone = (get_phone($text))?: '';
	if ($chat_id){
	$RowComand = $dbh->query("SELECT  comand FROM bufer_baraholka_bot WHERE chat_id = $chat_id");
	$RowID = $dbh->query("SELECT  id FROM base_baraholka WHERE chat_id = $chat_id AND moder = 0 AND post = 0");	
	$IdPhoto = $RowID[0]['id'];
	$comand = preg_replace('/[0-9]/','', $RowComand[0]['comand']);
	$comand_photo = $RowComand[0]['comand'];
	}
	$chatAdmin = '337959904'; // Устанавливае ID чата с ботом админа
	$comandAdmin = FormChars(preg_replace('/[0-9]/','', $text));
	$comandAdminId = preg_replace('/[a-z]|\//','', $text);
	
	
	
	if($text || $photo){
		
         if ($text == '/start' and $comand != '/stepone') {
			
				   $reply_markup = Keyboard::make()
							  ->setResizeKeyboard(true)
							  ->setOneTimeKeyboard(false)
							  ->row([
								Keyboard::button('Предложить объявление'),
							  ]);

					$telegram->sendMessage([
					  'chat_id' => $chat_id,
					  'text' => "Добро пожаловать в бота!\nЗдесь вы можете подать объявление на\nканал @uss_baraholka\n1 шаг: нажмите кнопку 'Предложить объявление'",
					  'reply_markup' => $reply_markup
					]);
         }elseif ($text == 'Предложить объявление')
		 {
			 $reply = "2 шаг: Набирите на клавиатуре <b> ТОЛЬКО ТЕКСТ</b> объявления. Отправьте боту в ленту боковой стрелкой справа.\n<b>ФОТО можно будет добавить позже, через кнопку Добавить фото.</b>\nПример:\nПродается кровать деревянная \nЦена 1000 руб \nТел 89991234567";
			 $reply_markup = Keyboard::remove(['selective' => false]);

			$telegram->sendMessage([
				'chat_id' => $chat_id,
				'parse_mode' => 'HTML',
				'text' => $reply,
				'reply_markup' => $reply_markup
			]);
			 
			 $comand = '/adds';
			 $Row = $dbh->query("SELECT  comand FROM bufer_baraholka_bot WHERE chat_id = $chat_id");
			 if (!$Row){
			 $dbh->query("INSERT INTO  bufer_baraholka_bot VALUES (NULL, $chat_id, '$comand', TRUE)");
			 }else{$dbh->query("UPDATE  bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chat_id");}
		 }elseif ($comandAdmin == '/broadcast' && $chat_id == $chatAdmin) {
			$telegram->sendMessage([
				'chat_id' => $chatAdmin,
				'text' => 'Введите текст для массовой рассылки всем пользователям бота.'
			]);
			$dbh->query("UPDATE bufer_baraholka_bot SET comand='/broadcast' WHERE chat_id=$chatAdmin");
		} elseif ($comand == '/broadcast' && $chat_id == $chatAdmin && $text) {
			// Сохраняем текст рассылки и запрашиваем фото
			
			
			// добавляем в тект форматирование, если оно есть.
			 if ($entities){
							$text = formatMessage($text, $entities);
							}
			$message = $text;
			$dbh->query("INSERT INTO broadcast_log VALUES (NULL, NOW(), NOW(), '$message', '', 0, 0, '')");
			$broadcast_id = $dbh->lastInsertId();

			$telegram->sendMessage([
				'chat_id' => $chatAdmin,
				'text' => "Текст сохранен (ID: $broadcast_id). Теперь отправьте фото для рассылки или напишите /skip, чтобы продолжить без фото."
			]);
			$dbh->query("UPDATE bufer_baraholka_bot SET comand='/broadcast_photo_$broadcast_id' WHERE chat_id=$chatAdmin");
		} elseif (preg_match('/^\/broadcast_photo_(\d+)$/', $comand_photo, $matches) && $chat_id == $chatAdmin) {
			$broadcast_id = $matches[1] * 1;
			if ($photo) {
				// Сохраняем file_id фото в broadcast_log
				$dbh->query("UPDATE broadcast_log SET img = '$photo' WHERE id = $broadcast_id");

				// Получаем текст рассылки из broadcast_log
				$row = $dbh->query("SELECT message FROM broadcast_log WHERE id = $broadcast_id");
				$message = $row[0]['message'] ?? '';

				// Отправляем фото с текстом в качестве подписи администратору
				$telegram->sendPhoto([
					'chat_id' => $chatAdmin,
					'photo' => $photo,
					'caption' => $message,
					'parse_mode' => 'HTML'
				]);

				// Предлагаем запустить или отменить рассылку
				$telegram->sendMessage([
					'chat_id' => $chatAdmin,
					'text' => "Рассылка готова. Для запуска напишите /startbroadcast".$broadcast_id." или /cancelbroadcast для отмены."
				]);

				// Устанавливаем команду в состояние "готово к запуску"
				$dbh->query("UPDATE bufer_baraholka_bot SET comand='/broadcast_ready_$broadcast_id' WHERE chat_id=$chatAdmin");
			} elseif ($text == '/skip') {
				// Получаем текст рассылки из broadcast_log
				$row = $dbh->query("SELECT message FROM broadcast_log WHERE id = $broadcast_id");
				$message = $row[0]['message'] ?? '';

				// Отправляем только текст администратору
				$telegram->sendMessage([
					'chat_id' => $chatAdmin,
					'text' => $message,
					'parse_mode' => 'HTML'
				]);

				// Предлагаем запустить или отменить рассылку
				$telegram->sendMessage([
					'chat_id' => $chatAdmin,
					'text' => "Рассылка готова. Для запуска напишите /startbroadcast".$broadcast_id." или /cancelbroadcast для отмены."
				]);

				// Устанавливаем команду в состояние "готово к запуску"
				$dbh->query("UPDATE bufer_baraholka_bot SET comand='/broadcast_ready_$broadcast_id' WHERE chat_id=$chatAdmin");
			} else {
				$telegram->sendMessage([
					'chat_id' => $chatAdmin,
					'text' => "Пожалуйста, отправьте фото или напишите /skip для запуска рассылки без фото."
				]);
			}
		}elseif (preg_match('/^\/broadcast_ready_(\d+)$/', $comand_photo, $matches) && $chat_id == $chatAdmin) 
		{
			$broadcast_id = $matches[1] * 1;
			if ($text == "/startbroadcast".$broadcast_id) {
				$telegram->sendMessage([
					'chat_id' => $chatAdmin,
					'text' => "Рассылка (ID: $broadcast_id) запускается."
				]);
				// $shell_command = "php /var/www/html/usssellbot/broadcast.php $broadcast_id > /dev/null 2>&1 &";
				$shell_command = "php /var/www/html/usssellbot/broadcast.php $broadcast_id > /var/www/html/usssellbot/broadcast_log.txt 2>&1 &";
				exec($shell_command);
				$dbh->query("UPDATE bufer_baraholka_bot SET comand='' WHERE chat_id=$chatAdmin");
			} elseif ($text == '/cancelbroadcast') {
				$telegram->sendMessage([
					'chat_id' => $chatAdmin,
					'text' => "Рассылка (ID: $broadcast_id) отменена."
				]);
				$dbh->query("UPDATE bufer_baraholka_bot SET comand='' WHERE chat_id=$chatAdmin");
			} else {
				$telegram->sendMessage([
					'chat_id' => $chatAdmin,
					'text' => "Пожалуйста, напишите /startbroadcast $broadcast_id для запуска или /cancelbroadcast для отмены."
				]);
			}
		}elseif($comand == '/adds' and $text and $text != '/stop' && $text != 'Сократить с ИИ' && $text != 'Удалить объявление')
		 {
			 
			 // добавляем в тект форматирование, если оно есть.
			 if ($entities){
							$text = formatMessage($text, $entities);
							// $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => json_encode($result)]);
							// $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => $text]);
							}
							
			 if ($photo_caption_entities){
							$text = formatMessage($text, $photo_caption_entities);
							// $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => json_encode($result)]);
							// $telegram->sendMessage(['chat_id' => $chatAdmin, 'text' => $text]);
							}
							
			 $text = cat_phone($text).chr(10);
			 // проверяем текст объявления на длину сообщения
			 $len = mb_strlen($text);
			 if ($len > 970){
				 
			$Row = $dbh->query("SELECT  moder, post FROM base_baraholka WHERE chat_id = $chat_id AND moder = 0 AND post = 0");
			 if (!$Row){
			 $dbh->query("INSERT INTO base_baraholka  VALUES (NULL, $chat_id, '$name', '$fname', '$lname', '$phone', '$text', 0, 0, NOW())");
			 }
			
				$reply = "Текст вашего объявления слишком большой. Сократите текст с помощью ИИ или начните снова.";

				$reply_markup = Keyboard::make()
				  ->setResizeKeyboard(true)
				  ->setOneTimeKeyboard(false)
				  ->row([
					Keyboard::inlineButton(['text' => 'Сократить с ИИ']),
				  ])
				  ->row([
					Keyboard::inlineButton(['text' => 'Удалить объявление']),
				  ]);

				$telegram->sendMessage([
				  'chat_id' => $chat_id,
				  'text' => $reply,
				  'reply_markup' => $reply_markup
				]);
				
			
			}else{
			 $Row = $dbh->query("SELECT  moder, post FROM base_baraholka WHERE chat_id = $chat_id AND moder = 0 AND post = 0");
			 if (!$Row){
			 $dbh->query("INSERT INTO base_baraholka  VALUES (NULL, $chat_id, '$name', '$fname', '$lname', '$phone', '$text', 0, 0, NOW())");
			 }
			 $comand = '/stepone';
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chat_id");
			 $reply = "3 шаг: нажмите кнопку ДОБАВИТЬ ФОТО или КНОПКУ С ДЕЙСТВИЕМ!";

				$reply_markup = Keyboard::make()
				  ->setResizeKeyboard(true)
				  ->setOneTimeKeyboard(false)
				  ->row([
					Keyboard::inlineButton(['text' => 'Опубликовать']),
					Keyboard::inlineButton(['text' => 'Посмотреть'])
				  ])
				  ->row([
					Keyboard::inlineButton(['text' => 'Удалить объявление']),
					Keyboard::inlineButton(['text' => 'Добавить фото'])
				  ]);

				$telegram->sendMessage([
				  'chat_id' => $chat_id,
				  'text' => $reply,
				  'reply_markup' => $reply_markup
				]);
			}
		 }elseif($comand == '/adds'  && $text == 'Сократить с ИИ' && $text != '/stop')
		 {
			 
			 include 'gpt_check.php';
			 
			 $reply = "3 шаг: нажмите кнопку ДОБАВИТЬ ФОТО или КНОПКУ С ДЕЙСТВИЕМ!";

				$reply_markup = Keyboard::make()
				  ->setResizeKeyboard(true)
				  ->setOneTimeKeyboard(false)
				  ->row([
					Keyboard::inlineButton(['text' => 'Опубликовать']),
					Keyboard::inlineButton(['text' => 'Посмотреть'])
				  ])
				  ->row([
					Keyboard::inlineButton(['text' => 'Удалить объявление']),
					Keyboard::inlineButton(['text' => 'Добавить фото'])
				  ]);

				$telegram->sendMessage([
				  'chat_id' => $chat_id,
				  'text' => $reply,
				  'reply_markup' => $reply_markup
				]);
				
				$telegram->sendMessage([
				  'chat_id' => $chatAdmin,
				  'text' => "Использовал ИИ \n".$text."\nПользователь: \n ".$chat_id." @".$name,
				  'parse_mode' => 'HTML'
				]);
			
		 }elseif($comand == '/adds'  && $text != '/stop' && $photo)
		 {
			 $telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => 'Нужно сначала отправить ТОЛЬКО текст!' ]);
			 
		 }elseif($comand == '/stepone' and $text == 'Добавить фото')
		 {   $comand = '/addphoto';
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chat_id");
			 $reply = "Прикрепите одну или несколько фото используя 'скрепку'";

				$reply_markup = Keyboard::make()
				  ->setResizeKeyboard(true)
				  ->setOneTimeKeyboard(false)
				  ->row([
					Keyboard::inlineButton(['text' => 'Опубликовать']),
					Keyboard::inlineButton(['text' => 'Посмотреть'])
				  ])
				  ->row([
					Keyboard::inlineButton(['text' => 'Удалить объявление'])
				  ]);

				$telegram->sendMessage([
				  'chat_id' => $chat_id,
				  'text' => $reply,
				  'reply_markup' => $reply_markup
				]);

			 
		 }elseif($comand == '/addphoto' AND $photo)
		 {   
				// Считаем сколько фото загружено в базу
			$CountPhoto = count($dbh->query("SELECT  * FROM base_photo_baraholka WHERE id_base=$IdPhoto"))+1;
			// Условие, чтобы загружать в базу до 10 фотографий
			 if($CountPhoto < 11){
			 $dbh->query("INSERT INTO   base_photo_baraholka VALUES (NULL, $IdPhoto, '$photo', '')");
	         $telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => 'Фото добавлено. Загружено '.$CountPhoto. " фото.\nДобавьте еще фото через скрепку или\n5 шаг: нажмите кнопку ПОСМОТРЕТЬ - чтобы посмотреть как выглядит ваше объявление."]);}
			 else{$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => "Уже загружено максимальное колличество фото!\n5 шаг: нажмите кнопку ПОСМОТРЕТЬ - чтобы посмотреть как выглядит ваше объявление."]);}
		 }elseif ($text == 'Удалить объявление' || $text == '/stop' || $text == 'Начать с начала')
		 {
			 if($IdPhoto){
			 $dbh->query("DELETE FROM base_photo_baraholka WHERE id_base=$IdPhoto");
			 $dbh->query("DELETE FROM base_baraholka WHERE id=$IdPhoto");
			 $comand = '';
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chat_id");
			 }
			 $reply = "Объявление удалено. Запустите бота вновь командой\n/start";
			 $reply_markup = json_encode(['remove_keyboard' => true]);
			 $telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply, 'reply_markup' => $reply_markup]);
		 }elseif (($comand == '/stepone' || $comand == '/addphoto') and $text == 'Посмотреть')
		 {
			 // Берем из базы объявление с модерацией 0
			 $Row = $dbh->query("SELECT  text, phone, username FROM base_baraholka WHERE chat_id = $chat_id AND moder = 0 AND id=$IdPhoto");
			 $phone = $Row[0]['phone'];
			 $text = $Row[0]['text'];
			 $nick = $Row[0]['username']? '@'.$Row[0]['username']: '';
			 if (str_contains($text, '@')){$nick = '';}
			 $RowIdBase = $dbh->query("SELECT  photo_id FROM base_photo_baraholka WHERE id_base=$IdPhoto");
			 // Выводим все фото, до 10 штук, методом sendMedia и к последней фото добовляем caption
			 if ($RowIdBase){
			 foreach ($RowIdBase as $key){
			 // $media .= '{"type":"photo","media":"'.$key['photo_id'].'"}, ';
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
			 // $media = mb_substr($media, 0, $len-3).',"caption":"'.json_encode($text) . ' ' . $nick.'","parse_mode":"HTML"}';

			 $request_params = [
							'chat_id' => $chat_id,
							'media' => json_encode($media_arr),
							'parse_mode' => 'HTML'
							];	 
			 sendMedia($token, $request_params);
		 }
			if (!$RowIdBase){
				$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $text .' '. $nick , 'parse_mode' => 'HTML']);
			}
			$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => '6 шаг: нажмите кнопку ОПУБЛИКОВАТЬ для отправки объявления на модерацию']);
			
			
		 }elseif (($comand == '/stepone' || $comand == '/addphoto') and $text == 'Опубликовать')
		 {
        $RowIdBase = $dbh->query("SELECT  photo_id FROM base_photo_baraholka WHERE id_base=$IdPhoto");
		$RowPhoneNick = $dbh->query("SELECT  phone, username FROM base_baraholka WHERE id = $IdPhoto");
		$phone = $RowPhoneNick[0]['phone'];
		$nick = $RowPhoneNick[0]['username']? '@'.$Row[0]['username']: '';
        if (count($RowIdBase) == 0) {
            // Нет загруженных фото, запрашиваем действие
            $reply = "Вы не добавили <b>ни одного фото</b>. Хотите <b>добавить фото</b> или опубликовать <b>только текст?</b>";

				$reply_markup = Keyboard::make()
				  ->setResizeKeyboard(true)
				  ->setOneTimeKeyboard(false)
				  ->row([
					Keyboard::inlineButton(['text' => 'Только текст']),
					Keyboard::inlineButton(['text' => 'Добавить фото'])
				  ])
				  ->row([
					Keyboard::inlineButton(['text' => 'Удалить объявление']),
					Keyboard::inlineButton(['text' => 'Посмотреть'])
				  ]);

				$telegram->sendMessage([
				  'chat_id' => $chat_id,
				  'text' => $reply,
				  'parse_mode' => 'HTML',
				  'reply_markup' => $reply_markup
				]);
			
        }elseif(!$nick && !$phone){
			$reply = "Вы не добавили <b>номер телефона или @username</b>. Для публикации объявления, добавьте контакт в текст объявления.";

				$reply_markup = Keyboard::make()
				  ->setResizeKeyboard(true)
				  ->setOneTimeKeyboard(false)
				  ->row([
					Keyboard::inlineButton(['text' => 'Начать с начала']),
				  ])
				  ->row([
					Keyboard::inlineButton(['text' => 'Удалить объявление']),
					Keyboard::inlineButton(['text' => 'Посмотреть'])
				  ]);

				$telegram->sendMessage([
				  'chat_id' => $chat_id,
				  'text' => $reply,
				  'parse_mode' => 'HTML',
				  'reply_markup' => $reply_markup
				]);
				
		}
			else {
            // Продолжаем с публикацией как раньше
            publishAd($dbh, $telegram, $token, $chat_id, $IdPhoto, $chatAdmin);
        }
    }
		elseif (($comand == '/stepone' || $comand == '/addphoto') and $text == 'Только текст'){
			
			$RowPhoneNick = $dbh->query("SELECT  phone, username FROM base_baraholka WHERE id = $IdPhoto");
			$phone = $RowPhoneNick[0]['phone'];
			$nick = $RowPhoneNick[0]['username']? '@'.$Row[0]['username']: '';
			if(!$nick && !$phone){
			$reply = "Вы не добавили <b>номер телефона или @username</b>. Для публикации объявления, добавьте контакт в текст объявления.";

				$reply_markup = Keyboard::make()
				  ->setResizeKeyboard(true)
				  ->setOneTimeKeyboard(false)
				  ->row([
					Keyboard::inlineButton(['text' => 'Начать с начала']),
				  ])
				  ->row([
					Keyboard::inlineButton(['text' => 'Удалить объявление']),
					Keyboard::inlineButton(['text' => 'Посмотреть'])
				  ]);

				$telegram->sendMessage([
				  'chat_id' => $chat_id,
				  'text' => $reply,
				  'parse_mode' => 'HTML',
				  'reply_markup' => $reply_markup
				]);
				
		}else{
			publishAd($dbh, $telegram, $token, $chat_id, $IdPhoto, $chatAdmin);
			}
			
		}
   	 
		 elseif($comandAdmin == '/post' && $chat_id == $chatAdmin && $comandAdminId )
		 
		 
		 {
			$Row = $dbh->query("SELECT  text, phone, username, chat_id FROM base_baraholka WHERE id = $comandAdminId AND moder = 1");
			$phone = $Row[0]['phone'];
			$chat_id = $Row[0]['chat_id'];
			 $text = $Row[0]['text'];
			 $nick = $Row[0]['username']? '@'.$Row[0]['username']: '';
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
			 $RowIdBase = $dbh->query("SELECT  photo_id FROM base_photo_baraholka WHERE id_base=$comandAdminId");
			 // Выводим все фото на канал Барахолки, до 10 штук, методом sendMedia и к последней фото добовляем caption
			 if ($RowIdBase){
				$file_ids = [];
			 foreach ($RowIdBase as $key){
			 // $media .= '{"type":"photo","media":"'.$key['photo_id'].'"}, ';
			 // массив file_id для загрузки в инстаграм
			 // $file_id[] = $key['photo_id'];
			 $photo_file = [
						'type' => 'photo',
						'media' => $key['photo_id'],
						'caption' => '',
						'parse_mode' => 'HTML'
					];
			 $file_ids[] = $key['photo_id'];
			 $media_arr[] = $photo_file;
			 }
			 // $len = mb_strlen($media);
			 // $media = mb_substr($media, 0, $len-3).',"caption":"'.$phone.' '.$nick.'"}';
			 // $media = mb_substr($media, 0, $len-3).',"caption":"'.$text." ".$nick.'","parse_mode":"HTML"}';
			 $cnt = count($media_arr);
			 $media_arr[$cnt - 1]['caption'] = $text.' '.$nick;

			 $request_params = [
							'chat_id' => '@uss_baraholka',
							'media' => json_encode($media_arr),
							'disable_notification' => true,
							'parse_mode' => 'HTML'
							];	 
			 sendMedia($token, $request_params); 
			
			 
		 }else{
			 // отправляем текст на канал за фото
				
				$telegram->sendMessage([ 'chat_id' => '@uss_baraholka', 'text' => $text .' '. $nick, 'parse_mode' => 'HTML', 'disable_notification' => true  ]);
		 }
				
				
				// загружаем фото на сервер по file_id для последующей загрузки в Инстаграмм
					// массив путей до файлов фото для Инстаграм
				/* foreach ($file_id as $id){
				 $request_params = ['file_id' =>$id];	
				$file = sendTm($token, getFile, $request_params);
				$out = json_decode($file, TRUE);
				$img = $out['result']['file_path'];
				copy ('https://api.telegram.org/file/bot'.$token.'/'.$img, __DIR__.'/'.$img);
				sleep (1);
			
				$dir[] = __DIR__.'/'.$out['result']['file_path'];
				}		 */		
				
				// Выводим сообщение что объявление опубликовано в чат пользователя	
					if($chat_id != 601171965){
				$reply = "Объявление опубликовано";
				$telegram->sendMessage([ 'chat_id' => $chat_id, 'parse_mode' => 'HTML', 'text' => "<b>".$reply. "</b>\n\n"]);
				$telegram->sendMessage([ 'chat_id' => $chat_id, 'parse_mode' => 'HTML', 'text' => $text]);
				
				$reply = "Запустите бота вновь командой\n/start";
				$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply]);	
					}
				// Выводим в чат Админа что объявление опубликовано
				$dbh->query("UPDATE  base_baraholka SET post=1 WHERE id=$comandAdminId");
				$telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => 'Опубликовано объявление ID '.$comandAdminId .' от пользователя '. $chat_id ]);
					
								
				sleep(1);
				
				// if (!empty($file_ids)){
					// include 'instagram_post.php';
					// $caption = $text;
					// $instagram_result = postToInstagram($file_ids, $text, $nick);

					// if ($instagram_result['success']) {
						// $telegram->sendMessage([
							// 'chat_id' => $chatAdmin,
							// 'text' => 'Объявление успешно опубликовано в Instagram. Код ответа: ' . $instagram_result['http_code'],
							// 'disable_notification' => true
						// ]);
					// } else {
						// $telegram->sendMessage([
							// 'chat_id' => $chatAdmin,
							// 'text' => 'Ошибка при публикации в Instagram: ' . $instagram_result['error']
						// ]);
					// }
				// }
				// if($error){
				// $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => json_encode($dir)]);
				// $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => json_encode($error)]);
				// $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => json_encode($photo_arr)]);
				// }
								
				$dbh->query("UPDATE  bufer_baraholka_bot SET comand='' WHERE chat_id=$chatAdmin");//CONCAT('$comandAdmin', $comandAdminId)
				
				
				// редактируем сообщение в чате админа
					 $RowIdMes = $dbh->query("SELECT  message_id FROM usselbot_message WHERE post_id=$comandAdminId AND command='post'");
					 $mesId = $RowIdMes[0]['message_id'];
					 // $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => json_encode($RowIdMes).' '.$mesId ]);
					 
					 
					  $request_params = [
					'chat_id' => $chatAdmin,
					'message_id' => $mesId,
					'text' => "Опубликовано\n/post".$comandAdminId. " <b>OK</b>",
					'parse_mode' => 'HTML', 
				  ];

				  
				  $mes_edit = sendTm($token, 'editMessageText', $request_params);
				  
				  // $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => json_encode($mes_edit) ]);
				
		 }elseif($comandAdmin == '/edit' && $chat_id == $chatAdmin && $comandAdminId )
		 {
			 $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => 'Отправьте текст для корректировки поста '.$comandAdminId ]);
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand=CONCAT('/edit', $comandAdminId) WHERE chat_id=$chatAdmin");
			 
		 }elseif($comand == '/edit' && $chat_id == $chatAdmin && $text)
		 {
			 $comandId = preg_replace('/[a-z]|\//','', $RowComand[0]['comand']);
			 $dbh->query("UPDATE  base_baraholka SET text='$text' WHERE id=$comandId");
			$dbh->query("UPDATE  bufer_baraholka_bot SET comand='' WHERE chat_id=$chatAdmin");
			 $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => 'Пост отредактирован id '.$comandId . " \nТЕКСТ:\n".$text, 'parse_mode' => 'HTML' ]);
			 
			 
		 }		 
		 elseif($text == '/stopchat' && $chat_id == $chatAdmin){
			 $comandId = preg_replace('/[a-z]|\//','', $RowComand[0]['comand']);
			 $Row = $dbh->query("SELECT  username, chat_id FROM base_baraholka WHERE id = $comandId");
			 $chat_id = $Row[0]['chat_id'];
			 $nick = $Row[0]['username']? '@'.$Row[0]['username']: '';
			 $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => 'Разговор с пользователем остановлен '.$nick ]);
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand='' WHERE chat_id=$chat_id");
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand='' WHERE chat_id=$chatAdmin");
			 
		 }elseif($comandAdmin == '/delete' && $chat_id == $chatAdmin && $comandAdminId ){
			 $comandId = ($comandAdminId) ?:preg_replace('/[a-z]|\//','', $RowComand[0]['comand']);
			 $Row = $dbh->query("SELECT   username, chat_id, phone FROM base_baraholka WHERE id = $comandId");
			 $chat_id = $Row[0]['chat_id'];
			 $nick = $Row[0]['username']? '@'.$Row[0]['username']: '';
			 $phone = $Row[0]['phone'];
			 
			 $dbh->query("DELETE FROM base_photo_baraholka WHERE id_base=$comandId");
			 $dbh->query("DELETE FROM base_baraholka WHERE id=$comandId");
			 $dbh->query("DELETE FROM list_message WHERE post_id=$comandId");
			 $comand = '';
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chat_id");
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chatAdmin");
			 $reply = "Объявление удалено Админом за не соответствие правилам. \nЗапустите бота вновь командой\n /start";
			 $telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => $reply]);
			 $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => 'Объявление удалено из базы user '.$nick.' тел. '.$phone.' чат '.$chat_id]);
				 
		 }elseif($comandAdmin == '/reply' && $chat_id == $chatAdmin && $comandAdminId ){
			 $comandId = ($comandAdminId) ?:preg_replace('/[a-z]|\//','', $RowComand[0]['comand']);
			 $Row = $dbh->query("SELECT   chat_id FROM base_baraholka WHERE id = $comandId");
			 $chat_id = $Row[0]['chat_id'];
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand=CONCAT('/reply', $comandId) WHERE chat_id=$chatAdmin");
			 $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => 'Напишите сообщение пользователю '. $chat_id ]);
			 $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => 'Команда для остановки чата /stopchat '. $chat_id ]);
			 
		 }elseif($comand == '/reply' && $chat_id == $chatAdmin  && $text){
			 $comandId = preg_replace('/[a-z]|\//','', $RowComand[0]['comand']);
			 $Row = $dbh->query("SELECT  username, chat_id FROM base_baraholka WHERE id = $comandId");
			 $chat_id = $Row[0]['chat_id'];
			 
			 $telegram->sendMessage([ 'chat_id' => $chat_id, 'parse_mode' => 'HTML', 'text' => "<b>Админ</b>\nВ ответ принимается только одно сообщение, следующие когда админ ответит. \n\n".$text ]);
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand='/reply' WHERE chat_id=$chat_id");
			 
		 }elseif($comand == '/reply'   && $text){
			 $Row = $dbh->query("SELECT  username, phone FROM base_baraholka WHERE chat_id = $chat_id");
			 $phone = $Row[0]['phone'];
			 $nick = $Row[0]['username']? '@'.$Row[0]['username']: $phone;
			 $telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => $nick.' '.$text ]);
			 $dbh->query("UPDATE  bufer_baraholka_bot SET comand='' WHERE chat_id=$chat_id");
		 }elseif($comandAdmin == '/addlist' && $chat_id == $chatAdmin && $comandAdminId ){
			 $comandId = ($comandAdminId) ?:preg_replace('/[a-z]|\//','', $RowComand[0]['comand']);
			 //Добавляем в БД list_message
			  $dbh->query("INSERT INTO list_message  VALUES (NULL, $comandId, NOW())");
			 // редактируем сообщение в чате админа
					 $RowIdMes = $dbh->query("SELECT  message_id FROM usselbot_message WHERE post_id=$comandAdminId AND command='addlist'");
					 $mesId = $RowIdMes[0]['message_id'];
					 
					  $request_params = [
					'chat_id' => $chatAdmin,
					'message_id' => $mesId,
					'text' => "Добавлено в LIST\n/addlist".$comandAdminId. " <b>OK</b>",
					'parse_mode' => 'HTML', 
				  ];
				  
				  $mes_edit = sendTm($token, 'editMessageText', $request_params);
			$telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => 'Добавлен в очередь '.$comandId ]);	  
			 
		 }elseif($comandAdmin == '/linkpay' && $chat_id == $chatAdmin && $comandAdminId ){
			 $comandId = ($comandAdminId) ?:preg_replace('/[a-z]|\//','', $RowComand[0]['comand']);
			 //Запрашиваем chat_id
			  $Row = $dbh->query("SELECT  chat_id  FROM base_baraholka WHERE id = $comandId");
			  $chatId = $Row[0]['chat_id'];
			 // редактируем сообщение в чате админа
					 $RowIdMes = $dbh->query("SELECT  message_id FROM usselbot_message WHERE post_id=$comandAdminId AND command='linkpay'");
					 $mesId = $RowIdMes[0]['message_id'];
					 
					  $request_params = [
					'chat_id' => $chatAdmin,
					'message_id' => $mesId,
					'text' => "ОПЛАТА SEND\n/linkpay".$comandAdminId. " <b>OK</b>",
					'parse_mode' => 'HTML', 
				  ];
				  
				  $mes_edit = sendTm($token, 'editMessageText', $request_params);
				  
			$telegram->sendMessage([ 'chat_id' => $chatId, 'text' => 'Публикация объявлений с сыслкой на каналы, группы, сайты и т.д. <b>платная</b>. Стоимость <b>50Руб</b>. Для оплаты напишите @olegpopjs ', 'parse_mode' => 'HTML' ]);	  
			$telegram->sendMessage([ 'chat_id' => $chatId, 'text' => 'Сообщите номер объявления № '.$comandId ]);	  
			 
		 }
		 
	elseif(($comand == '/stepone' || $comand == '/addphoto' || $comand == '/start' || $comand == '' || $comand == '/post') and ($text || $photo))
		 { $telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => 'Нет такой команды' ]);}
	}else{ 
	
	$telegram->sendMessage([ 'chat_id' => $chat_id, 'text' => 'Нет такой команды' ]);
		
		
	}
	
		
		// блок с inline кнопками рабочий
		// $reply = "Вы не добавили ни одного фото. Хотите добавить фото или опубликовать только текст?";

		// $reply_markup = [
		 // 'inline_keyboard' => [
		  // [
		   // ['text' => 'Только текст', 'callback_data' => 'publishTextOnly'],
		   // ['text' => 'Добавить фото', 'callback_data' => 'add_more_photos']
		  // ]
		 // ]
		// ];

		// $response = sendTm($token, 'sendMessage', [
		 // 'chat_id' => $chat_id,
		 // 'text' => $reply,
		 // 'reply_markup' => json_encode($reply_markup),
		// ]);
		
		// Проверяем результат и наличие callback_query
		if (isset($result['callback_query'])) {
		  $callback_query = $result['callback_query'];
		  $data = $callback_query['data'];
		  $chat_id = $callback_query['message']['chat']['id'];
		  $mesId = $callback_query['message']['message_id'];
		  $user_id = $callback_query['from']['id'];
		  $caption = isset($callback_query['message']['caption']) ? $callback_query['message']['caption'] : '';
		  $username = $callback_query['from']['username'];
		  $callback_id = $callback_query['id'];



		  $request_params = [
			'callback_query_id' => $callback_id,
			'text' => "Вы выбрали: $data"
		  ];

		  // Проверяем ответ на отправку и добавляем отладку
		  $answerResponse = sendTm($token, 'answerCallbackQuery', $request_params);

		} 
	
?>