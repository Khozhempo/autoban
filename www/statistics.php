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




$sites = $db->getRow("SELECT uid, description, siteid FROM sites WHERE userid = ?s AND uid = ?s AND disabled = '0'", return_userid($_SESSION['login']), e($_GET['uid']));

if (!isset($sites['uid'])) {
    header("Location: logout.php");
    exit;
}

$doc_statsitename = $sites['description'];

$doc_body_sitesDDOS = $db->getRow("SELECT
  COUNT(ip) AS today,
  (SELECT
     COUNT(ip)
   FROM blacklistip
   WHERE siteid = ?s
       AND reason = 0) AS alltime
FROM blacklistip
WHERE DATE(lastupdate) = DATE(NOW())
    AND siteid = ?s
    AND reason = 0", $sites['siteid'], $sites['siteid']);

$doc_body_sitesSusp = $db->getRow("SELECT
  COUNT(ip) AS today,
  (SELECT
     COUNT(ip)
   FROM blacklistip
   WHERE siteid = ?s
       AND reason = 2) AS alltime
FROM blacklistip
WHERE DATE(lastupdate) = DATE(NOW())
    AND siteid = ?s
    AND reason = 2", $sites['siteid'], $sites['siteid']);

// SUSP statistics
$sql_stat_susp = $db->getAll("SELECT
  DATE(lastupdate) DateOnly,
  COUNT(ip) AS number
FROM blacklistip
WHERE reason = 2
    AND siteid = ?s
GROUP BY DateOnly", $sites['siteid']);

$doc_stat_susp = "new Morris.Area({
           element: 'susp-chart',
           data: [";
foreach ($sql_stat_susp as $temp) {
    $doc_stat_susp = $doc_stat_susp . "{
        period: '" . $temp[DateOnly] . "',
        IPs: " . $temp[number] . "
        },";
}

$doc_stat_susp = $doc_stat_susp . "],
    xkey: 'period',
    ykeys: ['IPs'],
    labels: ['per day'],
    pointSize: 2,
    hideHover: 'auto',
    resize: true,
    ymin: 0
});";

// DDOS statistics
$sql_stat_ddos = $db->getAll("SELECT
  DATE(lastupdate) DateOnly,
  COUNT(ip) AS number
FROM blacklistip
WHERE reason = 0
    AND siteid = ?s
GROUP BY DateOnly", $sites['siteid']);

$doc_stat_DDOS = "new Morris.Area({
           element: 'ddos-chart',
           data: [";
foreach ($sql_stat_DDOS as $temp) {
    $doc_stat_DDOS = $doc_stat_DDOS . "{
        period: '" . $temp[DateOnly] . "',
        IPs: " . $temp[number] . "
        },";
}

$doc_stat_DDOS = $doc_stat_DDOS . "],
    xkey: 'period',
    ykeys: ['IPs'],
    labels: ['per day'],
    pointSize: 2,
    hideHover: 'auto',
    resize: true,
    ymin: 0
});";



$doc_body_agent = $doc_body_agent . '</tbody><thead><tr><th colspan="2">&nbsp;</th></tr></thead></table>';


include($templates_folder.'statistics.html');





?>
