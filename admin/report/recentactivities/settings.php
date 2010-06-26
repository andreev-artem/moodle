<?php
$ADMIN->add('reports', new admin_externalpage('reportrecentactivities', get_string('title', 'report_recentactivities'), "$CFG->wwwroot/$CFG->admin/report/recentactivities/index.php", 'report/recentactivities:view'));
?>
