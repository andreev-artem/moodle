<?php
$ADMIN->add('reports', new admin_externalpage('reportdemoaccess', get_string('title', 'report_demoaccess'), "$CFG->wwwroot/$CFG->admin/report/demoaccess/index.php", 'report/demoaccess:view'));
?>
