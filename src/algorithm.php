<?php
include_once(__DIR__ . "/../cfg/config.php");
$SPOT_RELATION_THRESHOLD      = 0.0005; //  NOT USED FOR NOW
$SPOT_RELATION_THRESHOLD_WEAK = 0.001;

function areElementsRelated($elm1, $elm2, &$tweetEntities, &$tweetSpots, &$tweetTemporalTerms, $numOfTweets)
        {
                global $NODE_RELATION_THRESHOLD_WEAK;
                global $SPOT_RELATION_THRESHOLD_WEAK;
                global $temporalReferenceGroups;
                $sameTemporalReferences =& $temporalReferenceGroups;
                $thNode         = $numOfTweets * $NODE_RELATION_THRESHOLD_WEAK;
                $thSpot         = $numOfTweets * $SPOT_RELATION_THRESHOLD_WEAK;
                $numOfOccurence = 0;
                if (substr($elm1, 0, 5) == "spot:")
                        {
                                $elm1     = str_replace("_", " ", substr($elm1, 5, 999999));
                                $elm1Type = "spot";
                        }
                else if (substr($elm1, 0, 13) == "temporalterm:")
                        {
                                $elm1     = substr($elm1, 13, 99999);
                                $elm1Type = "temporalterm";
                        }
                else
                                $elm1Type = "";
                
                if (substr($elm2, 0, 5) == "spot:")
                        {
                                $elm2     = str_replace("_", " ", substr($elm2, 5, 999999));
                                $elm2Type = "spot";
                        }
                else if (substr($elm2, 0, 13) == "temporalterm:")
                        {
                                $elm2     = substr($elm2, 13, 99999);
                                $elm2Type = "temporalterm";
                        }
                else
                                $elm2Type = "";
                
                if ($elm1Type == "temporalterm" && $elm2Type == "temporalterm")
                        {
                                foreach ($sameTemporalReferences as $group)
                                        {
                                                if (in_array($elm1, $group) && in_array($elm2, $group))
                                                        {
                                                                return true;
                                                        }
                                        }
                        }
                
                
                for ($a = 0; $a < $numOfTweets; $a++)
                        {
                                $foundOne = false;
                                $foundTwo = false;
                                if ($elm1Type == "spot")
                                        {
                                                if (in_array($elm1, $tweetSpots[$a]))
                                                                $foundOne = true;
                                        }
                                else if ($elm1Type == "temporalterm")
                                        {
                                                foreach ($sameTemporalReferences as $group)
                                                        {
                                                                if (in_array($elm1, $group))
                                                                        {
                                                                                $ff = false;
                                                                                foreach ($group as $ellm)
                                                                                        {
                                                                                                if (in_array($ellm, $tweetTemporalTerms[$a]))
                                                                                                                $ff = true;
                                                                                                break;
                                                                                        }
                                                                                if ($ff)
                                                                                        {
                                                                                                $foundOne = true;
                                                                                                break;
                                                                                        }
                                                                        }
                                                        }
                                                if (in_array($elm1, $tweetTemporalTerms[$a]))
                                                                $foundOne = true;
                                        }
                                else if (in_array($elm1, $tweetEntities[$a]))
                                                $foundOne = true;
                                if ($elm2Type == "spot")
                                        {
                                                if (in_array($elm2, $tweetSpots[$a]))
                                                                $foundTwo = true;
                                        }
                                else if ($elm2Type == "temporalterm")
                                        {
                                                foreach ($sameTemporalReferences as $group)
                                                        {
                                                                if (in_array($elm2, $group))
                                                                        {
                                                                                $ff = false;
                                                                                foreach ($group as $ellm)
                                                                                        {
                                                                                                if (in_array($ellm, $tweetTemporalTerms[$a]))
                                                                                                                $ff = true;
                                                                                                break;
                                                                                        }
                                                                                if ($ff)
                                                                                        {
                                                                                                $foundTwo = true;
                                                                                                break;
                                                                                        }
                                                                        }
                                                        }
                                                if (in_array($elm2, $tweetTemporalTerms[$a]))
                                                                $foundTwo = true;
                                        }
                                else if (in_array($elm2, $tweetEntities[$a]))
                                                $foundTwo = true;
                                if ($foundOne && $foundTwo)
                                                $numOfOccurence++;
                        }
                //echo $elm1." ".$elm2." ".$numOfOccurence."\n";
                if ($elm2Type == "spot" || $elm1Type == "spot")
                                return $numOfOccurence > $thSpot;
                else
                                return $numOfOccurence > $thNode;
        }



