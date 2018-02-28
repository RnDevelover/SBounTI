<?php
$TERMS = array();
$f     = fopen(__DIR__ . "/../texts/timeReferences.txt", "r");
while (($line = fgets($f)) != NULL)
        {
                $splitted = explode("\t", str_replace(array(
                                "\n",
                                "\r"
                ), "", $line));
                @$TERMS[$splitted[0]] = $splitted[1];
        }
function extractTemporalTerms($text)
        {
                $terms   = array();
                $pattern = '/[1][0-9][0-9][0-9]/';
                preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
                if (isset($matches[0]))
                        {
                                foreach ($matches[0] as $match)
                                        {
                                                if ($match[0] < 1800)
                                                                continue;
                                                if (isMention($text, $match[1]))
                                                                continue;
                                                @$terms[$match[0]] = 1;
                                        }
                        }
                
                $pattern = '/[2][0][0-9][0-9]/';
                
                preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
                if (isset($matches[0]))
                        {
                                foreach ($matches[0] as $match)
                                        {
                                                if ($match[0] < 1800)
                                                                continue;
                                                if (isMention($text, $match[1]))
                                                                continue;
                                                @$terms[$match[0]] = 1;
                                        }
                        }
                /*
                if($text=="United States presidential election debates, 2012")
                {
                print_r($terms);die;
                }*/
                $terms = array_keys($terms);
                return $terms;
        }
function extractTemporalTermsWithExpressions($text)
        {
                global $TERMS;
                $originalText = $text;
                $terms        = array();
                $pattern      = '/[1][0-9][0-9][0-9]/';
                preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
                if (isset($matches[0]))
                        {
                                foreach ($matches[0] as $match)
                                        {
                                                if ($match[0] < 1800)
                                                                continue;
                                                if (isMention($text, $match[1]))
                                                                continue;
                                                @$terms["year:" . $match[0]] = 1;
                                        }
                        }
                $pattern = '/[2][0][0-9][0-9]/';
                preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
                
                if (isset($matches[0]))
                        {
                                foreach ($matches[0] as $match)
                                        {
                                                if ($match[0] < 1800)
                                                                continue;
                                                if (isMention($text, $match[1]))
                                                                continue;
                                                @$terms["year:" . $match[0]] = 1;
                                        }
                        }
                $terms         = array_keys($terms);
                $text          = mb_strtolower($text, "UTF-8");
                $text          = preg_replace("/[[:punct:]]/u", ' (punc) ', $text);
                $text          = preg_replace("/[^[:alnum:][:space:](\(punc\))]/u", ' ', $text);
                $text          = mb_strtolower($text, "UTF-8");
                $text          = preg_replace('/\s+/', ' ', $text);
                $splittedTerms = explode(" ", $text);
                $twoGrams      = array();
                $oneGrams      = array();
                $TERM_KEYS     = array_keys($TERMS);
                if (in_array($splittedTerms[0], $TERM_KEYS))
                                @$oneGrams[$splittedTerms[0]] = 1;
                for ($a = 1; $a < count($splittedTerms); $a++)
                        {
                                $twoGrams[] = $splittedTerms[$a - 1] . " " . $splittedTerms[$a];
                        }
                $foundTwoGrams = array_values(array_intersect($TERM_KEYS, $twoGrams));
                for ($a = 1; $a < count($splittedTerms); $a++)
                        {
                                if (!in_array($splittedTerms[$a - 1] . " " . $splittedTerms[$a], $foundTwoGrams))
                                        {
                                                if (in_array($splittedTerms[$a], $TERM_KEYS))
                                                        {
                                                                $oneGrams[$splittedTerms[$a]] = 1;
                                                        }
                                        }
                        }
                $oneGrams = array_keys($oneGrams);
                $found    = array();
                foreach ($oneGrams as $g)
                        {
                                if ($g == "may")
                                        {
                                                $splitted = preg_split('/[^[:alnum:]]+/', $originalText);
                                                if (!in_array("May", $splitted))
                                                                continue;
                                        }
                                $found[$TERMS[$g]] = 1;
                        }
                foreach ($foundTwoGrams as $g)
                        {
                                $found[$TERMS[$g]] = 1;
                        }
                return array_merge(array_keys($found), $terms);
        }
function isMention(&$text, $index)
        {
                if ($index == 0)
                                return ($text[0] == "@");
                
                for ($a = $index; $a >= 0; $a--)
                        {
                                if (mb_substr($text, $a, 1, "UTF-8") == "@")
                                                return true;
                                if (mb_substr($text, $a, 1, "UTF-8") == " ")
                                                return false;
                        }
                return false;
        }
?>
