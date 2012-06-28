<?php

require('../../config.php');

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$pageurl = new moodle_url('/local/replacer/index.php');

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url($pageurl);
echo $OUTPUT->header();
?>
<form action="<? $pageurl->out(); ?>">
    <label for="courseid">Course ID:</label>
    <input type="text" id="courseid" name="courseid" />

    <label for="lessonid">Lesson ID:</label>
    <input type="text" id="lessonid" name="lessonid" />

    <input type="submit" id="submit" value="Replace" />
</form>
<?

$courseid = optional_param('courseid', NULL, PARAM_INT);
$lessonid = optional_param('lessonid', NULL, PARAM_INT);

$lessonids = array();
if ($courseid) $lessonids = array_keys($DB->get_records('lesson', array('course' => $courseid), 'id'));
elseif ($lessonid) $lessonids = array($lessonid);

if (count($lessonids)) {
    list($lsql, $lparams) = $DB->get_in_or_equal($lessonids);
    $lsql = 'lessonid ' . $lsql;
    $pages = $DB->get_records_select_menu('lesson_pages', $lsql, $lparams, '', 'id,contents');

    $search = '/(<a\s[^>]*href="[^"#\?]+\/([^"#\?]+\.swf)([#\?]d=([\d]{1,4})x([\d]{1,4}))?"[^>]*>)<\/a>/is';
    foreach ($pages as $pageid => $text) {
        $newtext = preg_replace($search, '${1}${2}</a>', $text);

        if ($text != $newtext) {
            echo html_writer::empty_tag('hr');

            echo html_writer::tag('div', $pageid);

            echo htmlspecialchars($text);
            echo html_writer::empty_tag('hr');

            echo htmlspecialchars($newtext);

            $page = (object)array('id' => $pageid, 'contents' => $newtext);
            $DB->update_record('lesson_pages', $page);
        }
    }
}

echo $OUTPUT->footer();

?>
