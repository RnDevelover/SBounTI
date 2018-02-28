<?php
$content = file_get_contents($argv[1]);
$pos     = mb_strpos($content, "\"text\":\"", 0, "UTF-8");
exit(($pos === false) ? 1 : 0);
?>
