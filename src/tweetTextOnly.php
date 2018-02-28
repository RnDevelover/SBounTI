<?php
$start = $argv[3];
$end   = $argv[3];
$first = true;
$f     = fopen($argv[1], "r");
while (($line = trim(fgets($f))) != null)
        {
                $js                        = array();
                $js["user"]                = "unkown";
                $js["text"]                = str_replace(array(
                                "\n",
                                "\r",
                                "\t"
                ), " ", $line);
                $js["retweetedStatusText"] = "";
                $js["createdAt"]           = ($first ? $start : $end); //date('D M j H:i:s O Y',$r[1]);
                $first                     = false;
                
                $pattern = "/(?:^|\s)(\#\w+)/";
                preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE);
                $hashtags = array();
                foreach ($matches[1] as $hashk => $hashv)
                        {
                                $hashtags[] = mb_substr($hashv[0], 1, 999999, "UTF-8");
                        }
                $js["hashtags"] = $hashtags;
                $pattern        = "/(?:^|\s)(@\w+)/";
                preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE);
                $mentions = array();
                foreach ($matches[1] as $hashk => $hashv)
                        {
                                $mentions[] = mb_substr($hashv[0], 1, 999999, "UTF-8");
                        }
                $js["mentions"] = $mentions;
                $regex          = '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i';
                preg_match_all($regex, $line, $matches);
                $urls       = $matches[0];
                $js["urls"] = $urls;
                echo json_encode($js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . "\n";
        }
?>
