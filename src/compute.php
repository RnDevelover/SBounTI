<?php
set_time_limit(0);
include_once(__DIR__ . "/../cfg/config.php");
include_once(__DIR__ . "/algorithm.php");

$LOOK_FOR_FIRST_PORTION_FOR_UNLINKEDPOST_UNIFICATION = 0.0008; // NOT USED FOR NOW


$ENTITY_UNIFICATION_THRESHOLD        = 0.001; // NOT DOCUMENTED
$ENTITY_UNIFICATION_THRESHOLD_HIGHER = 0.01; // NOT DOCUMENTED

$GLOBAL_VALUES = array();
$counterR      = 0;
$stopwords     = array();
$file          = fopen(__DIR__ . "/../texts/stopwords.txt", "rb");
while (!feof($file))
        {
                $stopwords[$counterR] = str_replace(array(
                                "\n",
                                "\r",
                                "\t"
                ), array(
                                "",
                                "",
                                ""
                ), fgets($file));
                $counterR++;
        }

include_once(__DIR__ . "/queryTagMe.php");
include_once(__DIR__ . "/getDbpediaType.php"); //WikidataType.php");
include_once(__DIR__ . "/getEntitiesFromWikipageTitleUsingPrepos.php");
include_once(__DIR__ . "/extractTemporalTerms.php");
include_once(__DIR__ . "/getWikipageNameGivenTwitterUsernameSparql.php");
///// LOAD TEXTS /////
$locationIndicatorsBefore = array();
$f                        = fopen(__DIR__ . "/../texts/locationIndicatorBefore.txt", "r");
while (($line = trim(fgets($f))) != NULL)
        {
                $locationIndicatorsBefore[] = $line;
        }
fclose($f);
$stopEntities = array();
$f            = fopen(__DIR__ . "/../texts/stopEntities.txt", "r");
while (($line = trim(fgets($f))) != NULL)
        {
                $stopEntities[] = $line;
        }
fclose($f);
$twitterStopwords = array();
$f                = fopen(__DIR__ . "/../texts/twitterStopwords.txt", "r");
while (($line = trim(fgets($f))) != NULL)
        {
                $twitterStopwords[] = $line;
        }
fclose($f);
//////// LOAD TEXTS END /////


$accordingToEntities = array();
$ENTITYCUTCOUNT      = 1500;
$entities            = array();
$tweetEntities       = array();
$tweetTemporalTerms  = array();
$tweetNo             = 0;
$stopwordArray       = array();

$finalTemporalTermDistribution    = array();
$AllEntities                      = array();
$AllOfTweets                      = array();
$tweetSpots                       = array();
$rawTweets                        = array();
$entityLocationIndicatorsInTweets = Array();
$allMentions                      = array();
$firstTimestamp                   = "";
$lastTimestamp                    = "";
$firstRead                        = false;
$entityFullyAppears               = array();
while (($js = trim(fgets(STDIN))) != NULL)
        {
                $rawTweet = json_decode($js, true);
                @$GLOBAL_VALUES["users"][$rawTweet["user"]]++;
                $rawTweets[] = $rawTweet;
                if (!$firstRead)
                        {
                                $firstTimestamp = $rawTweet["createdAt"];
                                $firstRead      = true;
                        }
                $lastTimestamp = $rawTweet["createdAt"];
                if (isset($rawTweet["mentions"]))
                                foreach ($rawTweet["mentions"] as $mention)
                                                $allMentions[$mention] = 1;
        }
$surfaceForms        = array();
$surfaceFormsReverse = array();
$TZ                  = date_default_timezone_get();
date_default_timezone_set('UTC');
$lastTimestamp  = date('Y-m-d\\TH:i:s\\Z', strtotime($lastTimestamp));
$firstTimestamp = date('Y-m-d\\TH:i:s\\Z', strtotime($firstTimestamp));
$nowTimestamp   = date('Y-m-d\\TH:i:s\\Z');
date_default_timezone_set($TZ);
$allMentions                      = array_keys($allMentions);
$wikipageNamesForTwitterUsernames = getWikipageNameGivenTwitterUsername($allMentions);

foreach ($wikipageNamesForTwitterUsernames as $key => $obj)
        {
                
                if (isset($obj["wikiPageTitle"]) && $obj["wikiPageTitle"] != "")
                                @$wikipageNameForTwitterUsername[$key] = $obj["wikiPageTitle"];
        }
