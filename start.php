<?php
include("cfg/config.php");
if (!isset($TAGME_GCUBE_TOKEN) || $TAGME_GCUBE_TOKEN == "")
        {
                error("Please obtain a TagMe api key by following: https://sobigdata.d4science.org/web/tagme/tagme-help\n\nEdit cfg/config.php accordingly.");
        }
$rExec = shell_exec("which Rscript 2>/dev/null");
if ($rExec == "")
        {
                error("Rscript command cannot be found. Please install.");
        }
$rExec = trim(shell_exec("Rscript src/check.R  && echo $?"));
if ($rExec == "" || $rExec != "0")
        {
                error("iGraph and RJSONIO R packages are needed. Tried to install, but failed.");
        }

if (!isset($argv[1]) || $argv[1] == "" || !file_exists($argv[1]))
        {
                error("Please specify an input file name that has short messages.");
        }
if (!isset($argv[2]) || $argv[2] == "")
        {
                error("Please specify a dataset name.");
        }

$content = file_get_contents($argv[1]);
$pos     = mb_strpos($content, "\"text\":\"", 0, "UTF-8");
if ($pos === false)
// PLAIN TEXT FILE
        {
                if (!isset($argv[3]) || !isset($argv[4]) || !validateDate($argv[3]) || !validateDate($argv[4]))
                        {
                                error("Text file format detected. Please provide valid start and end date-times as second and third parameters in format: \nD M d H:i:s O Y\nExample: Wed Sep 21 11:01:56 +0300 2016");
                        }
                //    echo "php src/tweetTextOnly.php \"".$argv[1]."\" \"".$argv[3]."\" \"".$argv[4]."\" | php src/compute.php | php src/makeOwlFile.php \"".$argv[2]."\"";die;
                echo shell_exec("php src/tweetTextOnly.php \"" . $argv[1] . "\" \"" . $argv[3] . "\" \"" . $argv[4] . "\" | php src/compute.php | php src/makeOwlFile.php \"" . $argv[2] . "\"");
                
        }
else
        {
                echo shell_exec("php src/tweetsExtended.php \"" . $argv[1] . "\" | php src/compute.php | php src/makeOwlFile.php \"" . $argv[2] . "\"");
                
        }

function validateDate($date, $format = 'D M d H:i:s O Y')
        {
                $d = DateTime::createFromFormat($format, $date);
                return $d && $d->format($format) == $date;
        }
function error($msg)
        {
                fwrite(STDERR, $msg . "\n");
                exit(-1);
        }
?>
