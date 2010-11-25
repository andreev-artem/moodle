<?php  // $Id: clearcourseactivity.php,v 0.8 2008/10/08 argentum@cdp.tsure.ru Exp $
require_once("../config.php");

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

    if (!isset($SESSION->bulk_users)) {
        $SESSION->bulk_users = array();
    }
    foreach ($users as $user) {
        $SESSION->bulk_users[] = $user;
    }

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
$navlinks[] = array('name' => get_string('selectforbulkactions'), 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);

print_header("$course->shortname: ".get_string('selectforbulkactions'), $course->fullname, $navigation, "", "", true, "&nbsp;", navmenu($course));

// generate the message
$confmsg = get_string('selectforbulkactionsconfirm');
$in = implode(',', $users);
$userlist = get_records_select_menu('user', "id IN ($in)", 'fullname', 'id,' . sql_fullname() . ' AS fullname');
$usernames = implode('<br />', $userlist);
$confmsg .= '<br />' . $usernames;

$optionsyes = array();
$optionsyes['confirm'] = true;
$optionsyes['id'] = $id;
$optionsyes['useridstr'] = $in;
$optionsyes['sesskey'] = $USER->sesskey;
    
// print the message
notice_yesno($confmsg, 'selectforbulkactions.php', '../user/index.php', $optionsyes, array('contextid'=>$context->id), 'post', 'get');

print_footer($course);
?>
