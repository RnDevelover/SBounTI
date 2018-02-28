<?php
include_once(__DIR__ . "/../cfg/config.php");

$json_encoded = "";
while (($js = trim(fgets(STDIN))) != NULL)
        {
                $json_encoded .= $js;
        }
$json = json_decode($json_encoded, true);

$ontContent = file_get_contents($ONTOLOGY_BASE_URL);
$pos        = mb_strpos($ontContent, "</rdf:RDF>");
echo mb_substr($ontContent, 0, $pos, "UTF-8");

?>
   <owl:NamedIndividual rdf:about="<?= $SEMANTIC_TOPIC_BASE_URL . $ALGORITHM ?>">
        <rdf:type rdf:resource="http://xmlns.com/foaf/0.1/Agent"/>
    </owl:NamedIndividual>
<?php
$generalTopicId    = 0;
$externalResources = array();
$intervals         = array();
$years             = array();
$NUMOFTOPICS       = 0;
foreach ($json["semanticTopics"] as $topicId => $topic)
        {
                if (isset($topic["who"]))
                        {
                                $whos = $topic["who"];
                                foreach ($whos as $who)
                                                $externalResources[$who] = 1;
                        }
                $whats = array();
                if (isset($topic["what"]))
                                $whats = $topic["what"];
                foreach ($whats as $what)
                                if (mb_substr($what, 0, 14, "UTF-8") != "unlinked_spot:")
                                                $externalResources[$what] = 1;
                if (isset($topic["where"]))
                        {
                                $wheres = $topic["where"];
                                foreach ($wheres as $where)
                                                $externalResources[$where] = 1;
                        }
                else
                                $wheres = array();
                @$whens = $topic["when"];
                if (isset($whens) && is_array($whens))
                                foreach ($whens as $when)
                                        {
                                                if (substr($when, 0, 5) == "year:")
                                                                $years[substr($when, 5, 99999)] = 1;
                                        }
                $startTime                                                     = $topic["startTime"];
                $endTime                                                       = $topic["endTime"];
                $intervals[str_replace(":", "-", $startTime . "-" . $endTime)] = array(
                                "start" => $startTime,
                                "end" => $endTime
                );
        }
foreach ($years as $year => $v)
        {
?><owl:NamedIndividual rdf:about="<?= $SEMANTIC_TOPIC_BASE_URL ?>year_<?= $year ?>"><time:unitType rdf:resource="http://www.w3.org/2006/time#unitYear"/><time:year rdf:datatype="http://www.w3.org/2001/XMLSchema#gYear"><?= $year ?></time:year></owl:NamedIndividual><?php
        }
foreach ($intervals as $intId => $interval)
        {
?><owl:NamedIndividual rdf:about="<?= $SEMANTIC_TOPIC_BASE_URL ?>interval_<?= $intId ?>">
        <rdf:type rdf:resource="http://www.w3.org/2006/time#Interval"/>
        <time:hasBeginning rdf:resource="<?= $SEMANTIC_TOPIC_BASE_URL ?>instant_<?= $intId ?>_start"/>
        <time:hasEnd rdf:resource="<?= $SEMANTIC_TOPIC_BASE_URL ?>instant_<?= $intId ?>_end"/>
    </owl:NamedIndividual>
    <owl:NamedIndividual rdf:about="<?= $SEMANTIC_TOPIC_BASE_URL ?>instant_<?= $intId ?>_start">
        <rdf:type rdf:resource="http://www.w3.org/2006/time#Instant"/>
        <time:inXSDDateTime rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime"><?= $interval["start"] ?></time:inXSDDateTime>
    </owl:NamedIndividual>
    <owl:NamedIndividual rdf:about="<?= $SEMANTIC_TOPIC_BASE_URL ?>instant_<?= $intId ?>_end">
        <rdf:type rdf:resource="http://www.w3.org/2006/time#Instant"/>
        <time:inXSDDateTime rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTime"><?= $interval["end"] ?></time:inXSDDateTime>
    </owl:NamedIndividual>
    <?php
        }
foreach ($externalResources as $er => $v)
        {
?>
   <owl:NamedIndividual rdf:about="http://dbpedia.org/resource/<?= $er ?>"/><?php
        }
$NUMOFTOPICS = 0;
$no          = "";
$comment     = "";
$explanation = $argv[1];
$identifier  = "";
$identifier  = mb_strtolower($explanation);
$comment     = "$explanation dataset topic:";
$generalTopicId++;
foreach ($json["semanticTopics"] as $topicId => $topic)
        {
                $topicUrl = $SEMANTIC_TOPIC_BASE_URL . str_replace(" ", "_", mb_strtolower($identifier, "UTF-8")) . "_topic_" . $topicId;
?>
   <owl:NamedIndividual rdf:about="<?= $topicUrl ?>">
        <rdf:type rdf:resource="<?= $ONTOLOGY_BASE_URL ?>Topic"/>
        <foaf:maker rdf:resource="<?= $SEMANTIC_TOPIC_BASE_URL . $ALGORITHM ?>"/>
        <rdfs:label xml:lang="en"><?= $comment . " $topicId" ?></rdfs:label>
        <topicCreatedAt rdf:datatype="http://www.w3.org/2001/XMLSchema#dateTimeStamp"><?= $topic["createdAt"] ?></topicCreatedAt>
        <observationInterval rdf:resource="<?= $SEMANTIC_TOPIC_BASE_URL ?>interval_<?= str_replace(":", "-", $topic["startTime"] . "-" . $topic["endTime"]) ?>"/>
        <?php
                @$whos = $topic["who"];
                if (isset($whos))
                                foreach ($whos as $who)
                                //20162016 First Presidential DebateV19 6-8 minutes 22
                                        {
?>
           <hasPerson rdf:resource="http://dbpedia.org/resource/<?= $who ?>"/><?php
                                        }
                @$wheres = $topic["where"];
                if (isset($wheres))
                                foreach ($wheres as $where)
                                        {
?>
           <hasLocation rdf:resource="http://dbpedia.org/resource/<?= $where ?>"/><?php
                                        }
                @$whats = $topic["what"];
                if (isset($whats))
                                foreach ($whats as $what)
                                        {
                                                if (mb_substr($what, 0, 14, "UTF-8") == "unlinked_spot:")
                                                        {
?><isAboutTerm><?= mb_substr($what, 14, 999999, "UTF-8") ?></isAboutTerm>
            <?php
                                                        }
                                                else
                                                        {
?>
           <isAbout rdf:resource="http://dbpedia.org/resource/<?= $what ?>"/><?php
                                                        }
                                        }
                @$whens = $topic["when"];
                if (isset($whens))
                                foreach ($whens as $when)
                                        {
                                                if (substr($when, 0, 5) == "year:")
                                                        {
?><hasTemporalTerm rdf:resource="<?= $SEMANTIC_TOPIC_BASE_URL ?>year_<?= substr($when, 5, 99999); ?>"/><?php
                                                        }
                                                else
                                                        {
?><hasTemporalExpression rdf:resource="<?= $ONTOLOGY_BASE_URL ?><?= $when ?>"/><?php
                                                        }
                                        }
?>
   </owl:NamedIndividual>
        <?php
        }
echo "</rdf:RDF>";
//echo file_get_contents(__DIR__."/../texts/ontologyFooter.txt");
?>
Download Formatting took: 175 ms PHP Formatter made by Spark Labs  
Copyright Gerben van Veenendaal  

