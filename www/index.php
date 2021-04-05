<?php
// TODO:
//   - отправка уведомлений по почте
//	 - обработчик истекших ключей и неактивных логинов (в отдельный скрипт по cron'у)
//   - поле email при получении из формы должно переводиться в строчные
//	 - при 10 неправильных паролях - блок на 10 минут

$document_root = $_SERVER['DOCUMENT_ROOT'];
$templates_folder = $document_root.'/templates/';
include $document_root.'/functions.php';

// проверка авторизации пользователя
session_start();
if (isset($_SESSION['login'])) {
    header("Location: main.php");
    exit;
}

include $document_root.'/i/safemysql.class.autoban.php';
$db = new SafeMysql();

// регистрация, проверка заполненной формы
$doc_action = e($_GET['action']);
$authkey = e($_GET['authkey']);

// установление титула для шаблона
switch($doc_action) {
	case "check_registration": case "registration":
		$panel_title = 'Регистрация';
		break;
	case "restore": case "restore_change_password": case "restore_change_password_set":
		$panel_title = 'Восстановление пароля';
		break;
	default:
}


// при логине, проверка
if ($doc_action == "login") {
	$login_case = checklogin(lowstr(e($_POST['email'])), e($_POST['password']));
	switch ($login_case) {
		case 'no user':
		case 'bad password':
			$panel_desc = '<div class="alert alert-danger">Имя пользователя или пароль введены неправильно</div>';
			$panel_body_email_haserror = 'has-error';
			$panel_body_email = 'id="inputError" autofocus';
			$panel_body_email_value = lowstr(e($_POST['email']));

			$panel_body_password_haserror = 'has-error';
			$panel_body_password = 'id="inputError"';
			break;
		case 'passed':
			// есть такой пользователь с таким паролем
			// session_start();
			$_SESSION['login'] = lowstr(e($_POST['email']));
			header("Location: main.php");
			exit();
		break;
	}
}




// проверка условий заполнения полей формы при восстановлении пароля, установка соответствующих значений для шаблона
if ($doc_action == "restore_send_confirmation") {
	if (isset($_POST['email']) && strlen(e($_POST['email'])) > 0) {
		$panel_body_email_haserror = 'has-success';
		$panel_body_email = 'id="inputSuccess" value="'. e($_POST['email']) .'"';
	} else {
		$panel_body_email_haserror = 'has-error';
		$panel_body_email = 'id="inputError" autofocus';
		$has_error = true;
	}
}

// проверка условий заполнения полей формы при регистрации, установка соответствующих значений для шаблона
if ($doc_action == "check_registration") {
	if (isset($_POST['email']) && strlen(e($_POST['email'])) > 0) {
		if (check_users_duplicate(lowstr(e($_POST['email']))) == true) {
			$panel_desc = '<div class="alert alert-danger">Пользователь с данными email уже существует</div>';
			$panel_body_email_haserror = 'has-error';
			$panel_body_email = 'id="inputError" autofocus';
			$has_error = true;
		} else {
			$panel_body_email_haserror = 'has-success';
			$panel_body_email = 'id="inputSuccess" value="'. lowstr(e($_POST['email'])) .'"';
		}
	} else {
		$panel_body_email_haserror = 'has-error';
		$panel_body_email = 'id="inputError" autofocus';
		$has_error = true;
	}
}

