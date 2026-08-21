<?php
session_start();
session_destroy();
setcookie('si_recruit_auth', '', time() - 3600, "/");
header("Location: login.php");
exit;
?>
