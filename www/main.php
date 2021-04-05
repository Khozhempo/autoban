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

$doc_sites = '
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>#</th>
                    <th width="100%">сайт</th>
                    <th>&nbsp;</th>
                    <th>&nbsp;</th>
                </tr>
            </thead>
            <tbody>';

$sites = $db->getAll("SELECT uid, description FROM sites WHERE userid = ?s AND disabled = '0'",return_userid($_SESSION['login']));
// $sites = $db->getAll("SELECT uid, description, siteid, (SELECT SUM(siteid) FROM allvisits WHERE siteid=sites.siteid) AS alltime, (SELECT SUM(siteid) FROM allvisits WHERE siteid=sites.siteid AND DATE(FROM_UNIXTIME(DATE))  = CURDATE()) AS today FROM sites WHERE userid = ?s AND disabled = '0'", return_userid($_SESSION['login']));

$tmp_inc = 0;
foreach ($sites as $site_value) {
    $doc_sites = $doc_sites . '
                <tr onclick="document.location = `site.php?action=change&uid=' . $site_value['uid'] . '`">
                    <td>' . ++$tmp_inc . '</td>
                    <td>' . $site_value['description'] . '</td>
                    <td>' . '<a href="bannedip.php?uid=' . $site_value['uid'] . '" title="заблокированные ip"><i class="fa fa-ban fa-fw" ></i></a>' .'</td>
                    <td>' . '<a href="statistics.php?uid=' . $site_value['uid'] . '" title="статистика"><i class="fa fa-bar-chart-o fa-fw"></i></a>' . '</td>
                </tr>
    ';
}


$doc_sites = $doc_sites . '</tbody><thead><tr><th colspan="4">&nbsp;</th></tr></thead></table>';


// echo $sql_allComputers[0];
// var_dump($sql_allComputers);
// echo "here:" . $sql_userid . "ok";

include($templates_folder.'main.html');





?>
