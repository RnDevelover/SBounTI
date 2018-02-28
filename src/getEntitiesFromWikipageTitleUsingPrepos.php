<?php
include_once(__DIR__ . "/queryTagMe.php");
if (!@is_array($stopwords))
        {
                $sayac     = 0;
                $stopwords = array();
                $file      = fopen(__DIR__ . "/../texts/stopwords.txt", "rb");
                while (!feof($file))
                        {
                                $stopwords[$sayac] = str_replace(array(
                                                "\n",
                                                "\r",
                                                "\t"
                                ), array(
                                                "",
                                                "",
                                                ""
                                ), fgets($file));
                                $sayac++;
                        }
        }

function getEntitiesFromWikipageTitle($s, $id = null)
        {
                global $stopwords;
                $s     = preg_replace('/[^a-z0-9 ]/i', " N/AN/AN/A ", $s);
                $oarr  = explode(" ", $s);
                $words = array();
                foreach ($oarr as $word)
                        {
                                $word = trim($word);
                                if (strlen($word) > 0 && !in_array(strtolower($word), $stopwords))
                                        {
                                                $words[] = $word;
                                        }
                                else
                                                $words[] = " N/AN/AN/A ";
                        }
                
                $sentence = "";
                for ($a = 0; $a < count($words); $a++)
                        {
                                $sentence .= " " . trim($words[$a]);
                        }
                $count = 0;
                
                $entities      = queryTagMe($sentence);
                $foundEntities = array();
                if (count($entities["annotations"]) > 0)
                                foreach ($entities["annotations"] as $annotation)
                                        {
                                                if ($id != null && $id == $annotation["id"])
                                                                continue;
                                                @$foundEntities[$annotation["id"]]["title"] = $annotation["title"];
                                        }
                return $foundEntities;
                die;
        }
