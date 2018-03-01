# S-BounTI: Semantic Topic Identification Method

S-BounTI is a topic identification approach that identifies topics of a crowd of microblog users. It represents topics using [Topico ontology](http://soslab.cmpe.boun.edu.tr/ontologies/topico.owl#) which is designed to express microblog topics.

S-BounTI and Topico are products of [Ahmet Yildirim](http://www.ahmetyildirim.com.tr)'s PhD work under the supervision of [Suzan Uskudarli](http://www.cmpe.boun.edu.tr/~uskudarli), members of SoSLab in Department of Computer Engineering, Bogazici University, Istanbul, Turkey.

If you have a set of microblog posts and want to have semantic topics, or if you want further information you can [directly contact to Ahmet Yıldırım](http://soslab.cmpe.boun.edu.tr/contact.php?c=ay#cf).

To install and run S-BounTI prerequisites must be satisfied. The instructions imply those prerequisites. The instructions given below are for Ubuntu based systems but can be ported to other operating systems.

*  Install R
*  Make sure that Rscript is running
*  Make sure that iGraph and RJSONIO is installed for R
*  Install php-cli (Php command line interface) version>5
*  Make sure that php-curl and php-mbstring is installed
*  Make sure that shell_exec is working in PHP-cli
*  Obtain a TagMe API key
    *  Download the SBounTI package and extract it in an empty directory
    *  Edit cfg/config.php according to need (such as base urls of resources that will be produced and the TagMe API key)
*  Obtain a microblog post dataset about 5 thousand posts, either in a file format of short texts in each line
or in a raw file retrieved from Twitter streaming API
*  Issue command:
    *  ./sbounti <filename> "<dataset_name>" "<start_date>" "<end_date>"
  for the text file
    *  ./sbounti <filename> "<dataset_name>" 
       for the raw Twitter streaming API file
       Where <filename> is the file name of the file that has short messages, <dataset_name> that is used in the explanations of the resources expressed in OWL, <start_date> and <end_date> are valid start and end date-times of the post set in the format as in example: Wed Sep 21 11:01:56 +0300 2016.
*  The produced OWL file contents are written to STDOUT. So, you may want to redirect the output to a file using "> filename.owl" at the end of the command.