unset($wikipageNamesForTwitterUsernames);
@$wikipageNameForTwitterUsernameKeys = array_keys($wikipageNameForTwitterUsername);
@$wikipageNameForTwitterUsernameValues = array_values($wikipageNameForTwitterUsername);
if (count($wikipageNameForTwitterUsernameKeys) > 0)
                foreach ($wikipageNameForTwitterUsernameKeys as $k => $vall)
                                $wikipageNameForTwitterUsernameKeys[$k] = "@" . $wikipageNameForTwitterUsernameKeys[$k];
if (count($wikipageNameForTwitterUsernameValues) > 0)
                foreach ($wikipageNameForTwitterUsernameValues as $k => $val)
                                $wikipageNameForTwitterUsernameValues[$k] = " " . $wikipageNameForTwitterUsernameValues[$k] . " ";

$wordToEntityMap = array();


$GLOBAL_VALUES["SpotsOfEntities"] = array();

$NumOfMentionReplaceTweeets = 0;
$NumOfPersonTweets          = 0;
$NumOfLocationTweets        = 0;
$NumOfTemporalTermTweets    = 0;
$NumOfUnlinkedSpotTweets    = 0;
foreach ($rawTweets as $js)
        {
                $text                = $js["text"];
                $retweetedStatusText = $js["retweetedStatusText"];
                
                $AllOfTweets[] = $text;
                $temporalTerms = extractTemporalTermsWithExpressions($text);
                if (count($temporalTerms) > 0)
                                $NumOfTemporalTermTweets++;
                $temporalTermsLowered = array_map('strtolower', $temporalTerms);
                foreach ($temporalTerms as $term)
                                @$finalTemporalTermDistribution[$term]++;
                $textForTagmeQuery = str_ireplace($wikipageNameForTwitterUsernameKeys, $wikipageNameForTwitterUsernameValues, $text);
                if ($textForTagmeQuery != $text)
                        {
                                $NumOfMentionReplaceTweeets++;
                        }
                $tags           = queryTagme($textForTagmeQuery);
                $entityArray    = array();
                $thisTweetSpots = array();
                if (count($tags["annotations"]) > 0)
                                foreach ($tags["annotations"] as $tag)
                                        {
                                                if (isMention($text, $tag["start"]))
                                                        {
                                                                continue;
                                                        }
                                                if (isset($tag["title"]) && in_array($tag["title"], $stopEntities))
                                                                continue;
                                                if ($tag["start"] > 0)
                                                        {
                                                                $startChar = mb_substr($textForTagmeQuery, $tag["start"] - 1, 1, "UTF-8");
                                                        }
                                                else
                                                                $startChar = " ";
                                                if (trim($startChar) != "")
                                                                continue;
                                                if ($tag["end"] < mb_strlen($textForTagmeQuery, "UTF-8"))
                                                        {
                                                                $endChar = mb_substr($textForTagmeQuery, $tag["end"], 1, "UTF-8");
                                                        }
                                                else
                                                                $endChar = " ";
                                                if (trim($endChar) != "")
                                                                continue;
                                                $tagSpot        = mb_strtolower($tag["spot"], "UTF-8");
                                                $tagSpotLowered = str_replace(" ", "", $tagSpot);
                                                if (in_array($tagSpotLowered, $stopwordArray) || in_array($tagSpot, $stopwordArray) || in_array($tagSpotLowered, $twitterStopwords) || in_array($tagSpot, $twitterStopwords) || in_array($tagSpotLowered, $stopwords) || in_array($tagSpot, $stopwords) || strlen($tagSpot) < 2 || is_numeric($tagSpot) || in_array($tagSpotLowered, $temporalTermsLowered))
                                                                continue;
                                                $allItemsAreStopWords = true;
                                                $itemsOfTag           = preg_split('/[^a-z0-9]/i', $tagSpot);
                                                foreach ($itemsOfTag as $itemOfTag)
                                                        {
                                                                if (strlen($itemOfTag) < 3)
                                                                                continue;
                                                                if (in_array($itemOfTag, $stopwords) || in_array($itemOfTag, $twitterStopwords) || in_array($itemOfTag, $stopwordArray))
                                                                        {
                                                                        }
                                                                else
                                                                        {
                                                                                $allItemsAreStopWords = false;
                                                                        }
                                                        }
                                                if ($allItemsAreStopWords)
                                                                continue;
                                                // DISCARD tags with () if not related
                                                if (isset($tag["title"]))
                                                        {
                                                                $pos1 = mb_strpos($tag["title"], ")", 0, "UTF-8");
                                                                $pos2 = mb_strpos($tag["title"], "(", 0, "UTF-8");
                                                                if ($pos1 === false || $pos2 === false)
                                                                        {
                                                                        }
                                                                else
                                                                        {
                                                                                if ($pos2 < $pos1)
                                                                                        {
                                                                                                $smaller = $pos2;
                                                                                                $bigger  = $pos1;
                                                                                        }
                                                                                else
                                                                                        {
                                                                                                $smaller = $pos1;
                                                                                                $bigger  = $pos2;
                                                                                        }
                                                                                $str1 = mb_substr($tag["title"], 0, $smaller, "UTF-8");
                                                                                $str2 = mb_substr($tag["title"], $bigger + 1, 999999, "UTF-8");
                                                                                if (strlen($str1) < 3)
                                                                                                continue;
                                                                                $items1   = preg_split('/[^a-z0-9]/i', mb_strtolower($str1, "UTF-8"));
                                                                                $items2   = $itemsOfTag;
                                                                                $suitable = false;
                                                                                foreach ($items1 as $item1)
                                                                                        {
                                                                                                if (mb_strlen($item1, "UTF-8") < 3)
                                                                                                                continue;
                                                                                                foreach ($items2 as $item2)
                                                                                                        {
                                                                                                                if (mb_strlen($item2, "UTF-8") < 3)
                                                                                                                                continue;
                                                                                                                if (levenshtein($item1, $item2) < 4)
                                                                                                                        {
                                                                                                                                $suitable = true;
                                                                                                                                break;
                                                                                                                        }
                                                                                                                if ($suitable)
                                                                                                                                break;
                                                                                                        }
                                                                                        }
                                                                                if (!$suitable)
                                                                                                continue;
                                                                        }
                                                        }
                                                if ($tag["link_probability"] < $TAGME_LINK_PROBABILITY_THRESHOLD && $tag["rho"] < $TAGME_RHO_THRESHOLD)
                                                        {
                                                                if (isset($tag["title"]))
                                                                        {
                                                                                @$surfaceForms[mb_strtolower($tagSpot, "UTF-8")][$tag["title"]]++;
                                                                                @$surfaceFormsReverse[$tag["title"]][mb_strtolower($tagSpot, "UTF-8")]++;
                                                                        }
                                                                $thisTweetSpots[] = $tagSpot;
                                                        }
                                                else
                                                        {
                                                                if (isset($tag["title"]))
                                                                        {
                                                                                $tempTerms    = extractTemporalTerms($tag["title"]);
                                                                                $mustContinue = false;
                                                                                if (count($tempTerms) > 0)
                                                                                        {
                                                                                                foreach ($tempTerms as $tempTerm)
                                                                                                        {
                                                                                                                if (mb_strpos($text, $tempTerm, 0, "UTF-8") === false)
                                                                                                                        {
                                                                                                                                $mustContinue = true;
                                                                                                                                break;
                                                                                                                        }
                                                                                                                else
                                                                                                                        {
                                                                                                                        }
                                                                                                        }
                                                                                                if ($mustContinue)
                                                                                                                continue;
                                                                                        }
                                                                        }
                                                                else
                                                                        {
                                                                                @$tag["title"] = "";
                                                                        }
                                                                @$ent = str_replace(" ", "_", $tag["title"]);
                                                                $seperatorText = str_replace("_", " ", mb_strtolower($ent, "UTF-8"));
                                                                if ($seperatorText != "")
                                                                        {
                                                                                $exitsInText = mb_strpos(mb_strtolower($text, "UTF-8"), $seperatorText, 0, "UTF-8");
                                                                        }
                                                                else
                                                                                $existsInText = false;
                                                                if ($exitsInText === false)
                                                                        {
                                                                        }
                                                                else
                                                                        {
                                                                                @$entityFullyAppears[$ent]++;
                                                                        }
                                                                if (in_array($ent, $stopEntities) || strlen($ent) < 2)
                                                                                continue;
                                                                @$entityArray[$ent] = 1;
                                                                @$AllEntities[$ent]++;
                                                                @$accordingToEntities[$ent][] = $text;
                                                                @$entityLocationIndicatorsInTweets[$tweetNo][$ent] = hasLocationIndicator($tag["start"], $textForTagmeQuery);
                                                                @$GLOBAL_VALUES["SpotsOfEntities"][$ent][$tag["spot"]] = 1;
                                                        }
                                        }
                foreach ($thisTweetSpots as $thisSpot)
                                @$tweetSpots[$thisSpot]++;
                $allTweetSpots[$tweetNo]      = $thisTweetSpots;
                $tweetEntities[$tweetNo]      = array_keys($entityArray);
                $tweetTemporalTerms[$tweetNo] = $temporalTerms;
                
                
                
                $tweetNo++;
        }
