<?php

    if (!defined('MOODLE_INTERNAL')) {
        die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page
    }

    if (has_capability('coursereport/lessonpreg:view', $context)) {
        echo '<p>';
        echo "<a href=\"{$CFG->wwwroot}/course/report/lessonpreg/index.php?id={$course->id}\">";
        echo get_string('title', 'report_lessonpreg')."</a>\n";
        echo '</p>';
    }
?>
