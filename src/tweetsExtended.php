<?php
set_time_limit(0);
for ($a = 1; $a < count($argv); $a++)
        {
                $f = fopen($argv[$a], "r");
                while (($line = fgets($f)) != null)
                        {
                                $tweet = json_decode($line, true);
                                if (isset($tweet["text"]))
                                        {
                                                $js         = array();
                                                $js["user"] = $tweet["user"]["id"];
                                                //              print_r($result);       
                                                $js["text"] = str_replace(array(
                                                                "\n",
                                                                "\r",
                                                                "\t"
                                                ), " ", $tweet["text"]);
                                                if (isset($tweet["retweetedStatus"]["text"]))
                                                                $js["retweetedStatusText"] = str_replace(array(
                                                                                "\n",
                                                                                "\r",
                                                                                "\t"
                                                                ), " ", $tweet["retweetedStatus"]["text"]);
                                                else
                                                                $js["retweetedStatusText"] = "";
                                                @$js["createdAt"] = $tweet["createdAt"];
                                                if (isset($tweet["created_at"]))
                                                                $js["createdAt"] = $tweet["created_at"];
                                                if (isset($tweet["entities"]["urls"]))
                                                                foreach ($tweet["entities"]["urls"] as $ue)
                                                                                @$js["urls"][] = $ue["expanded_url"];
                                                if (isset($tweet["entities"]["hashtags"]))
                                                                foreach ($tweet["entities"]["hashtags"] as $he)
                                                                                @$js["hashtags"][] = $he["text"];
                                                if (isset($tweet["entities"]["user_mentions"]))
                                                                foreach ($tweet["entities"]["user_mentions"] as $um)
                                                                                @$js["mentions"][] = $um["screen_name"];
                                                
                                                if (isset($tweet["userMentionEntities"]))
                                                                foreach ($tweet["userMentionEntities"] as $um)
                                                                                @$js["mentions"][] = $um["screenName"];
                                                if (isset($tweet["hashtagEntities"]))
                                                                foreach ($tweet["hashtagEntities"] as $he)
                                                                                @$js["hashtags"][] = $he["text"];
                                                if (isset($tweet["urlEntities"]))
                                                                foreach ($tweet["urlEntities"] as $ue)
                                                                                @$js["urls"][] = $ue["expandedURL"];
                                                echo json_encode($js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) . "\n";
                                        }
                        }
        }
?>
