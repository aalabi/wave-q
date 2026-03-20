<?php
require_once "../config.php";
$Inbox = new QuasiInbox();
?>
<html>

<head>
    <title></title>
</head>

<body>
    <?= $Inbox->getQuasiInboxContent(); ?>
</body>

</html>