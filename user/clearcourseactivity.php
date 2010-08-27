<?php  // $Id: clearcourseactivity.php,v 0.8 2008/10/08 argentum@cdp.tsure.ru Exp $
require_once("../config.php");
require_once("../mod/quiz/locallib.php");
require_once("../mod/lesson/lib.php");
require_once("../mod/assignment/lib.php");

$id       = required_param('id', PARAM_INT);              // course id
$users    = optional_param('userid', array(), PARAM_INT); // array of user id
$usersstr = optional_param('useridstr', '', PARAM_RAW);
$confirm  = optional_param('confirm', false, PARAM_BOOL);

if (! $course = get_record('course', 'id', $id)) {
    error("Course ID is incorrect");
}

$context = get_context_instance(CONTEXT_COURSE, $id);
require_login($course->id);

require_capability('moodle/role:assign', $context);

if ($confirm && confirm_sesskey()) {
    $users = explode(',', $usersstr);

    // first unenrol selected users from the course
    foreach ($users as $user) {
        role_unassign(0, $user, 0, $context->id);
    }

    // delete all quiz activity
    $attemptssql = "SELECT a.* FROM {$CFG->prefix}quiz_attempts a, {$CFG->prefix}quiz q
                    WHERE q.course=$id AND a.quiz=q.id AND a.userid IN ($usersstr)";
    if ($attempts = get_records_sql($attemptssql)) {
        foreach ($attempts as $attempt) {
            $quiz = get_record("quiz", "id", "{$attempt->quiz}");
            quiz_delete_attempt( $attempt, $quiz );
        }
    }

    // delete all lesson activity
    /// Clean up the timer table
    delete_records_select('lesson_timer', "userid IN ($usersstr)");

    /// Remove the grades from the grades and high_scores tables
    delete_records_select('lesson_grades', "userid IN ($usersstr)");
    delete_records_select('lesson_high_scores', "userid IN ($usersstr)");

    /// Remove attempts
    delete_records_select('lesson_attempts', "userid IN ($usersstr)");

    /// Remove seen branches  
    delete_records_select('lesson_branch', "userid IN ($usersstr)");

    /// update central gradebook
    /*$sql = "SELECT l.*, cm.idnumber as cmidnumber, l.course as courseid
            FROM {$CFG->prefix}lesson l, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
            WHERE m.name='lesson' AND m.id=cm.module AND cm.instance=l.id";
    if ($rs = get_recordset_sql($sql)) {
        while ($lesson = rs_fetch_next_record($rs)) {
            foreach ($users as $user) {
                lesson_update_grades($lesson, $user);
            }
        }
        rs_close($rs);
    }*/

    // delete all assignment submissions
    // delete submission files
    $assignmentssql = "SELECT a.id FROM {$CFG->prefix}assignment a WHERE course=$id";
    if ($assignments = get_records_sql($assignmentssql)) {
        foreach ($assignments as $assignmentid=>$none) {
            foreach ($users as $user) {
                fulldelete($CFG->dataroot.'/'.$id.'/moddata/assignment/'.$assignmentid.'/'.$user);
            }
        }
    }

    // delete submission records
    delete_records_select('assignment_submissions', "userid IN ($usersstr)");

    // update gradebook
    /*$sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
            FROM {$CFG->prefix}assignment a, {$CFG->prefix}course_modules cm, {$CFG->prefix}modules m
            WHERE m.name='assignment' AND m.id=cm.module AND cm.instance=a.id";
    if ($rs = get_recordset_sql($sql)) {
        while ($assignment = rs_fetch_next_record($rs)) {
            foreach ($users as $user) {
                $grade = new object();
                $grade->userid   = $user;
                $grade->rawgrade = NULL;
                assignment_grade_item_update($assignment, $grade);
            }
        }
        rs_close($rs);
    }*/

    // finally, delete all grade records to clean up database
    $sql = "SELECT g.id 
            FROM {$CFG->prefix}grade_grades g, {$CFG->prefix}grade_items i
            WHERE g.itemid = i.id AND i.courseid = $id AND g.userid IN ($usersstr)";
    $grades = get_fieldset_sql($sql);
    delete_records_select('grade_grades', 'id IN ('.implode(',', $grades).')');

    // we're done
    redirect("$CFG->wwwroot/user/index.php?id=$id", get_string('changessaved'));
}

// extracting user list
if (empty($users)) {
    foreach ($_POST as $k => $v) {
        if (preg_match('/^user(\d+)$/',$k,$m)) {
            $users[] = $m[1];
        }
    }
}

// Print headers
$navlinks = array();
$navlinks[] = array('name' => get_string('clearcourseactivity'), 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);

print_header("$course->shortname: ".get_string('clearcourseactivity'), $course->fullname, $navigation, "", "", true, "&nbsp;", navmenu($course));

// generate the message
$confmsg = get_string('clearcourseactivityconfirm');
$confmsg .= $course->fullname . '?';
$optionsyes = array();
$optionsyes['confirm'] = true;
$optionsyes['id'] = $id;
$optionsyes['useridstr'] = implode(',', $users);
$optionsyes['sesskey'] = $USER->sesskey;
    
// print the message
notice_yesno($confmsg, 'clearcourseactivity.php', '../user/index.php', $optionsyes, array('contextid'=>$context->id), 'post', 'get');

print_footer($course);
?>
