<?php

$apiKey = 'token';
$apiKey2 = 'token';  //openrouter
function sendApi($apiKey, $model, $messages, $temperature = 0.7, $max_tokens = 8000, $systemRole = null)
{
    $url = 'https://unlimbot.com/v1/chat/completions';

    // Добавляем системное сообщение, если оно задано
    if ($systemRole !== null) {
        array_unshift($messages, [
            "role" => "system",
            "content" => $systemRole
        ]);
    }

    $request_data = json_encode([
        "model" => $model,
        "messages" => $messages,
        "temperature" => $temperature,
        "max_tokens" => $max_tokens
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

function sendApi2($apiKey, $messages, $systemRole)
{

	 $url = 'https://openrouter.ai/api/v1/chat/completions';
	
	// Добавляем системное сообщение, если оно задано
    if ($systemRole !== null) {
        array_unshift($messages, [
            "role" => "system",
            "content" => $systemRole
        ]);
    }
	 
    $request_data = json_encode([
        "model" => 'deepseek/deepseek-chat:free',
        "messages" => $messages,
		"temperature" => 0.3,
		"max_tokens" => 16384,
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $headers = [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request_data);

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// $Row = mysqli_fetch_assoc(mysqli_query($CONNECT, "SELECT `text`, `post_id` FROM `geo_tm_content` WHERE `post` = 0 AND `check_gpt` = 0"));



// $text = str_replace('<br />', '', $text);
// $len = mb_strlen($text);
// echo '<h1> длина '.$len.'</h1>';
// echo $text;
// $text = '';
$Row = $dbh->query("SELECT  text, phone, username FROM base_baraholka WHERE chat_id = $chat_id AND moder = 0 AND id=$IdPhoto");
$text = $Row[0]['text'];
if ($text){


$model = "gpt-4o-mini";
$messages = [
    ["role" => "user", "content" => ".$text."]
];
$systemRole = "Ты админ телеграм канала Барахолка Уссурийск. Твоя задача сократить текст объявления который тебе поступает до 950 симвлов, сохрани форматирование. В ответ выдай готовый сокращенный текст без пояснений";

// $systemRole = "Проанализируй html код. Найди в нем ссыки с темами новостей. Выдай массив с значениями тема новости и ссыка";

$response = sendApi($apiKey, $model, $messages, 0.3, 16000, $systemRole);

if (!isset($response['choices'][0]['message']['content'])){
	$response = sendApi2($apiKey2, $messages, $systemRole);
}

print_r($response['model']);

$answer = $response['choices'][0]['message']['content'];

$text = $answer;
$len = mb_strlen($text);
echo '<h1> длина финал'.$len.'</h1>';

echo '<h1>'.$text.'</h1>';

}
if ($answer){
$Row = $dbh->query("UPDATE base_baraholka SET text='$text' WHERE id=$IdPhoto");
$comand = '/stepone';
$dbh->query("UPDATE  bufer_baraholka_bot SET comand='$comand' WHERE chat_id=$chat_id");
	
$telegram->sendMessage([
				  'chat_id' => $chat_id,
				  'text' => $text,
				  'parse_mode' => 'HTML'
				 
				]);
			
}else{
	$telegram->sendMessage([
				  'chat_id' => $chat_id,
				  'text' => 'Предложить объявление'
				 
				]);
	
}