<?php
// TODO:
//   - шаблон, top как в index + Выйти [логин]
//
//
$document_root = $_SERVER['DOCUMENT_ROOT'];
$templates_folder = $document_root.'/templates/';
include $document_root.'/i/safemysql.class.autoban.php';
include $document_root.'/functions.php';
$db = new SafeMysql();

$doc_action = e($_GET['action']);


// проверка авторизации пользователя
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

// установление титула для шаблона
switch($doc_action) {
    case "addsite":
        $panel_title = 'Добавить сайт';
        $panel_form_checkbox = 'checked';
        break;
    case "change":
        $panel_title = 'Обновить информацию о сайте';
        break;
    case "delete":
        $panel_title = 'Удалить сайт';
        break;
    default:
        // $panel_title = 'Добавить сайт';
        // $panel_form_checkbox = 'checked';
        break;
}

// $doc_test = return_userid($_SESSION['login']);

// обработчик событий
switch($doc_action) {
    case "addsite":
        $data = array('uid' => getKeySite(),
                        'active' => formCheckbox($_POST['active']),
                        'description' => e($_POST['name']),
                        'userid' => return_userid($_SESSION['login']));
        $db->query("INSERT INTO ?n SET ?u", 'sites', $data);
        serverUIDreload();
        header("Location: main.php");
        break;

    case "change":
    case "delete":
		if (isset($_GET['step']) && e($_GET['step']) == "confirmed") {
			$data = array('disabled' => 1);
			$db->query("UPDATE sites SET ?u WHERE uid = ?s AND userid = ?i", $data, e($_POST['uid']), return_userid($_SESSION['login']));
			serverUIDreload();
			header("Location: main.php");
		} else {
			$uid_record = $db->getRow("SELECT description, active FROM sites WHERE uid=?s AND disabled = 0",e($_GET['uid']));
			if ($uid_record['active'] == 1) { $panel_form_checkbox = "checked"; }
			$panel_form_uid = e($_GET['uid']);
			$panel_form_name = $uid_record['description'];
		}
        break;

    case "update":
        $data = array('active' => formCheckbox($_POST['active']),
                        'description' => e($_POST['name']));
        $db->query("UPDATE sites SET ?u WHERE uid = ?s AND userid = ?i AND disabled = 0", $data, e($_POST['uid']), return_userid($_SESSION['login']));
        header("Location: main.php");
        break;
}

    // $data = array('login' => lowstr(e($_POST['email'])),
    //                 'password' => create_hash(e($_POST['password'])),
    //                 'created' => date('Y-m-d H:i:s'));

    // $db->query("INSERT INTO ?n SET ?u", 'users', $data);



include($templates_folder.'site.html');

?>
