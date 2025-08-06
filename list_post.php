<?php
include('../vendor/autoload.php');
include ('classes/db.php');
include ('func.lib.php');

use Telegram\Bot\Api; 
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Objects\CallbackQuery;
use Telegram\Bot\Keyboard\Keyboard;

$token = '549877017:AAEPL3XggOPjjSmDLGUVvaeuFqq8JEPpun0';
$telegram = new Api($token); //Устанавливаем токен, полученный у BotFather
$dbh = new Db();
$chatAdmin = '337959904'; // Устанавливае ID чата с ботом админа

$Row = $dbh->query("SELECT  post_id FROM list_message ORDER BY id LIMIT 1");
$comandAdminId = $Row[0]['post_id'];


if($comandAdminId){
			$Row = $dbh->query("SELECT  text, phone, username, chat_id FROM base_baraholka WHERE id = $comandAdminId AND moder = 1");
			$phone = $Row[0]['phone'];
			$chat_id = $Row[0]['chat_id'];
			 $text = strip_tags($Row[0]['text']);
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
			 $media .= '{"type":"photo","media":"'.$key['photo_id'].'"}, ';
			 // массив file_id для загрузки в инстаграм
			 $file_id[] = $key['photo_id'];
			 $file_ids[] = $key['photo_id'];
			 }
			 $len = mb_strlen($media);
			 // $media = mb_substr($media, 0, $len-3).',"caption":"'.$phone.' '.$nick.'"}';
			 $media = mb_substr($media, 0, $len-3).',"caption":"'.$text." ".$nick.'","parse_mode":"HTML"}';

			 $request_params = [
							'chat_id' => '@uss_baraholka',
							'media' => '['.$media.']',
							'disable_notification' => true,
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
				$telegram->sendMessage([ 'chat_id' => $chatAdmin, 'text' => 'Опубликовано объявление ID '.$comandAdminId .' от пользователя '. $nick, 'disable_notification' => true ]);
					
								
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
				  $dbh->query("DELETE FROM list_message  WHERE post_id = $comandAdminId");
				
		 }