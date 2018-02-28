<?php

function validateDate($date, $format = 'D M j H:i:s O Y')
        {
                $d = DateTime::createFromFormat($format, $date);
                return $d && $d->format($format) == $date;
        }
exit((validateDate($argv[1]) == true) ? 0 : 1);
?>