arsort($tweetSpots);
arsort($AllEntities);
@$GLOBAL_VALUES["TweetSpotsBefore"] = $tweetSpots;
@$GLOBAL_VALUES["AllEntitiesBefore"] = $AllEntities;
@$GLOBAL_VALUES["TemporalTerms"] = $finalTemporalTermDistribution;
@$GLOBAL_VALUES["MetionWikiMaps"] = $wikipageNameForTwitterUsername;
@$GLOBAL_VALUES["NumOfMentionReplaceTweeets"] = $NumOfMentionReplaceTweeets;

$entityUnificationLowerThreshold  = $tweetNo * $ENTITY_UNIFICATION_THRESHOLD;
$entityunificationHigherThreshold = $tweetNo * $ENTITY_UNIFICATION_THRESHOLD_HIGHER;
$entityForms                      = array();
foreach ($AllEntities as $entityTitle => $entityVal)
        {
                $words = preg_split('/[^[:alnum:]]+/', mb_strtolower($entityTitle, "UTF-8"));
                foreach ($words as $keyW => $vW)
                                if (mb_strlen($vW) < 3)
                                                unset($words[$keyW]);
                $entityForms[$entityTitle] = array_values($words);
        }
$entityExchange = array();
foreach ($AllEntities as $entityTitle => $entityVal)
        {
                if ($entityVal < $entityUnificationLowerThreshold)
                        {
                                $count1 = count($entityForms[$entityTitle]);
                                if ($count1 > 3)
                                                continue;
                                if (isset($entityFullyAppears[$entityTitle]))
                                                $appears = $entityFullyAppears[$entityTitle];
                                else
                                                $appears = 0;
                                if ($appears / $entityVal > 0.7 && $count1 > 1)
                                                continue;
                                foreach ($AllEntities as $entityTitle1 => $entityVal1)
                                        {
                                                $count2 = count($entityForms[$entityTitle1]);
                                                if ($entityVal1 >= $entityunificationHigherThreshold && $count2 < 4)
                                                        {
                                                                $numOfIntersect = count(array_intersect($entityForms[$entityTitle], $entityForms[$entityTitle1]));
                                                                $numOfUnion     = count($entityForms[$entityTitle]) + count($entityForms[$entityTitle1]) - $numOfIntersect;
                                                                if ($numOfUnion > 0 && $numOfIntersect / $numOfUnion > 0.3)
                                                                        {
                                                                                $entityExchange[$entityTitle] = $entityTitle1;
                                                                        }
                                                        }
                                                else
                                                                break;
                                        }
                        }
        }
