<?php

serverUIDreload();

// ������������� ���������� uid - siteKey �� �������
function serverUIDreload() {
	// ���� ��� ���������� uid - siteKey �� �������
	$rootKey = "unique id string"; // key of service to reload configuration, store in autoban.go before compilation
	$autobansrv = "http://your_service/service/check";
	// echo $autobansrv . '?rootKey='.$rootKey;
	echo do_post_request($autobansrv . '?rootKey='.$rootKey);
}

// �������� POST �����
function do_post_request($url, $data) {
	$result = file_get_contents($url, false, stream_context_create(array(
		'http' => array(
			'method'  => 'GET',
			'header'  => 'Content-type: application/x-www-form-urlencoded'
			// 'content' => http_build_query($data)
		)
	)));

	return $result;
}

?>