<?php // $Id: index.php,v 1.0.1 2008/12/09 argentum@cdp.tsure.ru Exp $

require_once '../../../config.php';
require_once $CFG->libdir.'/gradelib.php';
require_once $CFG->dirroot.'/grade/lib.php';
require_once($CFG->libdir.'/tablelib.php');

$courseid = optional_param('id', $COURSE->id, PARAM_INT);
$userid   = optional_param('userid', $USER->id, PARAM_INT);
$hide     = optional_param('hide', false, PARAM_INT);

/// basic access checks
if (!$course = get_record('course', 'id', $courseid)) {
    print_error('nocourseid');
}
require_login($course);

if (!$user = get_complete_user_data('id', $userid)) {
    error("Incorrect userid");
}

$context     = get_context_instance(CONTEXT_COURSE, $course->id);
$usercontext = get_context_instance(CONTEXT_USER, $user->id);
require_capability('moodle/grade:viewall', $context);

/// return tracking object
$gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'synchronize', 'courseid'=>$course->id, 'userid'=>$userid));

/// last selected report session tracking
if (!isset($USER->grade_last_report)) {
    $USER->grade_last_report = array();
}
$USER->grade_last_report[$course->id] = 'synchronize';

/// Build navigation
$strgrades  = get_string('grades');
$reportname = get_string('modulename', 'gradereport_synchronize');

$navigation = grade_build_nav(__FILE__, $reportname, $course->id);

/// Print header
print_header_simple($strgrades.': '.$reportname, ': '.$strgrades, $navigation,
                    '', '', true, '', navmenu($course));

/// Print the plugin selector at the top
$plugin_info = grade_get_plugin_info($course->id, 'report', 'synchronize');
print_grade_plugin_selector($plugin_info);

if ($hide !== false && $hide != -1) {
    $grade_item = grade_item::fetch(array('id'=>$hide));
    $grade_item->set_hidden(true);
    echo '<div align=center>'.get_string('changessaved').'</div><br />';
}
    
$sql = "SELECT gi.id as giid, gi.itemname as name, cm.id as cmid, gi.itemmodule as module
        FROM {$CFG->prefix}grade_items gi INNER JOIN 
        ({$CFG->prefix}course_modules cm INNER JOIN {$CFG->prefix}modules m ON cm.module = m.id)
        ON m.name = gi.itemmodule AND cm.instance = gi.iteminstance
        AND gi.hidden = 0 AND cm.visible = 0 AND gi.courseid = $courseid";
$results = get_records_sql($sql);

if ($hide === -1) {
    foreach ($results as $item) {
        $grade_item = grade_item::fetch(array('id'=>$item->giid));
        $grade_item->set_hidden(true);
    }
    echo '<div align=center>'.get_string('changessaved').'</div><br />';
    $results = false;
}

if ($results) {
    $tablecolumns = array('name','link');
    $tableheaders = array(get_string('name'),'');
    $table = new flexible_table('grade-report-synchronize-'.$userid);
    
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);
    
    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'synchronize-grade');
    $table->set_attribute('class', 'boxaligncenter generaltable');
    $table->define_baseurl('index.php');
    $table->setup();
    foreach ($results as $item) {
        $table->add_data(array('<a href="'.$CFG->wwwroot.'/mod/'.$item->module.'/view.php?id='.$item->cmid.'">'.$item->name.'</a>',
        				'<a href="index.php?hide='.$item->giid.'&id='.$courseid.'">'.get_string('hide').'</a>'));
    }
    
    echo '<div align=center>'.get_string('header', 'gradereport_synchronize').'</div><br />';
    $table->print_html();
    echo '<br /><div align=center><a href="index.php?hide=-1&id='.$courseid.'">'.get_string('hideall', 'gradereport_synchronize').'</a></div>';
} else {
    echo '<div align=center>'.get_string('nodata', 'gradereport_synchronize').'</div>';
}

print_footer($course);

?>
