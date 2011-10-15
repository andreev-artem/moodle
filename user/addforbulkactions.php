<?php
require_once("../config.php");

$id       = required_param('id', PARAM_INT);              // course id
$users    = optional_param('userid', array(), PARAM_INT); // array of user id
$useridstr = optional_param('useridstr', '', PARAM_RAW);
$confirm  = optional_param('confirm', false, PARAM_BOOL);

if (! $course = $DB->get_record('course', array('id' => $id))) {
    error("Course ID is incorrect");
}

$context = get_context_instance(CONTEXT_COURSE, $id);
require_login($course->id);

require_capability('moodle/role:assign', $context);

if ($confirm && confirm_sesskey()) {
    $users = explode(',', $useridstr);

    if (!isset($SESSION->bulk_users)) {
        $SESSION->bulk_users = array();
    }

    $SESSION->bulk_users = array_merge($SESSION->bulk_users, $users);

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

$title = get_string('addforbulkactions', 'local_cdp_core_hacks_strings');
$PAGE->navbar->add($title);
$PAGE->set_title("$course->shortname: ".$title);
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

// generate the message
$confmsg = get_string('addforbulkactionsconfirm', 'local_cdp_core_hacks_strings');
list($in, $params) = $DB->get_in_or_equal($users);
$userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,' . $DB->sql_fullname() . ' AS fullname');
$usernames = implode('<br />', $userlist);
$confmsg .= '<br />' . $usernames;

$optionsyes = array(
        'confirm' => true,
        'id' => $id,
        'useridstr' => implode(',', $users),
        'sesskey' => $USER->sesskey);
$yesbtn = new single_button(new moodle_url('/user/addforbulkactions.php', $optionsyes), get_string('yes'));
$nobtn = new single_button(new moodle_url('/user/index.php', array('contextid'=>$context->id)), get_string('no'), 'get');

echo $OUTPUT->confirm($confmsg, $yesbtn, $nobtn);

echo $OUTPUT->footer();
?>
