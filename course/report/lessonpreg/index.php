<?php  // $Id: index.php,v 1.0 2009/02/14 argentum@cdp.tsure.ru Exp $

require_once('../../../config.php');

$id        = required_param('id', PARAM_INT);                 // course id.
$start     = optional_param('start', false, PARAM_BOOL);
$expr      = optional_param('expr', 0, PARAM_INT);
$lessonids = optional_param('lessons', false, PARAM_RAW);
$auto      = optional_param('auto', false, PARAM_BOOL);

if (!$course = get_record('course', 'id', $id)) {
    print_error('invalidcourse');
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $course->id);
require_capability('coursereport/lessonpreg:view', $context);

$strlessonpreg = get_string('title', 'report_lessonpreg');
$strreports    = get_string('reports');

$langdir = $CFG->dirroot.'/course/report/lessonpreg/lang/';
$pluginname = 'report_lessonpreg';

if ($start) {
    $SESSION->lessonpregexp = $expr;
    $SESSION->lessonpreg = array();
    $lessons = array();
    foreach ($_POST as $k => $v) {
        if (preg_match('#^lesson_(\d+)$#',$k,$m)) {
            $lessons[] = $m[1];
        }
    }
    
    if (empty($lessons)) {
        redirect('index.php?id='. $id);
    }
    
    $SQL = "SELECT id FROM {$CFG->prefix}lesson_pages WHERE lessonid IN (". implode(',', $lessons). " )";
    $pages = get_records_sql($SQL);
    foreach ($pages as $page) {
        $SESSION->lessonpreg[] = $page->id;
    }
        
    $SESSION->lessonpregtotal = count($SESSION->lessonpreg);
    $SESSION->lessonpregcount = 0;
    redirect('iterator.php?id='. $id. ( $auto ? '&auto=1' : '' ));
}

$navlinks = array();
$navlinks[] = array('name' => $strreports, 'link' => "../../report.php?id=$course->id", 'type' => 'misc');
$navlinks[] = array('name' => $strlessonpreg, 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);
print_header("$course->shortname: $strlessonpreg", $course->fullname, $navigation);

$SQL = "SELECT id, name FROM {$CFG->prefix}lesson WHERE course = $id ORDER BY name";
$lessons = get_records_sql($SQL);

echo '<br />';
echo '<form action="index.php?id=' . $id . '" method="POST" >';
echo '<div align="center">';
echo '<select name=expr size=1>';
echo '<option value=1>'. s('<div class="width">...'). '</option>';
echo '<option value=2>'. s('<div></div>, </>, <//>'). '</option>';
echo '<option value=3>'. s('<div class="rightcolumn">...'). '</option>';
echo '<option value=4>'. s('<div class="leftcolumn">... => ruslesson'). '</option>';
echo '<option value=5>'. s('<div class="leftcolumn">... => physlesson'). '</option>';
echo '<option value=6>'. s('<div class="leftcolumn">... => mathlesson'). '</option>';
echo '<option value=7>'. s('<div class="rightcolumn"> only'). '</option>';
echo '<option value=8>'. s('intendWrong -> intend wrong'). '</option>';
echo '<option value=9>'. s('textexampleWrong -> textexample wrong'). '</option>';
echo '<option value=10>'. s('intend -> lecindent'). '</option>';
echo '<option value=11>'. s('hyphen -> dash'). '</option>';
echo '</select><br />';
echo "<input type=checkbox name=auto value=1>". get_string( 'autoreplace', $pluginname, NULL, $langdir ) . "<br /></div>\n";

//echo '<div style="margin-left: 20%">'."\n";
foreach ($lessons as $lesson) {
    echo '<input type="checkbox" id="lesson" name="lesson_'.$lesson->id.'" />';
    echo s($lesson->name);
    echo "<br />\n";
}

echo '<br />';
echo '<input type=button name=checkall value="'. get_string('checkall') . '" onclick="{
  var el=document.getElementsByTagName('. "'INPUT'".');
  for(var i=0;i<el.length;i++) {
  	if(el[i].id=='. "'lesson'". ')
    	void(el[i].checked=1);
  }}">';
echo '&nbsp;';
echo '<input type=button name=uncheckall value="'. get_string('deselectall') . '" onclick="{
  var el=document.getElementsByTagName('. "'INPUT'".');
  for(var i=0;i<el.length;i++) {
  	if(el[i].id=='. "'lesson'". ')
    	void(el[i].checked=0);
  }}">';

//echo '</div>';

echo '<div align="center">';
echo '<br /><input type=submit name=start value="' . get_string('go') . '">';
echo '</div>';
echo '</form>';

print_footer();
?>
