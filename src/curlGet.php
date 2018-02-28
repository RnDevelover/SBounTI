<?php
include_once(__DIR__ . "/getFileName.php");
function curl_get($url, $cache = false)
        {
                $md5filename = getFileName("urlcache", $url);
                //echo $md5filename;die;
                if ($cache == true)
                        {
                                if (file_exists($md5filename))
                                                return file_get_contents($md5filename);
                        }
                
                $defaults = array();
                @$defaults[CURLOPT_URL] = $url;
                @$defaults[CURLOPT_HEADER] = 0;
                @$defaults[CURLOPT_RETURNTRANSFER] = TRUE;
                @$defaults[CURLOPT_TIMEOUT] = 0;
                
                $ch = curl_init();
                curl_setopt_array($ch, $defaults);
                if (!$result = curl_exec($ch))
                        {
                                $retry = 0;
                                while ( /*curl_errno($ch) == 28 && */ $retry < 10)
                                        {
                                                sleep(10);
                                                $result = curl_exec($ch);
                                                if ($result)
                                                                break;
                                                //        echo "Retry:$retry\n";
                                                $retry++;
                                        }
                                if (!$result)
                                                trigger_error(curl_error($ch));
                        }
                curl_close($ch);
                if ($cache == true)
                        {
                                $f = fopen($md5filename, "w");
                                fwrite($f, $result);
                                fclose($f);
                        }
                return $result;
        }
?>
