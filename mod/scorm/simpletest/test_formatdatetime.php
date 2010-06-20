<?php
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

// Unit tests for scorm_formatdatetime function from locallib.php
// Please note that they're made to work only with English language strings,
// so you have to set up English in Moodle settings first.

// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/mod/scorm/locallib.php'); // Include the code to test
 
class scorm_formatdatetime_test extends UnitTestCase {
    function test_scorm2004_format() {
        $SUTs = array(1=>'PT001H012M0043.12S', 2=>'PT15.3S', 3=>'P01Y02M5DT0H7M', 4=>'P0Y0M0DT0H1M00.00S',
                      5=>'P1YT15M00.01S', 6=>'P0Y0M0DT0H0M0.0S', 7=>'P1MT4M0.30S', 8=>'PT', 9=>'P1DT2H3S', 10=>'P4M');
        $validates = array(1=>'1 hours 12 minutes 43.12 seconds', 2=>'15.3 seconds', 3=>'1 years 2 months 5 days 7 minutes ',
                           4=>'1 minutes ', 5=>'1 years 15 minutes 0.01 seconds', 6=>'', 7=>'1 months 4 minutes 0.30 seconds',
                           8=>'', 9=>'1 days 2 hours 3 seconds', 10=>'4 months ');
        foreach ($SUTs as $key=>$SUT) {
            $formatted = scorm_format_date_time($SUT);
            $this->assertEqual($formatted, $validates[$key]);
        }
    }
 
    function test_scorm12_format() {
        $SUTs = array(1=>'00:00:00', 2=>'1:2:3', 3=>'12:34:56.78', 4=>'00:12:00.03', 5=>'01:00:23', 6=>'00:12:34.00',
                      7=>'00:01:02.03', 8=>'00:00:00.1', 9=>'1:23:00', 10=>'2:00:00');
        $validates = array(1=>'', 2=>'1 hours 2 minutes 3 seconds', 3=>'12 hours 34 minutes 56.78 seconds',
                           4=>'12 minutes 0.03 seconds', 5=>'1 hours 23 seconds', 6=>'12 minutes 34 seconds',
                           7=>'1 minutes 2.03 seconds', 8=>'0.1 seconds', 9=>'1 hours 23 minutes ', 10=>'2 hours ');
        foreach ($SUTs as $key=>$SUT) {
            $formatted = scorm_format_date_time($SUT);
            $this->assertEqual($formatted, $validates[$key]);
        }
    }
    
    function test_non_datetime() {
    }
}
?>
