<?php  // $Id: iterator.php,v 1.0 2009/02/14 argentum@cdp.tsure.ru Exp $

require_once('../../../config.php');

$id          = required_param('id', PARAM_INT);                 // course id.
$auto        = optional_param('auto', false, PARAM_BOOL);
$pageskip    = optional_param('pageskip', false, PARAM_BOOL);
$pageconfirm = optional_param('pageconfirm', false, PARAM_BOOL);

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

$navlinks = array();
$navlinks[] = array('name' => $strreports, 'link' => "../../report.php?id=$course->id", 'type' => 'misc');
$navlinks[] = array('name' => $strlessonpreg, 'link' => null, 'type' => 'misc');
$navigation = build_navigation($navlinks);
print_header("$course->shortname: $strlessonpreg", $course->fullname, $navigation);

if (!isset($SESSION->lessonpreg) || !isset($SESSION->lessonpregexp)) {
    redirect('index.php?id='. $id, get_string( 'errornodata', $pluginname, NULL, $langdir ));
}

switch($SESSION->lessonpregexp) {
    case 1:
        $pattern = '#\s*<div class="width">\s*<div class="minwidth">\s*<div class="layout">\s*<div class="container">\s*([\s\S]*)\s*</div>\s*</div>\s*</div>\s*</div>#i';
        $replace = "$1";
        break;
        
    case 2:
        $pattern = '#<div>\s*</div>|</>|<//>#i';
        $replace = '';
        break;
        
    case 3:
        $pattern = '#<div class="rightcolumn">\s*<div class="rightlist">\s*([\s\S]*)\s*</div>\s*</div>#i';
        $replace = '#';
        break;
        
    case 4:
        $pattern = '#\s*<div class="leftcolumn">\s*<div class="leftlist">\s*([\s\S]*)\s*</div>\s*</div>#i';
        $replace = '<div class="ruslesson">$1</div>';
        break;
        
    case 5:
        $pattern = '#\s*<div class="leftcolumn">\s*<div class="leftlist">\s*([\s\S]*)\s*</div>\s*</div>#i';
        $replace = '<div class="physlesson">$1</div>';
        break;
        
    case 6:
        $pattern = '#\s*<div class="leftcolumn">\s*<div class="leftlist">\s*([\s\S]*)\s*</div>\s*</div>#i';
        $replace = '<div class="mathlesson">$1</div>';
        break;
        
    case 7:
        $pattern = '#<div class="rightcolumn">([\s\S]*)</div>#i';
        $replace = '#';
        break;
		
	case 8:
	    $pattern = '#intendWrong#i';
	    $replace = 'intend wrong';
	    break;
		
	case 9:
	    $pattern = '#textexampleWrong#i';
	    $replace = 'textexample wrong';
		break;
		
	case 10:
	    $pattern = '#intend#i';
		$replace = 'lecindent';
		break;
		
	case 11:
	    $pattern = '# - #i';
		// it's a dash!
		$replace = " – ";
		break;

	default:
        redirect('index.php?id='. $id, get_string( 'errorwrongexp', $pluginname, NULL, $langdir ));
}

$starttime = time();

function is_timeout_close($start)
{
    return (time() - $start >= ini_get('max_execution_time') * 0.8);
}

function repfunc($matches)
{
    global $SESSION, $CFG;
    
    //echo s($matches[0]);
    //echo '<br/>';
    //echo s($matches[1]);
    switch($SESSION->lessonpregexp) {
        case 3:
            $divclose = '';
            $divmatch = preg_match('#</div>\s*</div>#i', $matches[0], $divclose, PREG_OFFSET_CAPTURE);
            return substr( $matches[0], $divclose[0][1] + strlen($divclose[0][0]) );
            break;

		case 7:
            $divclose = '';
            $divmatch = preg_match('#</div>#i', $matches[0], $divclose, PREG_OFFSET_CAPTURE);
            return substr( $matches[0], $divclose[0][1] + strlen($divclose[0][0]) );
            break;
            
        default:
            redirect('index.php?id='. $id, get_string( 'errorwrongexp', $pluginname, NULL, $langdir ));
    }    
}

function iterate_preg($start, $pattern, $replace) {
    global $SESSION, $CFG;
    
    $curslice = array_slice($SESSION->lessonpreg, 0, 20);
    $SQL = "SELECT * FROM {$CFG->prefix}lesson_pages WHERE id IN (". implode(',', $curslice). ")";
    $pages = get_records_sql($SQL);
    foreach ($pages as $page) {
        $count = 0;
        if ($replace != '#') {
            $result = preg_replace($pattern, $replace, $page->contents, -1, $count);
        } else {
            $result = preg_replace_callback($pattern, "repfunc", $page->contents, -1, $count);
        }
        if ($count) {
            $page->title = addslashes($page->title);
            $page->contents = addslashes($result);
            update_record("lesson_pages", $page);
            $SESSION->lessonpregcount++;
        }
        array_shift($SESSION->lessonpreg);
        
        if (is_timeout_close($start)) {
            return false;
        }
    }
    
    return true;
}

