<?php
ini_set('display_errors', true);
ini_set('display_startup_errors', true);
ini_set('error_reporting', -1);

require_once __DIR__ . '/../config.php';

class Db {

    protected $dbh;
    public function __construct()
    {
        $username = DB_USER;
        $password = DB_PASSWORD;
        $host = DB_HOST;
        $db = DB_NAME;
        $this->dbh  =  new PDO("mysql:dbname=$db;host=$host;charset=utf8mb4", $username, $password);
    }
    public function execute( $sql){
        if ($this->dbh->prepare($sql)) {
            return true;}
        else {
            return false;}

    }

   /*  public function query($sql){
            if ($this->execute($sql)){
                $sth = $this->dbh->prepare($sql);
                $sth->execute();
                return $sth->fetchAll(PDO::FETCH_ASSOC);// если объект то вставить в скобки fetchAll(PDO::FETCH_OBJ) fetch(PDO::FETCH_ASSOC)
            }else {
                return false;}
        } */
		
	public function query($sql, $params = []) {
    try {
        $sth = $this->dbh->prepare($sql);
        $sth->execute($params);  // Используем параметризованные запросы
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $this->reconnect();  // Переподключение при ошибке
        throw $e;  // Проброс исключения для обработки выше
    }
	}

	private function reconnect() {
	       $this->dbh = new PDO("mysql:dbname=".DB_NAME.";host=".DB_HOST.";charset=utf8mb4", DB_USER, DB_PASSWORD); // Повторная инициализация
	   }
		
	// Новый метод для получения последнего ID
    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }
	
}

//$base = new Db();


//$mas = array( ':chat_id' => 5555, ':username' => 'Limon1980', ':firstname' => 'FLimon1980', ':lastname' => 'LLimon1980',':text' => 'Первый пост', ':post' => 0, ':moder' => 0);
//var_dump($mas);
//$test = 'Тест555';
//$data = $base->query("INSERT INTO base_baraholka  VALUES (NULL, 123, '$test', 'Эдик', 'Эдик', 'Эдик', 0, 0, NOW())");
//var_dump($data);



?>