function algorithm(&$BOUNTITopics, $numOfTweets, &$persons, &$locations, &$allEntities, &$temporalTerms, &$temporalTermsInTweets, &$spots, &$spotsInTweets, &$tokenArray, &$tweetsAccordingToEntities, &$allTweets, &$tweetEntities, &$allTemporalTerms, $entityLocationIndicatorsInTweets, $startTimestamp, $endTimestamp, $nowTimestamp)
        {
                global $temporalReferenceGroups;
                global $LOCATION_CONSIDERATION_TH;
                global $NODE_RELATION_THRESHOLD;
                global $SPOT_RELATION_THRESHOLD;
                global $TOPIC_MERGE_DIFFERENCE_TH;
                global $STRONG_CONN_RATIO;
                global $GLOBAL_VALUES;
                $sameTemporalReferences =& $temporalReferenceGroups;
                $validLocations          = array();
                $locationConTh           = $numOfTweets * $LOCATION_CONSIDERATION_TH;
                $locationIndicatorCounts = array();
                $strongConnRatio         = $numOfTweets * $STRONG_CONN_RATIO;
                for ($a = 0; $a < count($entityLocationIndicatorsInTweets); $a++)
                        {
                                if (isset($entityLocationIndicatorsInTweets[$a]))
                                                foreach ($entityLocationIndicatorsInTweets[$a] as $key => $value)
                                                        {
                                                                if ($value == "1")
                                                                                @$locationIndicatorCounts[$key]++;
                                                        }
                        }
                foreach ($locationIndicatorCounts as $key => $val)
                        {
                                if (isset($locations[$key]))
                                                if ($val > $locationConTh)
                                                                $validLocations[$key] = 1;
                        }
                @$GLOBAL_VALUES["LocationIndicatorCounts"] = $locationIndicatorCounts;
                @$GLOBAL_VALUES["ValidLocations"] = array_keys($validLocations);
                $relationThreshold     = $numOfTweets * $NODE_RELATION_THRESHOLD;
                $spotRelationThreshold = $numOfTweets * $SPOT_RELATION_THRESHOLD;
                $edges                 = array();
                $foundTemporalTerms    = array();
                for ($a = 0; $a < $numOfTweets; $a++)
                        {
                                for ($b = 0; $b < count($tweetEntities[$a]); $b++)
                                        {
                                                for ($c = 0; $c < count($tweetEntities[$a]); $c++)
                                                        {
                                                                if ($b >= $c)
                                                                                continue;
                                                                if (strcmp($tweetEntities[$a][$b], $tweetEntities[$a][$c]) < 0)
                                                                                @$edges[$tweetEntities[$a][$b]][$tweetEntities[$a][$c]]++;
                                                                else
                                                                                @$edges[$tweetEntities[$a][$c]][$tweetEntities[$a][$b]]++;
                                                        }
                                                for ($c = 0; $c < count($spotsInTweets[$a]); $c++)
                                                        {
                                                                $spot = "spot:" . str_replace(" ", "_", $spotsInTweets[$a][$c]);
                                                                if (strcmp($tweetEntities[$a][$b], $spot) < 0)
                                                                                @$edges[$tweetEntities[$a][$b]][$spot]++;
                                                                else
                                                                                @$edges[$spot][$tweetEntities[$a][$b]]++;
                                                        }
                                                for ($c = 0; $c < count($temporalTermsInTweets[$a]); $c++)
                                                        {
                                                                $temporalterm                      = "temporalterm:" . $temporalTermsInTweets[$a][$c];
                                                                $foundTemporalTerms[$temporalterm] = 1;
                                                                if (strcmp($tweetEntities[$a][$b], $temporalterm) < 0)
                                                                                @$edges[$tweetEntities[$a][$b]][$temporalterm]++;
                                                                else
                                                                                @$edges[$temporalterm][$tweetEntities[$a][$b]]++;
                                                        }
                                        }
                                for ($b = 0; $b < count($spotsInTweets[$a]); $b++)
                                        {
                                                $spot1 = "spot:" . str_replace(" ", "_", $spotsInTweets[$a][$b]);
                                                for ($c = 0; $c < count($spotsInTweets[$a]); $c++)
                                                        {
                                                                if ($b >= $c)
                                                                                continue;
                                                                $spot2 = "spot:" . str_replace(" ", "_", $spotsInTweets[$a][$c]);
                                                                if (strcmp($spot1, $spot2) < 0)
                                                                                @$edges[$spot1][$spot2]++;
                                                                else
                                                                                @$edges[$spot2][$spot1]++;
                                                        }
                                                for ($c = 0; $c < count($temporalTermsInTweets[$a]); $c++)
                                                        {
                                                                $temporalterm                      = "temporalterm:" . $temporalTermsInTweets[$a][$c];
                                                                $foundTemporalTerms[$temporalterm] = 1;
                                                                if (strcmp($spot1, $temporalterm) < 0)
                                                                                @$edges[$spot1][$temporalterm]++;
                                                                else
                                                                                @$edges[$temporalterm][$spot1]++;
                                                        }
                                        }
                                for ($b = 0; $b < count($temporalTermsInTweets[$a]); $b++)
                                        {
                                                $temporalTerm1                      = $temporalTermsInTweets[$a][$b];
                                                $foundTemporalTerms[$temporalTerm1] = 1;
                                                for ($c = 0; $c < count($temporalTermsInTweets[$a]); $c++)
                                                        {
                                                                if ($b >= $c)
                                                                                continue;
                                                                $temporalTerm2                      = $temporalTermsInTweets[$a][$c];
                                                                $foundTemporalTerms[$temporalTerm2] = 1;
                                                                if (strcmp($temporalTerm1, $temporalTerm2) < 0)
                                                                                @$edges[$temporalterm1][$temporalterm2]++;
                                                                else
                                                                                @$edges[$temporalterm2][$temporalterm1]++;
                                                        }
                                        }
                        }
                
                foreach ($sameTemporalReferences as $termSource => $terms)
                        {
                                for ($a = 0; $a < count($terms); $a++)
                                        {
                                                for ($b = 0; $b < count($terms); $b++)
                                                        {
                                                                if ($a >= $b)
                                                                                continue;
                                                                if (isset($foundTemporalTerms[$terms[$a]]) && isset($foundTemporalTerms[$terms[$b]]))
                                                                        {
                                                                                $term1 = "temporalterm:" . $terms[$a];
                                                                                $term2 = "temporalterm:" . $terms[$b];
                                                                                if (strcmp($term1, $term2) < 0)
                                                                                                @$edges[$term1][$term2] = $numOfTweets;
                                                                                else
                                                                                                @$edges[$term2][$term1] = $numOfTweets;
                                                                        }
                                                        }
                                        }
                        }
                $RFileName              = __DIR__ . "/../tmp/edges_" . md5(json_encode($edges)) . ".list";
                $f                      = fopen($RFileName, "w");
                $AllNodes               = array();
                $AllNodes               = array_keys($edges);
                $AllNodesNew            = array();
                $NumEdgesBefore         = 0;
                $NumEdgesAfter          = 0;
                $GLOBAL_VALUES["Edges"] = array();
                foreach ($edges as $node1 => $edge)
                        {
                                if ($node1 == "")
                                                continue;
                                $AllNodes = array_merge($AllNodes, array_keys($edge));
                                foreach ($edge as $node2 => $weight)
                                        {
                                                $NumEdgesBefore++;
                                                $th = $relationThreshold;
                                                if (substr($node1, 0, 5) == "spot:" || substr($node2, 0, 5) == "spot:")
                                                                continue;
                                                if ($node2 != "")
                                                                $GLOBAL_VALUES["Edges"][$node1][$node2] = $weight;
                                                if ($node2 != "" && $weight > $th)
                                                        {
                                                                $NumEdgesAfter++;
                                                                $AllNodesNew[$node1] = 1;
                                                                $AllNodesNew[$node2] = 1;
                                                                fwrite($f, "$node1 $node2\n");
                                                        }
                                        }
                        }
                foreach ($AllNodes as $key => $value)
                                if (substr($value, 0, 5) == "spot:")
                                                unset($AllNodes[$key]);
                $AllNodes = array_values($AllNodes);
                foreach ($AllNodesNew as $key => $value)
                                if (substr($key, 0, 5) == "spot:")
                                                unset($AllNodesNew[$key]);
                $AllNodesNewNew = array_keys($AllNodesNew);
                
                $TemporalTermsBeforeRemoval = array();
                $TemporalTermsAfterRemoval  = array();
                $EntitiesBeforeRemoval      = array();
                $EntitiesAfterRemoval       = array();
                foreach ($AllNodes as $key => $value)
                                if (substr($value, 0, 13) == "temporalterm:")
                                                @$TemporalTermsBeforeRemoval[substr($value, 13, 999999)] = 1;
                                else
                                                @$EntitiesBeforeRemoval[$value] = 1;
                
                foreach ($AllNodesNewNew as $key => $value)
                                if (substr($value, 0, 13) == "temporalterm:")
                                                @$TemporalTermsAfterRemoval[substr($value, 13, 999999)] = 1;
                                else
                                                @$EntitiesAfterRemoval[$value] = 1;
                $TemporalTermsBeforeRemoval = array_keys($TemporalTermsBeforeRemoval);
                $TemporalTermsAfterRemoval  = array_keys($TemporalTermsAfterRemoval);
                $EntitiesAfterRemoval       = array_keys($EntitiesAfterRemoval);
                $EntitiesBeforeRemoval      = array_keys($EntitiesBeforeRemoval);
                
                $elementCoverageBefore  = 0;
                $elementCoverageAfter   = 0;
                $relationCoverageBefore = 0;
                $relationCoverageAfter  = 0;
                
                for ($a = 0; $a < $numOfTweets; $a++)
                        {
                                $tempTermIntersectBefore = array_intersect($TemporalTermsBeforeRemoval, $temporalTermsInTweets[$a]);
                                $entityIntersectBefore   = array_intersect($EntitiesBeforeRemoval, $tweetEntities[$a]);
                                $tempTermIntersectAfter  = array_intersect($TemporalTermsAfterRemoval, $temporalTermsInTweets[$a]);
                                $entityIntersectAfter    = array_intersect($EntitiesAfterRemoval, $tweetEntities[$a]);
                                if (count($tempTermIntersectBefore) + count($entityIntersectBefore) > 1)
                                                $relationCoverageBefore++;
                                if (count($tempTermIntersectBefore) + count($entityIntersectBefore) > 0)
                                                $elementCoverageBefore++;
                                
                                if (count($tempTermIntersectAfter) + count($entityIntersectAfter) > 1)
                                                $relationCoverageAfter++;
                                if (count($tempTermIntersectAfter) + count($entityIntersectAfter) > 0)
                                                $elementCoverageAfter++;
                                
                        }
                @$GLOBAL_VALUES["ElementCoverageBefore"] = $elementCoverageBefore;
                @$GLOBAL_VALUES["RelationCoverageBefore"] = $relationCoverageBefore;
                @$GLOBAL_VALUES["ElementCoverageAfter"] = $elementCoverageAfter;
                @$GLOBAL_VALUES["RelationCoverageAfter"] = $relationCoverageAfter;
                
                
                
                
                @$GLOBAL_VALUES["AllNodesBefore"] = count(array_unique($AllNodes));
                @$GLOBAL_VALUES["AllNodesAfter"] = count($AllNodesNew);
                @$GLOBAL_VALUES["NumEdgesBefore"] = $NumEdgesBefore;
                @$GLOBAL_VALUES["NumEdgesAfter"] = $NumEdgesAfter;
                
                fclose($f);
                $jsonR            = shell_exec("Rscript " . __DIR__ . "/../src/computeCliques.R $RFileName 2>/dev/null");
                $decodedJson      = json_decode(str_replace(array(
                                " ",
                                "\n",
                                "\t",
                                "\r"
                ), "", $jsonR), true);
                $topics           = array();
                $maxCount         = 0;
                $CliqueDistBefore = array();
                foreach ($decodedJson as $topicCandidate)
                        {
                                @$CliqueDistBefore[count($topicCandidate)]++;
                                if (count($topicCandidate) > $maxCount)
                                                $maxCount = count($topicCandidate);
                        }
                // DO SOME CLEANUP AND POST_PROCESSING : MERGING
                for ($a = 0; $a < count($decodedJson); $a++)
                        {
                                $allSpots = true;
                                $allTerms = true;
                                foreach ($decodedJson[$a] as $key => $value)
                                        {
                                                if (substr($key, 0, 5) != "spot:")
                                                                $allSpots = false;
                                                if (substr($key, 0, 13) != "temporalterm:")
                                                                $allTerms = false;
                                        }
                                if ($allSpots || $allTerms)
                                                unset($decodedJson[$a]);
                        }
                $keepCliques = array();
                $decodedJson = array_values($decodedJson);
                for ($a = 0; $a < count($decodedJson); $a++)
                        {
                                $cliqueNodes = array_keys($decodedJson[$a]);
                                if (count($decodedJson[$a]) == 2)
                                        {
                                                $edgeValue   = 0;
                                                $cliqueNodes = array_keys($decodedJson[$a]);
                                                if (isset($edges[$cliqueNodes[0]][$cliqueNodes[1]]))
                                                        {
                                                                $edgeValue = $edges[$cliqueNodes[0]][$cliqueNodes[1]];
                                                        }
                                                else if (isset($edges[$cliqueNodes[1]][$cliqueNodes[0]]))
                                                        {
                                                                $edgeValue = $edges[$cliqueNodes[1]][$cliqueNodes[0]];
                                                        }
                                                if ($edgeValue > $strongConnRatio)
                                                                $keepCliques[] = $decodedJson[$a];
                                        }
                                else if (count($decodedJson[$a]) == 3)
                                        {
                                                $keepThis = true;
                                                for ($b = 0; $b < count($decodedJson[$a]); $b++)
                                                        {
                                                                $breakYes = false;
                                                                for ($c = $b + 1; $c < count($decodedJson[$a]); $c++)
                                                                        {
                                                                                if (isset($edges[$cliqueNodes[$b]][$cliqueNodes[$c]]))
                                                                                        {
                                                                                                if ($edges[$cliqueNodes[$b]][$cliqueNodes[$c]] <= $strongConnRatio)
                                                                                                        {
                                                                                                                $breakYes = true;
                                                                                                                $keepThis = false;
                                                                                                                break;
                                                                                                        }
                                                                                        }
                                                                                else if (isset($edges[$cliqueNodes[$c]][$cliqueNodes[$b]]))
                                                                                        {
                                                                                                if ($edges[$cliqueNodes[$c]][$cliqueNodes[$b]] <= $strongConnRatio)
                                                                                                        {
                                                                                                                $keepThis = false;
                                                                                                                $breakYes = true;
                                                                                                                break;
                                                                                                        }
                                                                                        }
                                                                                else
                                                                                        {
                                                                                                $keepThis = false;
                                                                                                $breakYes = true;
                                                                                                break;
                                                                                        }
                                                                        }
                                                                if ($breakYes)
                                                                                break;
                                                        }
                                                if ($keepThis)
                                                                $keepCliques[] = $decodedJson[$a];
                                        }
                        }
                $decodedJson = array_values($decodedJson);
                for ($a = 0; $a < count($decodedJson); $a++)
                        {
                                $topicCandidate1 = array_keys($decodedJson[$a]);
                                $topicDeleted    = false;
                                if (count($topicCandidate1) == $maxCount || count($topicCandidate1) >= 3)
                                                for ($b = $a + 1; $b < count($decodedJson); $b++)
                                                        {
                                                                $topicCandidate2 = array_keys($decodedJson[$b]);
                                                                if (count($topicCandidate2) == $maxCount || count($topicCandidate2) >= 3)
                                                                        {
                                                                                $union        = array_unique(array_merge($topicCandidate1, $topicCandidate2));
                                                                                $intersection = array_intersect($topicCandidate1, $topicCandidate2);
                                                                                if (count($intersection) / count($union) >= 0.6)
                                                                                        {
                                                                                                $diff1   = array_diff($topicCandidate1, $intersection);
                                                                                                $diff2   = array_diff($topicCandidate2, $intersection);
                                                                                                $related = true;
                                                                                                foreach ($diff1 as $ent1)
                                                                                                        {
                                                                                                                $related = true;
                                                                                                                foreach ($diff2 as $ent2)
                                                                                                                        {
                                                                                                                                if ($ent1 == $ent2)
                                                                                                                                                continue;
                                                                                                                                if (!areElementsRelated($ent1, $ent2, $tweetEntities, $spotsInTweets, $temporalTermsInTweets, $numOfTweets))
                                                                                                                                        {
                                                                                                                                                $related = false;
                                                                                                                                                break;
                                                                                                                                        }
                                                                                                                        }
                                                                                                                if (!$related)
                                                                                                                                break;
                                                                                                        }
                                                                                                if ($related)
                                                                                                        {
                                                                                                                unset($decodedJson[$b]);
                                                                                                                $topicDeleted    = true;
                                                                                                                $decodedJson[$a] = array_fill_keys($union, 1);
                                                                                                                $decodedJson     = array_values($decodedJson);
                                                                                                                break;
                                                                                                        }
                                                                                        }
                                                                        }
                                                        }
                                if ($topicDeleted)
                                                $a--;
                        }
                for ($a = 0; $a < count($decodedJson); $a++)
                        {
                                $breakNeeded = false;
                                for ($b = $a + 1; $b < count($decodedJson); $b++)
                                        {
                                                $array1       = array_keys($decodedJson[$a]);
                                                $array2       = array_keys($decodedJson[$b]);
                                                $intersection = array_intersect($array1, $array2);
                                                $diff1        = array_diff($array1, $intersection);
                                                $diff2        = array_diff($array2, $intersection);
                                                if (count($diff1) == 0)
                                                        {
                                                                unset($decodedJson[$a]);
                                                                $decodedJson = array_values($decodedJson);
                                                                $breakNeeded = true;
                                                                $a--;
                                                                break;
                                                        }
                                                if (count($diff2) == 0)
                                                        {
                                                                unset($decodedJson[$b]);
                                                                $decodedJson = array_values($decodedJson);
                                                                $b--;
                                                        }
                                        }
                                if ($breakNeeded)
                                                break;
                        }
                for ($a = 0; $a < count($decodedJson); $a++)
                        {
                                for ($b = $a + 1; $b < count($decodedJson); $b++)
                                        {
                                                $array1                   = array_keys($decodedJson[$a]);
                                                $array2                   = array_keys($decodedJson[$b]);
                                                $intersection             = array_intersect($array1, $array2);
                                                $diff1                    = array_diff($array1, $intersection);
                                                $diff2                    = array_diff($array2, $intersection);
                                                $union                    = array_unique(array_merge($array1, $array2));
                                                $sameCount                = 0;
                                                $firstTopicTemporalTerms  = array();
                                                $secondTopicTemporalTerms = array();
                                                $firstTopicDifferences    = array();
                                                $secondTopicDifferences   = array();
                                                foreach ($diff1 as $elm1)
                                                        {
                                                                if (substr($elm1, 0, 13) == "temporalterm:")
                                                                                $firstTopicTemporalTerms[] = substr($elm1, 13, 999999);
                                                                else
                                                                                $firstTopicDifferences[] = $elm1;
                                                        }
                                                foreach ($diff2 as $elm2)
                                                        {
                                                                if (substr($elm2, 0, 13) == "temporalterm:")
                                                                                $secondTopicTemporalTerms[] = substr($elm2, 13, 999999);
                                                                else
                                                                                $secondTopicDifferences[] = $elm2;
                                                        }
                                                foreach ($firstTopicTemporalTerms as $key => $term)
                                                        {
                                                                $found = false;
                                                                foreach ($array2 as $array2ent)
                                                                        {
                                                                                if (substr($array2ent, 0, 13) == "temporalterm:")
                                                                                        {
                                                                                                $array2ent = substr($array2ent, 13, 999999);
                                                                                                foreach ($sameTemporalReferences as $group)
                                                                                                        {
                                                                                                                if (in_array($term, $group) && in_array($array2ent, $group))
                                                                                                                                $found = true;
                                                                                                        }
                                                                                                if ($found)
                                                                                                                break;
                                                                                        }
                                                                        }
                                                                if ($found)
                                                                                unset($firstTopicTemporalTerms[$key]);
                                                        }
                                                
                                                foreach ($secondTopicTemporalTerms as $key => $term)
                                                        {
                                                                $found = false;
                                                                foreach ($array1 as $array1ent)
                                                                        {
                                                                                if (substr($array1ent, 0, 13) == "temporalterm:")
                                                                                        {
                                                                                                $array1ent = substr($array1ent, 13, 999999);
                                                                                                foreach ($sameTemporalReferences as $group)
                                                                                                        {
                                                                                                                if (in_array($term, $group) && in_array($array1ent, $group))
                                                                                                                                $found = true;
                                                                                                        }
                                                                                                if ($found)
                                                                                                                break;
                                                                                        }
                                                                        }
                                                                if ($found)
                                                                                unset($secondTopicTemporalTerms[$key]);
                                                        }
                                                
                                                $unrelatedCount = count($firstTopicTemporalTerms) + count($secondTopicTemporalTerms) + count($firstTopicDifferences) + count($secondTopicDifferences);
                                                if ($unrelatedCount / count($union) <= $TOPIC_MERGE_DIFFERENCE_TH)
                                                        {
                                                                $decodedJson[$a] = array_fill_keys($union, 1);
                                                                unset($decodedJson[$b]);
                                                                $decodedJson = array_values($decodedJson);
                                                                $a--;
                                                                break;
                                                        }
                                        }
                        }
                /////////// END CLEANUP AND POST_PROCESSING
                $newTopics = array();
                foreach ($decodedJson as $topicCandidate)
                        {
                                if (count($topicCandidate) == $maxCount || count($topicCandidate) >= 3)
                                        {
                                                $newTopics[] = $topicCandidate;
                                        }
                        }
                
                
                $newTopics = array_merge($newTopics, $keepCliques);
                
                for ($a = 0; $a < count($newTopics); $a++)
                                for ($b = $a + 1; $b < count($newTopics); $b++)
                                        {
                                                if (isSameCandidate($newTopics[$a], $newTopics[$b]))
                                                        {
                                                                unset($newTopics[$b]);
                                                                $newTopics = array_values($newTopics);
                                                                $b--;
                                                        }
                                        }
                
                $newTopics                      = array_values($newTopics);
                $CliqueDistAfter                = array();
                $TopicsWithPersons              = 0;
                $TopicsWithLocations            = 0;
                $TopicsWithTemporalTerms        = 0;
                $TopicsWithAll                  = 0;
                $TopicsWithPersonTemporalTerm   = 0;
                $TopicsWithPersonLocation       = 0;
                $TopicsWithLocationTemporalTerm = 0;
                
                ///// REVERSE ENGINEERING
                $reverseEngineeringArray = array();
                for ($a = 0; $a < $numOfTweets; $a++)
                        {
                                foreach ($newTopics as $topicNo => $topicCandidate)
                                        {
                                                $nodes = array_keys($topicCandidate);
                                                for ($b = 0; $b < count($nodes); $b++)
                                                        {
                                                                $node1       = $nodes[$b];
                                                                $node1Exists = false;
                                                                if (substr($node1, 0, 13) == "temporalterm:")
                                                                        {
                                                                                @$node1 = substr($node1, 13, 999999);
                                                                                if (in_array($node1, $temporalTermsInTweets[$a]))
                                                                                        {
                                                                                                $node1Exists = true;
                                                                                        }
                                                                        }
                                                                else
                                                                        {
                                                                                if (in_array($node1, $tweetEntities[$a]))
                                                                                        {
                                                                                                $node1Exists = true;
                                                                                        }
                                                                        }
                                                                if (!$node1Exists)
                                                                                continue;
                                                                for ($c = $b + 1; $c < count($nodes); $c++)
                                                                        {
                                                                                $node2       = $nodes[$c];
                                                                                $node2Exists = false;
                                                                                if (substr($node2, 0, 13) == "temporalterm:")
                                                                                        {
                                                                                                $node2 = substr($node2, 13, 999999);
                                                                                                if (in_array($node2, $temporalTermsInTweets[$a]))
                                                                                                        {
                                                                                                                $node2Exists = true;
                                                                                                        }
                                                                                        }
                                                                                else
                                                                                        {
                                                                                                if (in_array($node2, $tweetEntities[$a]))
                                                                                                        {
                                                                                                                $node2Exists = true;
                                                                                                        }
                                                                                        }
                                                                                if ($node2Exists)
                                                                                        {
                                                                                                $reverseEngineeringArray[$topicNo][$a]["tweetText"]  = $allTweets[$a];
                                                                                                $reverseEngineeringArray[$topicNo][$a]["elements"][] = array(
                                                                                                                $node1,
                                                                                                                $node2
                                                                                                );
                                                                                        }
                                                                        }
                                                        }
                                        }
                        }
                for ($a = 0; $a < count($reverseEngineeringArray); $a++)
                        {
                                if (!isset($reverseEngineeringArray[$a]))
                                        {
                                                $reverseEngineeringArray[$a] = array();
                                        }
                                else
                                                $reverseEngineeringArray[$a] = array_values($reverseEngineeringArray[$a]);
                        }
                /////////////
                $topicEntities      = array();
                $topicTemporalTerms = array();
                foreach ($newTopics as $topicNo => $topicCandidate)
                        {
                                $hasPerson       = 0;
                                $hasLocation     = 0;
                                $hasTemporalTerm = 0;
                                @$CliqueDistAfter[count($topicCandidate)]++;
                                        {
                                                $topic = array();
                                                foreach ($topicCandidate as $element => $nodeId)
                                                        {
                                                                if (substr($element, 0, 5) == "spot:")
                                                                        {
                                                                                @$topic["what"][] = "unlinked_spot:" . str_replace("_", " ", substr($element, 5, 999999));
                                                                        }
                                                                else if (substr($element, 0, 13) == "temporalterm:")
                                                                        {
                                                                                @$topic["when"][] = substr($element, 13, 999999);
                                                                                @$topicTemporalTerms[substr($element, 13, 999999)] = 1;
                                                                                $hasTemporalTerm = 1;
                                                                        }
                                                                else
                                                                        {
                                                                                if (isset($persons[$element]))
                                                                                        {
                                                                                                @$topic["who"][] = $element;
                                                                                                @$topicEntities[$element] = 1;
                                                                                                $hasPerson = 1;
                                                                                        }
                                                                                else if (isset($validLocations[$element]))
                                                                                        {
                                                                                                @$topic["where"][] = $element;
                                                                                                @$topicEntities[$element] = 1;
                                                                                                $hasLocation = 1;
                                                                                        }
                                                                                else
                                                                                        {
                                                                                                @$topic["what"][] = $element;
                                                                                                @$topicEntities[$element] = 1;
                                                                                        }
                                                                        }
                                                                $topic["createdAt"] = $nowTimestamp;
                                                                $topic["startTime"] = $startTimestamp;
                                                                $topic["endTime"]   = $endTimestamp;
                                                        }
                                                $topic["reverseEngineering"] = $reverseEngineeringArray[$topicNo];
                                                $topics[]                    = $topic;
                                        }
                                $TopicsWithPersons += $hasPerson;
                                $TopicsWithLocations += $hasLocation;
                                $TopicsWithTemporalTerms += $hasTemporalTerm;
                                if ($hasPerson + $hasTemporalTerm == 2)
                                                $TopicsWithPersonTemporalTerm++;
                                if ($hasPerson + $hasLocation == 2)
                                                $TopicsWithPersonLocation++;
                                if ($hasLocation + $hasTemporalTerm == 2)
                                                $TopicsWithLocationTemporalTerm++;
                                if ($hasPerson + $hasLocation + $hasTemporalTerm == 3)
                                                $TopicsWithAll++;
                        }
                
                $GLOBAL_VALUES["CliqueDistBefore"]               = $CliqueDistBefore;
                $GLOBAL_VALUES["CliqueDistAfter"]                = $CliqueDistAfter;
                $GLOBAL_VALUES["TopicsWithPersons"]              = $TopicsWithPersons;
                $GLOBAL_VALUES["TopicsWithLocations"]            = $TopicsWithLocations;
                $GLOBAL_VALUES["TopicsWithTemporalTerms"]        = $TopicsWithTemporalTerms;
                $GLOBAL_VALUES["TopicsWithPersonTemporalTerm"]   = $TopicsWithPersonTemporalTerm;
                $GLOBAL_VALUES["TopicsWithPersonLocation"]       = $TopicsWithPersonLocation;
                $GLOBAL_VALUES["TopicsWithLocationTemporalTerm"] = $TopicsWithLocationTemporalTerm;
                $GLOBAL_VALUES["TopicsWithAll"]                  = $TopicsWithAll;
                
                $topicTemporalTerms = array_keys($topicTemporalTerms);
                $topicEntities      = array_keys($topicEntities);
                /// FIND ENTITY AND RELATION COVERAGE OF TOPICS
                $entityCoverage     = 0;
                $relationCoverage   = 0;
                for ($a = 0; $a < $numOfTweets; $a++)
                        {
                                $tempTermIntersect = array_intersect($topicTemporalTerms, $temporalTermsInTweets[$a]);
                                $entityIntersect   = array_intersect($topicEntities, $tweetEntities[$a]);
                                if (count($tempTermIntersect) + count($entityIntersect) > 1)
                                                $relationCoverage++;
                                if (count($tempTermIntersect) + count($entityIntersect) > 0)
                                                $entityCoverage++;
                        }
                @$GLOBAL_VALUES["TopicsEntityCoverage"] = $entityCoverage;
                @$GLOBAL_VALUES["TopicsRelationCoverage"] = $relationCoverage;
                /// ENTITY AND RELATION COVERAGE ENDS
                return $topics;
        }


function isSameCandidate($topic1, $topic2)
        {
                foreach ($topic1 as $key => $value)
                        {
                                $found = false;
                                foreach ($topic2 as $key1 => $value1)
                                                if ($key1 == $key)
                                                                $found = true;
                                if (!$found)
                                                return false;
                        }
                foreach ($topic2 as $key => $value)
                        {
                                $found = false;
                                foreach ($topic1 as $key1 => $value1)
                                                if ($key1 == $key)
                                                                $found = true;
                                if (!$found)
                                                return false;
                        }
                return true;
        }




?>