$GLOBAL_VALUES["NumOfPostsEntityExchange"] = 0;
foreach ($entityExchange as $oldd => $neww)
        {
                $AllEntities[$neww] += $AllEntities[$oldd];
                unset($AllEntities[$oldd]);
                for ($a = 0; $a < $tweetNo; $a++)
                        {
                                foreach ($tweetEntities[$a] as $kEn => $kV)
                                                if ($kV == $oldd)
                                                        {
                                                                unset($tweetEntities[$a][$kEn]);
                                                                $GLOBAL_VALUES["NumOfPostsEntityExchange"]++;
                                                                $tweetEntities[$a][] = $neww;
                                                                $tweetEntities[$a]   = array_unique($tweetEntities[$a]);
                                                                break;
                                                        }
                        }
        }
$GLOBAL_VALUES["EntityExchange"]         = $entityExchange;
//// POST-PROCESSING: UNIFICATION OF UNLINKED SPOTS:
$levenArray1                             = array();
$levenArray2                             = array();
$tweetSpotsLookTh                        = $LOOK_FOR_FIRST_PORTION_FOR_UNLINKEDPOST_UNIFICATION * $tweetNo * 2.4;
$GLOBAL_VALUES["NumOfPostsSpotExchange"] = 0;
foreach ($tweetSpots as $tsts1 => $valval1)
        {
                if ($valval1 < $tweetSpotsLookTh)
                                break;
                if (mb_strlen($tsts1) < 5)
                                continue;
                foreach ($tweetSpots as $tsts2 => $valval2)
                        {
                                if ($valval2 < $tweetSpotsLookTh)
                                                break;
                                if ($tsts1 == $tsts2)
                                                continue;
                                if ($tsts1 . "s" == $tsts2 || $tsts2 . "s" == $tsts1)
                                        {
                                        }
                                else if (mb_strlen($tsts2) < 5)
                                                continue;
                                if (levenshtein($tsts1, $tsts2) < 2)
                                        {
                                                if ($valval2 < $valval1)
                                                        {
                                                                $kKk    = $tsts2;
                                                                $kKkVal = $valval2;
                                                                $bBb    = $tsts1;
                                                                $bBbVal = $valval1;
                                                        }
                                                else
                                                        {
                                                                $kKk    = $tsts1;
                                                                $kKkVal = $valval1;
                                                                $bBb    = $tsts2;
                                                                $bBbVal = $valval2;
                                                        }
                                                $levenArray1[$kKk] = $bBb;
                                                $levenArray2[$bBb] = $kKk;
                                                @$tweetSpots[$bBb] += $kKkVal;
                                                unset($tweetSpots[$kKk]);
                                                @$GLOBAL_VALUES["SpotExchange"][$kKk] = $bBb;
                                                for ($ss = 0; $ss < $tweetNo; $ss++)
                                                        {
                                                                if (!in_array($kKk, $allTweetSpots[$ss]))
                                                                                continue;
                                                                foreach ($allTweetSpots[$ss] as $kika => $mkmk)
                                                                                if ($mkmk == $kKk)
                                                                                                unset($allTweetSpots[$ss][$kika]);
                                                                $allTweetSpots[$ss][] = $bBb;
                                                                $allTweetSpots[$ss]   = array_values(array_unique($allTweetSpots[$ss]));
                                                                $GLOBAL_VALUES["NumOfPostsSpotExchange"]++;
                                                        }
                                        }
                        }
        }
