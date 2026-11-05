
<?php
session_start();

$_SESSION = [];
session_destroy();

header("Location: /pwad73_php/Event%20Decoration/index.php");
exit;
?>
