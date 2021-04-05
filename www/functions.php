<?php

// инициирование обновления uid - siteKey на сервере
function serverUIDreload() {
	// ключ для обновления uid - siteKey на сервере
	$rootKey = "unique key";
	$autobansrv = "http://autobanservice/reload";
	do_post_request($autobansrv . '?rootKey='.$rootKey);
}


// проверка пользователя при логине, ответы: no user, passed, bad password
function checklogin ($email, $password) {
	global $db;
	$user = $db->getRow("SELECT login, password FROM users WHERE login=?s",$email);	// запрос базы на пользователей с введенным логином
	if (empty($user)) {
		return "no user"; 	// нет такого пользователя
	} else {
		// есть такой пользователь, сверка паролей
		$password_entered = create_hash($password);
		if ($password_entered == $user['password']) {
			return "passed";
		} else {
			return "bad password";
		}
	}
}

function check_password($hash, $password) {
	// первые 29 символов хеша, включая алгоритм, «силу замедления» и оригинальную «соль» поместим в переменную  $full_salt
    $full_salt = substr($hash, 0, 29);

    // выполним хеш-функцию для переменной $password
    $new_hash = crypt($password, $full_salt);

	// возвращаем результат («истина» или «ложь»)
    return ($hash == $new_hash);
}

// проверяет наличие дубликата пользователя
function check_users_duplicate($username) {
	global $db;
	$user = $db->getOne("SELECT login FROM users WHERE login=?s",$username);
	if (empty($user)) {
		return false;
	} else {
		return true;
	}
}

function return_userid($username) {
	global $db;
	$user = $db->getOne("SELECT id FROM users WHERE login=?s",$username);
	return $user;
}

// --------------------------------------------
function e($string) {	// чистит внешнюю строку от html кода
	return htmlspecialchars($string, ENT_QUOTES);
}

function lowstr($string) { // переводит строку в нижний регистрации
	return mb_strtolower($string);
}

function create_hash($password) {
	$unique_salt = 'toeAI29o6LZvmMXSbCyJkM'; // соль для blowfish должна быть длиной в 22 символа
	return crypt($password, '$2a$10$'.$unique_salt);
}

// генерация уникального ключа по формату
function generateKey() {
	return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

// генерация уникального ключа для вставки на сайт
function generateKeySite() {
    return sprintf('%04X%04X%04X%04X%04X%04X%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
}

// получение уникального ключа для вставки на сайт и проверка на совпадение
function getKeySite() {
	global $db;
	do {
		$key = generateKeySite();
	    $uid = $db->getOne("SELECT uid FROM sites WHERE uid=?s",$key);
	    if ($uid == "") { $haveduplicate = "no"; } else {$haveduplicate = "yes";}
	} while ($haveduplicate == "yes");
    return $key;
}

// обработчик формы типа checkbox
function formCheckbox($form) {
	if (e($form) == "on") {
		return 1;
	} else {
		return 0;
	}
}


// пустой GET запрос
function do_post_request($url) {
	$result = file_get_contents($url, false, stream_context_create(array(
		'http' => array(
			'method'  => 'GET',
			'timeout' => 3,
			'header'  => 'Content-type: application/x-www-form-urlencoded'
		)
	)));

	return $result;
}

// проверка ip адреса
function validateIP($ip){
    return inet_pton($ip) !== false;
}

?>