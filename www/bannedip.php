<?php
// TODO:
//   - ...
//
//
$document_root = $_SERVER['DOCUMENT_ROOT'];
$templates_folder = $document_root.'/templates/';
include $document_root.'/i/safemysql.class.autoban.php';
include $document_root.'/functions.php';
$db = new SafeMysql();


// проверка авторизации пользователя
session_start();
if (!isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

$doc_action = e($_GET['action']);
if ($doc_action=='delete') {
	$sites = $db->getRow("SELECT sites.siteid FROM blacklistip, sites WHERE blacklistip.ip = ?s AND blacklistip.siteid = sites.siteid AND sites.uid = ?s", e($_GET['ip']), e($_GET['uid']));
	if (!isset($sites['siteid'])) {
		header("Location: logout.php");
		exit;
	}

	$delete = $db->query("DELETE FROM blacklistip WHERE blacklistip.ip = ?s AND siteid = ?s", e($_GET['ip']), $sites['siteid']);
	header("Location: bannedip.php?uid=".e($_GET['uid']));
	exit;
} elseif ($doc_action=='addip') {
	if (!isset($_POST['ipv4']) || validateIP($_POST['ipv4']) == false) {
		header("Location: logout.php");
		exit;
	}

	$sites = $db->getRow("SELECT uid, siteid FROM sites WHERE uid = ?s AND userid = ?s AND disabled = '0'", e($_GET['uid']), return_userid($_SESSION['login']));
	if (!isset($sites['uid'])) {
		header("Location: logout.php");
		exit;
	}

	$add = $db->query("INSERT INTO blacklistip SET siteid = ?s, ip = ?s, reason = 1 ON DUPLICATE KEY UPDATE ban_count = ban_count + 1, lastupdate = NOW()", $sites['siteid'], ip2long(e($_POST['ipv4'])));
	header("Location: bannedip.php?uid=".e($_GET['uid']));
	exit;
}

$doc_var_uid = e($_GET['uid']);
$doc_sites = '
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ip</th>
                    <th>количество ограничений</th>
                    <th>причина</th>
                    <th>последнее обновление</th>
					<th></th>
                </tr>
            </thead>
            <tbody>';

// $sites = $db->getAll("SELECT uid, description FROM sites WHERE userid = ?s AND disabled = '0'",return_userid($_SESSION['login']));
// $sites = $db->getAll("SELECT uid, description, siteid, (SELECT SUM(siteid) FROM allvisits WHERE siteid=sites.siteid) AS alltime, (SELECT SUM(siteid) FROM allvisits WHERE siteid=sites.siteid AND DATE(FROM_UNIXTIME(DATE))  = CURDATE()) AS today FROM sites WHERE userid = ?s AND disabled = '0'", return_userid($_SESSION['login']));

$sites = $db->getRow("SELECT uid, description FROM sites WHERE userid = ?s AND uid = ?s AND disabled = '0'",return_userid($_SESSION['login']), e($_GET['uid']));
$doc_sitename = $sites['description'];

// $sites = $db->getAll("SELECT INET_NTOA(ip) as ip, lastupdate, ban_count FROM blacklistip WHERE userid = ?s AND uid = ?s ORDER BY ban_count DESC",return_userid($_SESSION['login']), e($_GET['uid']));
// $sites = $db->getAll("SELECT sites.userid, sites.uid, INET_NTOA(blacklistip.ip) AS ip, blacklistip.lastupdate, blacklistip.ban_count FROM blacklistip, sites WHERE sites.userid = ?s AND sites.uid = ?s AND sites.siteid = blacklistip.siteid ORDER BY blacklistip.ban_count DESC",return_userid($_SESSION['login']), e($_GET['uid']));
$sites = $db->getAll("SELECT sites.userid, sites.uid, blacklistip.ip, blacklistip.lastupdate, blacklistip.ban_count, blacklistip.reason FROM blacklistip, sites WHERE sites.userid = ?s AND sites.uid = ?s AND sites.siteid = blacklistip.siteid ORDER BY blacklistip.ban_count DESC",return_userid($_SESSION['login']), e($_GET['uid']));

foreach ($sites as $site_value) {
    $doc_sites = $doc_sites . '
                <tr>
                    <td>' . long2ip($site_value['ip']) . '</td>
                    <td>' . $site_value['ban_count'] . '</td>
                    <td>' . localReturnReason($site_value['reason']) . '</td>
                    <td>' . $site_value['lastupdate'] . '</td>
					<td><a data-toggle="modal" data-target="#myModal' . $site_value['ip'] . '"><i class="fa fa-times fa-fw"></i></a></td>
                </tr>
    ';

$doc_sites = $doc_sites . '
				<!-- Modal -->
				<div class="modal fade" id="myModal' . $site_value['ip'] . '" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								<h4 class="modal-title" id="myModalLabel">Удаление ip адреса</h4>
							</div>
							<div class="modal-body">
								Подтвердите удаление адреса <b>' . long2ip($site_value['ip']) . '</b> из списка тех, кому ограничен доступ
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">Отменить</button>
								<a href="?action=delete&uid=' . e($_GET['uid']) . '&ip=' . $site_value['ip'] . '" type="button" class="btn btn-primary">Удалить</a>
							</div>
						</div>
						<!-- /.modal-content -->
					</div>
					<!-- /.modal-dialog -->
				</div>
				<!-- /.modal -->';
}


$doc_sites = $doc_sites . '</tbody><thead><tr><th colspan="5">&nbsp;</th></tr></thead></table>';



// echo $sql_allComputers[0];
// var_dump($sql_allComputers);
// echo "here:" . $sql_userid . "ok";

include($templates_folder.'bannedip.html');

// обрабочик reason в текст
//0 - DDOS, 1 - manual add, 2 - suspicious reason
function localReturnReason ($reason) {
    switch ($reason) {
    case 0:
        return "DDOS";
        break;
    case 1:
        return "добавлено<br>вручную";
        break;
    case 2:
        return "подозрительное<br>поведение";
        break;
    }
}




?>