if ($doc_action == "check_registration" || $doc_action == "restore_change_password_set") {
	if (isset($_POST['password']) && strlen(e($_POST['password'])) > 0) {
		$panel_body_password_haserror = 'has-success';
		$panel_body_password = 'id="inputSuccess" value="'. e($_POST['password']) .'"';
	} else {
		$panel_body_password_haserror = 'has-error';
		$panel_body_password = 'id="inputError" autofocus';
		$has_error = true;
	}

	if (isset($_POST['password2']) && strlen(e($_POST['password2'])) > 0) {
		$panel_body_password2_haserror = 'has-success';
		$panel_body_password2 = 'id="inputSuccess" value="'. e($_POST['password2']) .'"';
	} else {
		$panel_body_password2_haserror = 'has-error';
		$panel_body_password2 = 'id="inputError" autofocus';
		$has_error = true;
	}

	if (e($_POST['password']) != e($_POST['password2'])) {
		$panel_desc = '<div class="alert alert-danger">Пароль в обоих полях должен быть одинаковым</div>';
		$panel_body_password = 'id="inputError" value="'. e($_POST['password']) .'"';
		$panel_body_password_haserror = 'has-error';
		$panel_body_password2 = 'id="inputError" value="'. e($_POST['password2']) .'"';
		$panel_body_password2_haserror = 'has-error';
		$has_error = true;
	}

	if (strlen(e($_POST['password']))<6) {
		$panel_desc = '<div class="alert alert-danger">Пароль должен быть не короче 6 символов</div>';
		$panel_body_password = 'id="inputError" value="'. e($_POST['password']) .'"';
		$panel_body_password_haserror = 'has-error';
		$panel_body_password2 = 'id="inputError" value="'. e($_POST['password2']) .'"';
		$panel_body_password2_haserror = 'has-error';
		$has_error = true;
	}
}


// добавление пользователя, создание ключа и отправка письма активации пользователя
if ($has_error != true && $doc_action == "check_registration") {
	$data = array('login' => lowstr(e($_POST['email'])),
					'password' => create_hash(e($_POST['password'])),
					'created' => date('Y-m-d H:i:s'));

	$db->query("INSERT INTO ?n SET ?u", 'users', $data);
	$userid = $db->getOne("SELECT LAST_INSERT_ID()");

	$activationkey = generateKey();
	$data = array('authkey' => $activationkey,
					'userid' => $userid);
	$db->query("INSERT INTO ?n SET ?u", 'users_activations', $data);
	// здесь надо отправить письмо со ссылкой на активацию логина

	header('Location: ?action=send_confirmation');

// создание ключа и отправка письма восстановления пароля
} elseif ($has_error != true && $doc_action == "restore_send_confirmation") {
	// проверка на существование пользователя
	$userid = return_userid(lowstr(e($_POST['email'])));

	if ($userid > 0) {
		$activationkey = generateKey();
		$data = array('authkey' => $activationkey,
						'userid' => $userid);
		$db->query("INSERT INTO ?n SET ?u", 'users_activations', $data);

		// здесь надо отправить письмо со ссылкой на сброс пароля
	}
	header('Location: ?action=restore_confirmation_sent');

// активация пользователя
} elseif ($doc_action == "users_activation") {
	$userid = $db->getOne("SELECT userid FROM users_activations WHERE authkey = ?s AND used = '0'",$authkey);
	if (!empty($userid)) {
		$db->query("UPDATE users SET active = '1' WHERE id = ?i", $userid);
		$db->query("UPDATE users_activations SET used = '1' WHERE authkey = ?s", $authkey);
		header('Location: ?action=user_activation_ok');
	} else {
		header('Location: ?action=user_activation_bad');
	}

// изменение пароля
} elseif ($has_error != true && $doc_action == "restore_change_password_set") {
	$userid = $db->getOne("SELECT userid FROM users_activations WHERE authkey = ?s AND used = '0'",$authkey);
	if (!empty($userid)) {
		$db->query("UPDATE users_activations SET used = '1' WHERE authkey = ?s", $authkey);
		$db->query("UPDATE ?n SET password = ?s WHERE id = ?i", 'users', create_hash(e($_POST['password'])), $userid);
		// echo create_hash(e($_POST['password']));
		header('Location: ?action=user_restore_ok');
	} else {
		header('Location: ?action=user_restore_bad');
	}
} else {
	include($templates_folder.'index.html');
}









exit();

?>