/*echo $tweetSpots["temperament"]."\n";
die;*/
$GLOBAL_VALUES["NumOfPostsSpotExchangeNew"] = 0;
foreach ($tweetSpots as $spotText => $valueOfSpot)
        {
                foreach ($AllEntities as $entityText => $valueOfEntity)
                        {
                                $spotTextNew   = mb_strtolower($spotText, "UTF-8");
                                $entityTextNew = str_replace("_", " ", mb_strtolower($entityText, "UTF-8"));
                                if ($entityTextNew == $spotTextNew)
                                        {
                                                
                                                $AllEntities[$entityText] += $valueOfSpot;
                                                unset($tweetSpots[$spotText]);
                                                @$GLOBAL_VALUES["linkSpots"][$spotText] = $entityText;
                                                for ($a = 0; $a < $tweetNo; $a++)
                                                        {
                                                                $accountedTweet = false;
                                                                for ($b = 0; $b < count($allTweetSpots[$a]); $b++)
                                                                        {
                                                                                if ($allTweetSpots[$a][$b] == $spotTextNew)
                                                                                        {
                                                                                                if (!$accountedTweet)
                                                                                                        {
                                                                                                                $accountedTweet = true;
                                                                                                                $GLOBAL_VALUES["NumOfPostsSpotExchangeNew"]++;
                                                                                                        }
                                                                                                unset($allTweetSpots[$a][$b]);
                                                                                                $tweetEntities[$a][] = $entityText;
                                                                                                //                        $accordingToEntities[$entityText][]=$AllOfTweets[$a];
                                                                                        }
                                                                        }
                                                                $tweetEntities[$a] = array_values(array_unique($tweetEntities[$a]));
                                                                $allTweetSpots[$a] = array_values(array_unique($allTweetSpots[$a]));
                                                        }
                                        }
                        }
        }