if ($auto) {
    flush();
    echo '<div align="center">';
    while (!empty($SESSION->lessonpreg)) {
        $timeout = !iterate_preg($starttime, $pattern, $replace);
        echo ( $SESSION->lessonpregtotal - count($SESSION->lessonpreg) ) . ' / ' . $SESSION->lessonpregtotal;
        echo ' ' . get_string( 'processed', $pluginname, NULL, $langdir ) . ', ';
        echo $SESSION->lessonpregcount . ' ' . get_string( 'updated', $pluginname, NULL, $langdir ) . '<br />';
        flush();
        if ($timeout) {
            echo '</div>';
            print_footer();
            redirect('iterator.php?id='. $id. '&auto=1');
            die;
        }
    }

    echo '</div>';
}

if ($pageskip || $pageconfirm) {
    echo '<div align="center"><b><i>';
    if ($pageconfirm) {
        $SQL = "SELECT * FROM {$CFG->prefix}lesson_pages WHERE id = " . current($SESSION->lessonpreg);
        $page = get_record_sql($SQL);
        $count = 0;
        if ($replace != '#') {
            $result = preg_replace($pattern, $replace, $page->contents, -1, $count);
        } else {
            $result = preg_replace_callback($pattern, "repfunc", $page->contents, -1, $count);
        }
        if (!$count) {
            echo get_string( 'nochanges', $pluginname, NULL, $langdir );
        } else {
            $page->title = addslashes($page->title);
            $page->contents = addslashes($result);
            update_record("lesson_pages", $page);
            $SESSION->lessonpregcount++;
            echo get_string('changessaved');
        }
    } else {
        echo get_string( 'skipped', $pluginname, NULL, $langdir );
    }
    array_shift($SESSION->lessonpreg);
    echo '</b></i></div><br />';
}

do {
    if (empty($SESSION->lessonpreg)) {
        break;
    }
    
    $SQL = "SELECT title, contents FROM {$CFG->prefix}lesson_pages WHERE id = " . current( $SESSION->lessonpreg );
    $page = get_record_sql($SQL);

    $count = 0;
    if ($replace != '#') {
        $result = preg_replace($pattern, $replace, $page->contents, -1, $count);
    } else {
        $result = preg_replace_callback($pattern, "repfunc", $page->contents, -1, $count);
    }
    if (!$count) {
        array_shift( $SESSION->lessonpreg );
    }
    
    if (is_timeout_close($starttime)) {
        echo get_string( 'skipping', $pluginname, NULL, $langdir );
        print_footer();
        redirect('iterator.php?id='. $id);
        die;
    }
} while (!$count && !empty($SESSION->lessonpreg));

if (empty($SESSION->lessonpreg)) {
    unset($SESSION->lessonpreg);
    unset($SESSION->lessonpregexp);
    unset($SESSION->lessonpregtotal);
    $resultstr = get_string( 'completed', $pluginname, NULL, $langdir ) . '<br />';
    $resultstr .=  get_string( 'total', $pluginname, NULL, $langdir ) . ': ' . $SESSION->lessonpregcount;
    unset($SESSION->lessonpregcount);
    redirect('index.php?id='. $id, $resultstr, 5);
} else {
    echo '<br /><div align="center">';
    echo get_string('page'). ': ' . ( $SESSION->lessonpregtotal - count($SESSION->lessonpreg) + 1 ) . ' / ' . $SESSION->lessonpregtotal;
    echo '<br /><i>' . $page->title . '</i> <br /><br />';
    echo '<table width="95%"><tr>';
    echo '<td>' . get_string( 'before', $pluginname, NULL, $langdir ) . '</td>';
    echo '<td>' . get_string( 'after', $pluginname, NULL, $langdir ) . '</td>';
    echo '</tr><tr>';
    echo '<td><font size="1">';
    /*if ($count) {
        $matches = array();
        $match = preg_match($pattern, $page->contents, $matches, PREG_OFFSET_CAPTURE);
        var_dump($match);
        var_dump($matches);
        echo '<b>';
        echo $matches1[0][1];
        echo '<br />';
        echo $matches2[0][1];
        echo '<br />';
        echo strlen($page->contents);
        echo '<br />';
        echo(s(substr($page->contents,$matches2[0][1],100500)));
        echo '<br />';
        echo $page->id;
        echo '</b>';
    } else {*/
    echo s($page->contents);
    //}
    echo '</font></td>';
    echo '<td>';
    echo '<font size="1">' . s($result) . '</font>';
    echo '</td></tr></table>';
    
    echo '<form action="iterator.php?id=' . $id . '" method="POST" >';
    echo '<input type=submit name=pageskip value="' . get_string( 'skip', $pluginname, NULL, $langdir ) . '">';
    echo '&nbsp; <input type=submit name=pageconfirm value="' . get_string( 'save', $pluginname, NULL, $langdir ) . '">';
    echo '</form>';
    echo '</div>';
}

print_footer();
?>
