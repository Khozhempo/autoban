<?php
// CONFIG
$uid = 'unique id string';
$autobansrv = "http://your_service/service/check";

$cookiename = "autoban";

// ?action=turnoff - назначет cookie и отмечает ip в группу не для обработки
// ?action=turnon  - удаляет cookie
error_reporting(0);	# disable to show php debug error

$userAgent  = getenv('HTTP_USER_AGENT');
$userHttpIp = getenv('HTTP_CLIENT_IP');
$userHttpX  = getenv('HTTP_X_FORWARDED_FOR');
$userRemote = getenv('REMOTE_ADDR');
$userUrl    = getenv('REQUEST_URI');
$cookie_d   = getenv('HTTP_COOKIE');
$userRefer  = getenv('HTTP_REFERER');   // предыдущая страница
$userReqMeth= getenv('REQUEST_METHOD'); // GET or POST

$get_action = isset($_GET['action']) ? e($_GET['action']) : '';

// обработчик cookie
if ($get_action == "turnoff") {
	setcookie($cookiename,"off",time() + 60 * 60 * 24 * 30 * 12,"/");
} elseif ($get_action == "turnon") {
	unset($_COOKIE[$cookiename]);
    setcookie($cookiename, null, -1, '/');
}

// получение ip
if (!empty($userHttpIp)) {
	$ip = $userHttpIp;
} elseif (!empty($userHttpX)) {
	$ip = $userHttpX;
} else {
	$ip = $userRemote;
}
// $bot=$userAgent;  

// if (strstr($userAgent, 'Yandex')) {$bot='Yandex';}
// elseif (strstr($userAgent, 'Google')) {$bot='Google';}
// elseif (strstr($userAgent, 'Yahoo')) {$bot='Yahoo';}
// elseif (strstr($userAgent, 'Mail')) {$bot='Mail';}

// if ($bot!='Yandex' and $bot!='Google' and $bot!='Yahoo' and $bot!='Mail') { $ownervisit = 2; }
# Если кука autoban установлена в значении off, то это админ сайта
if (strstr($userAgent, 'Yandex') || strstr($userAgent, 'Google') || strstr($userAgent, 'Yahoo') || strstr($userAgent, 'Mail')) { 
	$ownervisit = 2; 
} elseif (strripos($cookie_d, $cookiename.'=off') == true) { 
	$ownervisit = 1; 
} else { 
	$ownervisit = 0; 
}
	
$data = array(
	'ip'		 => $ip,
	'date'       => time(true),
	'url'        => $userUrl,
	'ownervisit' => $ownervisit,
	'agent'	     => $userAgent,
	'refer'      => $userRefer,
	'method'     => $userReqMeth
);
// echo do_post_request($autobansrv . '?uid='.$uid, $data);
$result = do_post_request($autobansrv . '?uid='.$uid, $data);
// echo "Result: ";
// echo $result;
// echo $userAgent;
if ($result == "IP banned") {
	header("Location: /403.php");
	exit;
}

if (strstr($userAgent, 'SemrushBot') || strstr($userAgent, 'MJ12bot') || strstr($userAgent, 'AhrefsBot') || strstr($userAgent, 'bingbot') || strstr($userAgent, 'DotBot') || strstr($userAgent, 'LinkpadBot') || strstr($userAgent, 'SputnikBot') || strstr($userAgent, 'statdom.ru') || strstr($userAgent, 'MegaIndex.ru') || strstr($userAgent, 'WebDataStats') || strstr($userAgent, 'Jooblebot') || strstr($userAgent, 'Baiduspider') || strstr($userAgent, 'BackupLand') || strstr($userAgent, 'NetcraftSurveyAgent') || strstr($userAgent, 'openstat.ru')) {
	header("Location: /403.php");
	exit;
}

// ----- FUNCTIONS -----
// отправка POST формы
function do_post_request($url, $data) {
	$result = file_get_contents($url, false, stream_context_create(array(
		'http' => array(
			'method'  => 'POST',
			'timeout' => 5,
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => http_build_query($data)
		)
	)));

	return $result;
}

// чистит внешнюю строку от html кода
function e($string) { return htmlspecialchars($string, ENT_QUOTES); }

?>
