<?php
include_once(__DIR__ . "/curlGet.php");
function copyUsernameFromTo($from, $to)
        {
                $prefix = __DIR__ . "/../caches/twitterUserDirectorySparqlSparql/";
                $from   = md5($from);
                $to     = md5($to);
                $file1  = $prefix . substr($from, 0, 2) . "/" . $from;
                $file2  = $prefix . substr($to, 0, 2) . "/" . $to;
                if (!file_exists($file1))
                                return;
                @mkdir($prefix . substr($to, 0, 2));
                $content = file_get_contents($file1);
                $f       = fopen($file2, "w");
                fwrite($f, $content);
                fclose($f);
        }

function getWikipageNameGivenTwitterUsername($arr)
        {
                $prefix      = __DIR__ . "/../caches/twitterUserDirectorySparql/";
                $queryArray  = array();
                $returnArray = array();
                foreach ($arr as $uname)
                        {
                                $md5Uname = md5($uname);
                                @mkdir($prefix . substr($md5Uname, 0, 2));
                                $file = $prefix . substr($md5Uname, 0, 2) . "/" . $md5Uname;
                                if (file_exists($file))
                                        {
                                                $re = json_decode(file_get_contents($file), true);
                                                @$returnArray[$uname] = $re;
                                        }
                                else
                                        {
                                                $loweredName = mb_strtolower($uname, "UTF-8");
                                                if ($loweredName != $uname)
                                                        {
                                                                $md5LoweredName = md5($loweredName);
                                                                $file           = $prefix . substr($md5LoweredName, 0, 2) . "/" . $md5LoweredName;
                                                                if (file_exists($file))
                                                                        {
                                                                                $re = json_decode(file_get_contents($file), true);
                                                                                @$returnArray[$uname] = $re;
                                                                                @$returnArray[$loweredName] = $re;
                                                                        }
                                                                else
                                                                        {
                                                                                @$queryArray[$uname] = 1;
                                                                                @$queryArray[$loweredName] = 1;
                                                                        }
                                                        }
                                                else
                                                        {
                                                                @$queryArray[$uname] = 1;
                                                        }
                                        }
                        }
                $saveArray = array();
                while (count($queryArray) > 0)
                        {
                                $queryString = "";
                                $count       = 0;
                                foreach ($queryArray as $key => $uname)
                                        {
                                                $queryString .= "\"" . $key . "\",";
                                                unset($queryArray[$key]);
                                                @$returnArray[$key] = array();
                                                @$saveArray[$key] = array();
                                                $count++;
                                                if ($count == 50 || count($queryArray) == 0)
                                                        {
                                                                $queryString = rtrim(trim($queryString, ", "), ", ");
                                                                $queryString = "SELECT ?human ?twitterusername { ?human wdt:P2002 ?twitterusername . FILTER (?twitterusername IN ($queryString))}";
                                                                $result      = curl_get("https://query.wikidata.org/bigdata/namespace/wdq/sparql?format=json&query=" . urlencode($queryString), true);
                                                                $result      = str_replace(array(
                                                                                "\r",
                                                                                "\n",
                                                                                "\t"
                                                                ), "", $result);
                                                                $result      = json_decode($result, true);
                                                                if (isset($result["results"]["bindings"]) && count($result["results"]["bindings"]) > 0)
                                                                        {
                                                                                $newResult = array();
                                                                                for ($a = 0; $a < count($result["results"]["bindings"]); $a++)
                                                                                        {
                                                                                                $pos = strrpos($result["results"]["bindings"][$a]["human"]["value"], "/");
                                                                                                
                                                                                                $newResult[] = substr($result["results"]["bindings"][$a]["human"]["value"], $pos + 1, 999999);
                                                                                        }
                                                                                $result1 = curl_get("https://www.wikidata.org/w/api.php?action=wbgetentities&ids=" . implode("|", $newResult) . "&sites=enwiki&format=json", true);
                                                                                $result1 = json_decode($result1, true);
                                                                                foreach ($result1["entities"] as $key => $entity)
                                                                                        {
                                                                                                $label      = "";
                                                                                                $aliases    = array();
                                                                                                $twUserName = "";
                                                                                                $title      = "";
                                                                                                if (isset($entity["labels"]["en"]["value"]))
                                                                                                        {
                                                                                                                $label = $entity["labels"]["en"]["value"];
                                                                                                        }
                                                                                                if (isset($entity["aliases"]["en"]) && count($entity["aliases"]["en"]) > 0)
                                                                                                        {
                                                                                                                for ($a = 0; $a < count($entity["aliases"]["en"]); $a++)
                                                                                                                                $aliases[] = $entity["aliases"]["en"][$a]["value"];
                                                                                                        }
                                                                                                
                                                                                                if (isset($entity["claims"]["P2002"][0]["mainsnak"]["datavalue"]["value"]))
                                                                                                                $twUserName = $entity["claims"]["P2002"][0]["mainsnak"]["datavalue"]["value"];
                                                                                                if (isset($entity["sitelinks"]["enwiki"]["title"]))
                                                                                                                $title = str_replace(" ", "_", $entity["sitelinks"]["enwiki"]["title"]);
                                                                                                $obj        = array();
                                                                                                $twUserName = mb_strtolower($twUserName, "UTF-8");
                                                                                                @$obj["twitterUserName"] = $twUserName;
                                                                                                @$obj["label"] = $label;
                                                                                                @$obj["aliases"] = $aliases;
                                                                                                @$obj["wikiPageTitle"] = $title;
                                                                                                @$saveArray[$twUserName] = $obj;
                                                                                                @$returnArray[$twUserName] = $obj;
                                                                                        }
                                                                        }
                                                                break;
                                                        }
                                        }
                        }
                foreach ($saveArray as $key => $obj)
                        {
                                $md5Name = md5(mb_strtolower($key, "UTF-8"));
                                @mkdir($prefix . substr($md5Name, 0, 2));
                                $f = fopen($prefix . substr($md5Name, 0, 2) . "/" . $md5Name, "w");
                                fwrite($f, json_encode($obj, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE));
                                fclose($f);
                        }
                return $returnArray;
        }
?>
