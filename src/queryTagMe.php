<?php
include_once(__DIR__ . "/curlGet.php");
include_once(__DIR__ . "/../cfg/config.php");

function queryTagme($text)
        {
                global $TAGME_GCUBE_TOKEN;
                $c = curl_get("https://tagme.d4science.org/tagme/tag?lang=en&gcube-token=$TAGME_GCUBE_TOKEN&text=" . urlencode($text) . "&tweet=false&include_categories=false&include_all_spots=true&epsilon=0.5", true);
                $c = json_decode($c, true);
                return $c;
                print_r($c);
        }
