<?php

$dbName = 'sqliteTest.db';
$db = new SQLite3($dbName);

echo 'создал бд файл с именем ' . $dbName;

$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE
)");


$users = [];
$x = 0;
while($x <= 30){
	array_push($users,
	['name' => "name{$x}",
	'email' => "email{$x}@mail.com"]);
	$x++;
}

$stmt = $db->prepare("INSERT OR IGNORE INTO users (name, email) VALUES (:name, :email)");

foreach ($users as $user) {
    $stmt->bindValue(':name', $user['name'], SQLITE3_TEXT);
    $stmt->bindValue(':email', $user['email'], SQLITE3_TEXT);
    
    $stmt->execute();
}

$db->close();
echo "Сидинг завершен!\n";

//var_dump($users);

die;

