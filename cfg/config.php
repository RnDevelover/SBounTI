<?php

$temporalReferenceGroups = array(
                array(
                                "Today",
                                "Now",
                                "Tonight",
                                "ThisEvening",
                                "ThisMorning",
                                "ThisAfternoon"
                )
); // GROUP of TEMPORAL REFERENCES THAT ARE CONSIDERED TO BE IN THE SAME TOPIC

$TEMPORALCONTEXT = "2018"; // YEAR TEMPORAL CONTEXT. RECOMMENDED TO BE SET TO THE YEAR OF THE POSTS 

$STRONG_CONN_RATIO            = 0.01; // tau_{_kc}
$LOCATION_CONSIDERATION_TH    = 0.002; // tau_{loc}
$NODE_RELATION_THRESHOLD      = 0.001; // tau_{e}
$NODE_RELATION_THRESHOLD_WEAK = 0.0005; // tau_e_{min}
$TOPIC_MERGE_DIFFERENCE_TH    = 0.2; // 1-tau_{c}

$TAGME_RHO_THRESHOLD              = 0.15; // tau_{rho}
$TAGME_LINK_PROBABILITY_THRESHOLD = 0.4; // tau_{p}

$TAGME_GCUBE_TOKEN = ""; // TAGME API KEY must be set.




$VERSION                 = "V0.5"; // NO NEED TO EDIT
$SEMANTIC_TOPIC_BASE_URL = "http://soslab.cmpe.boun.edu.tr/sbounti/topics_algorithm$VERSION.owl#"; // BASE URL THAT THE TOPICS WILL BE PUBLISED AT
$ONTOLOGY_BASE_URL       = "http://soslab.cmpe.boun.edu.tr/ontologies/topico.owl#"; // TOPICO ONTOLOGY URI // NO NEED TO EDIT
$ALGORITHM               = "TopicIdentificationAlgorithm$VERSION"; // NAME OF THE TOPIC IDENTIFICATION ALGORITHM



?>
