<?php
    setcookie(session_name(), session_id(), time()-60*60*24);
    session_unset();
    session_destroy();
	header("Location: index.php");
?>