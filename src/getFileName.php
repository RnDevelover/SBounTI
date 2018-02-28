<?php
//////// PREPARES CACHE FILE NAMES

function getFileName($infix, $data)
//typeCacheLonger/
        {
                $md = md5($data);
                $st = substr($md, 0, 2);
                $md = $st . "/" . $md;
                @mkdir(__DIR__ . "/../caches/$infix/$st");
                
                return __DIR__ . "/../caches/$infix/" . $md;
        }

?>