//print_r($allTweetSpots);
//die;
$entityCheckArray = array();
foreach ($AllEntities as $entEnt => $valval)
        {
                if ($valval < $tweetSpotsLookTh)
                                break;
                $arrArr = preg_split('/[^[:alnum:]]+/', mb_strtolower($entEnt, 'UTF-8'));
                foreach ($arrArr as $keyKey => $valValVal)
                                if (mb_strlen($valValVal, "UTF-8") < 3)
                                                unset($arrArr[$keyKey]);
                $entityCheckArray[$entEnt] = array_values($arrArr);
        }
foreach ($tweetSpots as $tsts => $valval)
        {
                $tsArr = preg_split('/[^[:alnum:]]+/', mb_strtolower($tsts, 'UTF-8'));
                foreach ($tsArr as $tsKey => $tsKw)
                        {
                                if (mb_strlen($tsKw, "UTF-8") < 3)
                                                unset($tsArr[$tsKey]);
                        }
                foreach ($entityCheckArray as $keyKey => $entCheckArr)
                        {
                                $matchingTsCount = 0;
                                foreach ($tsArr as $tsKw)
                                        {
                                                if (in_array($tsKw, $entCheckArr))
                                                                $matchingTsCount++;
                                        }
                                $unionCount = count($tsArr) + count($entCheckArr) - $matchingTsCount;
                                if ($unionCount > 0 && $matchingTsCount / $unionCount > 0.2)
                                        {
                                                unset($tweetSpots[$tsts]);
                                                $AllEntities[$keyKey] += $valval;
                                                @$GLOBAL_VALUES["SpotEntityMatchFix"][$tsts] = $keyKey;
                                                for ($ss = 0; $ss < $tweetNo; $ss++)
                                                        {
                                                                if (in_array($tsts, $allTweetSpots[$ss]))
                                                                        {
                                                                                @$GLOBAL_VALUES["SpotEntityMatchFix"][$mkmk][$keyKey];
                                                                                if (!in_array($keyKey, $tweetEntities[$ss]))
                                                                                                @$tweetEntities[$ss][] = $keyKey;
                                                                                foreach ($allTweetSpots[$ss] as $kika => $mkmk)
                                                                                                if ($mkmk == $tsts)
                                                                                                                unset($allTweetSpots[$ss][$kika]);
                                                                                $allTweetSpots[$ss] = array_values($allTweetSpots[$ss]);
                                                                        }
                                                        }
                                        }
                        }
        }
/// RESET ALL RELATED DATA
$accordingToEntities = array();
$accordingToSpots    = array();
for ($a = 0; $a < $tweetNo; $a++)
        {
                for ($b = 0; $b < count($allTweetSpots[$a]); $b++)
                        {
                                @$accordingToSpots[$allTweetSpots[$a][$b]][] = $AllOfTweets[$a];
                        }
                for ($b = 0; $b < count($tweetEntities[$a]); $b++)
                        {
                                @$accordingToEntities[$tweetEntities[$a][$b]][] = $AllOfTweets[$a];
                        }
        }


$surfaceReplaces = array();
foreach ($surfaceForms as $surfForm => $ents)
        {
                $theSames     = array();
                $mostFreq     = -10000;
                $mostSelected = "";
                $selected     = "";
                foreach ($ents as $ent => $repVal)
                        {
                                $ent = str_replace(" ", "_", $ent);
                                if ($repVal > $mostFreq)
                                        {
                                                $mostFreq     = $repVal;
                                                $mostSelected = $ent;
                                                $selected     = $ent;
                                                @$theSames[$ent] = $repVal;
                                        }
                                else if ($repVal == $mostFreq)
                                        {
                                                @$theSames[$ent] = $repVal;
                                        }
                        }
                
                $mostFreq     = -1000;
                $mostSelected = "";
                if (count($theSames) > 1)
                        {
                                foreach ($theSames as $ent => $repVal)
                                        {
                                                if (isset($AllEntities[$ent]) && $AllEntities[$ent] > $mostFreq)
                                                        {
                                                                $mostFreq     = $AllEntities[$ent];
                                                                $mostSelected = $ent;
                                                        }
                                        }
                                if ($mostSelected != "")
                                                @$surfaceReplaces[$surfForm] = $mostSelected;
                        }
                else
                        {
                                @$surfaceReplaces[$surfForm] = $selected;
                        }
                
                if ($mostSelected != "")
                        {
                                @$surfaceReplaces[$surfForm] = $ent;
                                continue;
                        }
        }

