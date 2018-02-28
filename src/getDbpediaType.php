<?php
include_once(__DIR__ . "/curlGet.php");
include_once(__DIR__ . "/getFileName.php");
$typesMatch = array();
$f          = fopen(__DIR__ . "/../texts/typesDbpedia.txt", "r");
while (($line = fgets($f)) != NULL)
        {
                $line = str_replace("\n", "", $line);
                $line = explode("\t", $line);
                if ($line[2] != "?")
                                @$typesMatch[$line[1]][$line[2]] = $line[3];
                if ($line[2] == "?")
                                @$propertyMatch[$line[1]] = $line[3];
        }
function getDbpediaType(&$arr)
        {
                if (count($arr) == 0)
                                return array();
                global $typesMatch;
                global $propertyMatch;
                $typeProps  = array_keys($typesMatch);
                $typeString = "";
                foreach ($typesMatch as $predicate => $options)
                        {
                                foreach ($options as $object => $mapping)
                                        {
                                                $typeString .= "$object , ";
                                        }
                        }
                $returnedTypes = array();
                $str           = "";
                $startquery    = false;
                foreach ($arr as $a)
                        {
                                $a = str_replace(array(
                                                "&amp;",
                                                "&quot;",
                                                "&gt;",
                                                "&lt;"
                                ), array(
                                                "&",
                                                "\"",
                                                ">",
                                                "<"
                                ), $a);
                                $str .= "<http://dbpedia.org/resource/$a>, ";
                        }
                $typeString = trim(rtrim($typeString, " ,"), " ,");
                $str        = trim(rtrim($str, " ,"), " ,");
                $query      = "SELECT ?entity ?ty WHERE { ?entity rdf:type ?ty FILTER (?entity IN ($str)) . FILTER(?ty IN ($typeString)) } ";
                $query      = "http://dbpedia.org/sparql?default-graph-uri=http%3A%2F%2Fdbpedia.org&query=" . urlencode($query) . "&format=json&CXML_redir_for_subjs=121&CXML_redir_for_hrefs=&timeout=30000&debug=on";
                $s          = curl_get($query, true);
                $s          = rtrim(trim($s));
                $s          = json_decode($s, true);
                if (isset($s["results"]["bindings"]) && count($s["results"]["bindings"]) > 0)
                                foreach ($s["results"]["bindings"] as $bind)
                                        {
                                                $entity = $bind["entity"]["value"];
                                                $pos    = strrpos($entity, "/");
                                                $entity = substr($entity, $pos + 1, 99999999);
                                                $ty     = "<" . $bind["ty"]["value"] . ">";
                                                if (isset($typesMatch["rdf:type"][$ty]))
                                                                $returnedTypes[$entity] = $typesMatch["rdf:type"][$ty];
                                        }
                return $returnedTypes;
                print_r($returnedTypes);
                die;
                print_r($s);
                die;
                /*select ?entity ?ty
                where {
                ?entity rdf:type ?ty.
                FILTER (?entity IN (<http://dbpedia.org/resource/Toni_Braxton>, <http://dbpedia.org/resource/Donald_Trump>, <http://dbpedia.org/resource/Istanbul>)) .
                FILTER (?ty IN (foaf:Person, geo:SpatialThing))
                }*/
                
                
                /*@$returned$a);
                $md=substr($md,0,6).$md;
                $fileName=getFileName("typeCache",$a);
                if(file_exists ($fileName)&&filesize($fileName)>0)
                {
                @$returnedTypes[$a]=str_replace("\n","",file_get_contents($fileName));
                $typeS=explode(":",$returnedTypes[$a]);
                if(count($typeS)>1)
                {
                if(isset($typesMatch[$typeS[0]][$typeS[1]]))
                $returnedTypes[$a]=$typesMatch[$typeS[0]][$typeS[1]];
                }
                }
                else
                {
                $str.="|".$a;
                $startquery=true;
                }
                }
                if($startquery==false)
                return $returnedTypes;
                $str=urlencode(trim($str,"|"));
                $s=curl_get("https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&titles=$str&sites=enwiki&sitefilter=enwiki&props=sitelinks&languages=en",false);
                $json=json_decode($s,true);
                $ids=array();
                $idstring="";
                foreach($json["entities"] as $id =>$itm)
                {
                @$ids[$id]=str_replace(" ","_",$itm["sitelinks"]["enwiki"]["title"]);
                $idstring.=",".trim($id,"Q");
                }
                $idstring=urlencode(trim($idstring,","));
                $s2=curl_get("https://wdq.wmflabs.org/api?format=json&q=items[$idstring]&props=".$propString,false);
                //echo str_replace(array("[","]"),array("{","}"),$s2);die;
                $s2=str_replace("[],","",$s2);
                $s2=str_replace(",[]","",$s2);
                $s2=str_replace("[]","",$s2);
                $json=json_decode($s2,true);
                foreach($typeProps as $typeProp)
                {
                if(@count($json["props"][$typeProp])>0)
                foreach($json["props"][$typeProp] as $prop)
                {
                @$type=$typesMatch[$typeProp][$prop[2]];
                if(!isset($type)||$type=="")
                $type="";
                if($type!="")
                {
                @$returnedTypes[$ids["Q".$prop[0]]]=$type;
                $file=fopen(getFileName("typeCache",$ids["Q".$prop[0]]),"w");
                fwrite($file,$type);
                fclose($file);
                }
                else
                {
                $type="wikidata:".$prop[2];
                @$returnedTypes[$ids["Q".$prop[0]]]=$type;
                $file=fopen(getFileName("typeCache",$ids["Q".$prop[0]]),"w");
                fwrite($file,$type);
                fclose($file);
                }
                }
                }
                foreach($propertyMatch as $property=>$type)
                {
                if(@count($json["props"][$property])>0)
                foreach($json["props"][$property] as $prop)
                {
                @$returnedTypes[$ids["Q".$prop[0]]]=$type;
                $file=fopen(getFileName("typeCache",$ids["Q".$prop[0]]),"w");
                fwrite($file,$type);
                fclose($file);
                }    
                }
                */
                return $returnedTypes;
        }
function getDbpediaTypeLonger($arr)
        {
                $file = getFileName("dbpediaTypeCacheLonger", json_encode($arr));
                if (file_exists($file))
                                return json_decode(file_get_contents($file), true);
                $alreadyExists = array();
                foreach ($arr as $key => $entity)
                        {
                                $file1 = getFileName("dbpediaTypeCache", $entity);
                                if (file_exists($file1) && filesize($file1) > 0)
                                        {
                                                @$alreadyExists[$entity] = str_replace("\n", "", file_get_contents($file1));
                                                unset($arr[$key]);
                                        }
                        }
                $arr       = array_values($arr);
                $newArray  = array();
                $arraySize = count($arr);
                $tempArray = array();
                for ($a = 0; $a < $arraySize; $a++)
                        {
                                if ($a != 0 && $a % 50 == 0 || $a + 1 == $arraySize)
                                        {
                                                $results = getDbpediaType($tempArray);
                                                foreach ($results as $key => $value)
                                                        {
                                                                $fff = fopen(getFileName("dbpediaTypeCache", $key), "w");
                                                                fwrite($fff, $value);
                                                                fclose($fff);
                                                        }
                                                $tempArray = array();
                                                $newArray  = array_merge($newArray, $results);
                                        }
                                $tempArray[] = $arr[$a];
                        }
                $newArray = array_merge($newArray, $alreadyExists);
                $json     = json_encode($newArray, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
                $f        = fopen($file, "w");
                fwrite($f, $json);
                fclose($f);
                return $newArray;
        }