foreach ($surfaceReplaces as $surf => $repl)
                if ($repl == 0 || $repl == "")
                                unset($surfaceReplaces[$surf]);
for ($a = 0; $a < $tweetNo; $a++)
        {
                foreach ($allTweetSpots[$a] as $spKey => $twSpot)
                        {
                                if (in_array($twSpot, array_keys($surfaceReplaces)))
                                        {
                                                if (!isset($AllEntities[$surfaceReplaces[$twSpot]]))
                                                                continue;
                                                $tweetEntities[$a][] = $surfaceReplaces[$twSpot];
                                                $tweetEntities[$a]   = array_values(array_unique($tweetEntities[$a]));
                                                unset($allTweetSpots[$a][$spKey]);
                                                $tweetSpots[$twSpot]--;
                                                $AllEntities[$surfaceReplaces[$twSpot]]++;
                                                if (is_array($allTweetSpots[$a]))
                                                                $allTweetSpots[$a] = array_values($allTweetSpots[$a]);
                                                $a--;
                                        }
                        }
        }
foreach ($surfaceReplaces as $twSpot => $replaceEntity)
        {
                for ($a = 0; $a < $tweetNo; $a++)
                        {
                                $pos = mb_strpos(mb_strtolower($AllOfTweets[$a], "UTF-8"), mb_strtolower($twSpot, "UTF-8"), 0, "UTF-8");
                                if ($pos === false)
                                                continue;
                                else
                                        {
                                                if (!isset($AllEntities[$replaceEntity]))
                                                                continue;
                                                $c1                  = count($tweetEntities[$a]);
                                                $tweetEntities[$a][] = $replaceEntity;
                                                $tweetEntities[$a]   = array_values(array_unique($tweetEntities[$a]));
                                                if (count($tweetEntities[$a]) > $c1)
                                                        {
                                                                $AllEntities[$replaceEntity]++;
                                                        }
                                        }
                        }
        }

@$GLOBAL_VALUES["SurfaceReplaces"] = $surfaceReplaces;
@$GLOBAL_VALUES["TweetSpotsAfter"] = $tweetSpots;
@$GLOBAL_VALUES["AllEntitiesAfter"] = $AllEntities;


///////// TEMPORAL TERMS DISTRIBUTION

$temporalTermsDistribution = array();
foreach ($tweetTemporalTerms as $tweetId => $terms)
        {
                foreach ($terms as $term)
                        {
                                if (substr($term, 0, 5) != "year:")
                                                continue;
                                @$temporalTermsDistribution[$term]++;
                        }
        }
arsort($temporalTermsDistribution);
//print_r($temporalTermsDistribution);
$validTemporalTerms = array();
$aa                 = 0;
foreach ($temporalTermsDistribution as $tempTerm => $val)
        {
                $aa++;
                @$validTemporalTerms[$tempTerm] = 1;
                if ($aa == 5)
                                break;
        }
@$validTemporalTerms["year:" . $TEMPORALCONTEXT] = 1;

$counter = 0;
$count   = 0;
$urls    = array();
arsort($AllEntities);
$ccc              = 0;
$forTypeResolving = array();
foreach ($AllEntities as $en => $val)
        {
                if ($ccc == $ENTITYCUTCOUNT)
                                break;
                @$forTypeResolving[] = $en;
                $ccc++;
        }
$entityTyps                                   = getDbpediaTypeLonger($forTypeResolving);
$GLOBAL_VALUES["LocationsAccordingToDbPedia"] = array();
foreach ($entityTyps as $enty => $typp)
        {
                if ($typp == "location")
                                @$GLOBAL_VALUES["LocationsAccordingToDbPedia"][$enty] = $AllEntities[$enty];
                if ($typp != "person" && $typp != "location")
                                unset($entityTyps[$enty]);
        }
$tweetPersonArray   = array();
$tweetLocationArray = array();

$otherEntities = array();
foreach ($entityTyps as $enty => $typp)
        {
                if ($typp != "person" && $typp != "location")
                        {
                                @$otherEntities[$enty] = $typp;
                                unset($entityTyps[$enty]);
                        }
        }
$tweetPersonArray   = array();
$tweetLocationArray = array();
$numPersons         = array();
$numLocations       = Array();

for ($a = 0; $a < $tweetNo; $a++)
        {
                if (count($allTweetSpots[$a]) > 0)
                                $NumOfUnlinkedSpotTweets++;
                
        }

$numOfOtherTweets = 0;
for ($a = 0; $a < count($tweetEntities); $a++)
        {
                @$tweetLocationArray[$a] = array();
                @$tweetPersonArray[$a] = array();
                $isPerson   = false;
                $isLocation = false;
                $isOther    = false;
                foreach ($tweetEntities[$a] as $ent)
                        {
                                @$typeOfEntity = $entityTyps[$ent];
                                if ($typeOfEntity == "location")
                                        {
                                                $isLocation = true;
                                                @$numLocations[$ent]++;
                                                @$tweetLocationArray[$a][] = $ent;
                                        }
                                else if ($typeOfEntity == "person")
                                        {
                                                $isPerson = true;
                                                @$numPersons[$ent]++;
                                                @$tweetPersonArray[$a][] = $ent;
                                        }
                                else
                                                $isOther = true;
                        }
                if ($isPerson)
                                $NumOfPersonTweets++;
                if ($isLocation)
                                $NumOfLocationTweets++;
                if ($isOther)
                                $numOfOtherTweets++;
        }

arsort($AllEntities);
arsort($finalTemporalTermDistribution);
arsort($numPersons);
arsort($numLocations);
@$GLOBAL_VALUES["NumOfPersons"] = $numPersons;
@$GLOBAL_VALUES["NumOfLocations"] = $numLocations;
@$GLOBAL_VALUES["NumOfTweetsWithPerson"] = $NumOfPersonTweets;
@$GLOBAL_VALUES["NumOfTweetsWithLocation"] = $NumOfLocationTweets;
@$GLOBAL_VALUES["NumOfTweetsWithTemporalTerm"] = $NumOfTemporalTermTweets;
@$GLOBAL_VALUES["NumOfTweetsWithUnlinkedSpot"] = $NumOfUnlinkedSpotTweets;
@$GLOBAL_VALUES["NumOfTweetsWithOtherTypes"] = $numOfOtherTweets;

$RDFs           = array();
$semanticTopics = array();

arsort($tweetSpots);
$topics = array();

$newApproachTopics = algorithm($topics, $tweetNo, $numPersons, $numLocations, $AllEntities, array_keys($validTemporalTerms), $tweetTemporalTerms, $tweetSpots, $allTweetSpots, $tArray, $accordingToEntities, $AllOfTweets, $tweetEntities, $finalTemporalTermDistribution, $entityLocationIndicatorsInTweets, $firstTimestamp, $lastTimestamp, $nowTimestamp);

$output = array(
                "numOfTweets" => $tweetNo,
                "validTemporalTerms" => array_keys($validTemporalTerms),
                "personEntities" => $numPersons,
                "locationEntities" => $numLocations,
                "allEntities" => $AllEntities,
                "finalTemporalTermDistribution" => $finalTemporalTermDistribution,
                "semanticTopics" => $newApproachTopics,
                "RDFs" => $RDFs,
                "tweetsAccordingToEntities" => $accordingToEntities,
                "AllTweets" => $AllOfTweets,
                "Spots" => $allTweetSpots,
                "tweetsAccordingToSpots" => $accordingToSpots,
                "GlobalValues" => $GLOBAL_VALUES
);
echo json_encode($output, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
die;


function hasLocationIndicator($start, $textForTagmeQuery)
        {
                global $locationIndicatorsBefore;
                return in_array(mb_strtolower(end(preg_split('/\s+/', trim(rtrim(mb_substr($textForTagmeQuery, 0, $start, "UTF-8"))))), "UTF-8"), $locationIndicatorsBefore);
        }

?>
