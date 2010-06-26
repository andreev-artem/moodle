<?php

    require_once('../../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once('recent_form.php');

    admin_externalpage_setup('reportrecentactivities');
    admin_externalpage_print_header();

    print_heading(get_string('title', 'report_recentactivities'));
    
    //$timeelements = getdate($USER->lastlogin);
    //$timestart = mktime(0, 0, 0, $timeelements['mon'], $timeelements['mday'], $timeelements['year']);
    $timestart = $USER->lastlogin;
    $mform = new recent_form();
    $mform->set_data(array( 'date' => 0 ));
    if ($formdata = $mform->get_data(false)) {
        if ($formdata->date) {
            $timestart = $formdata->date;
        }
    }

    $mform->display();
    
    echo '<div class="activitydate">';
    echo get_string('activitysince', '', userdate($timestart));
    echo '</div><br />';
    
    $courses = get_records('course');
    $changelist = array();
    
    foreach ($courses as $course) {
        $logs = get_records_select('log', "time > $timestart AND course = $course->id AND
                                           module = 'course' AND
                                           (action = 'add mod' OR action = 'update mod')",
                                   "id ASC");
    
        if ($logs) {
            $modinfo =& get_fast_modinfo($course);
            foreach ($logs as $key => $log) {
                $info = split(' ', $log->info);
    
                if (count($info) != 2) {
                    debugging("Incorrect log entry info: id = ".$log->id, DEBUG_DEVELOPER);
                    continue;
                }
    
                $modname    = $info[0];
                $instanceid = $info[1];
    
                // do not display added and later deleted activities
                if (!isset($modinfo->instances[$modname][$instanceid])) {
                    continue;
                }
                $cm = $modinfo->instances[$modname][$instanceid];

                $strmod = get_string('modulename', $modname);
                
                if ($cm->groupmode == VISIBLEGROUPS) {
                    $groupmodelabel = get_string('groupsvisible');
                } else if ($cm->groupmode == SEPARATEGROUPS) {
                    $groupmodelabel = get_string('groupsseparate');
                } else {
                    $groupmodelabel = get_string('groupsnone');
                }
                
                if ($cm->modname == 'label') {
                    $activityname = $cm->extra;
                } else {
                    $activityname = $cm->name;
                }
                
                if ($log->action == 'update mod' && !empty($changelist[$log->info])) {
                    continue;
                }
                
                $straction = '';
                if ($log->action == 'update mod') {
                    $straction = get_string('updated', 'report_recentactivities');
                } else if ($log->action == 'add mod') {
                    $straction = get_string('added', 'report_recentactivities');
                }
                                
                $changelist[$log->info] = 
                    array(  'course' => "<a href=\"$CFG->wwwroot/course/view.php?id=$course->id\">". format_string($course->shortname, true). "</a>",
							'module' => $straction. " $strmod: <a href=\"$CFG->wwwroot/mod/$cm->modname/view.php?id={$cm->id}\">".format_string($activityname, true)."</a>",
                            'groupmode' => $groupmodelabel,
                            'grouping' => $cm->groupingid == 0 ? get_string('none') : groups_get_grouping_name($cm->groupingid),
                            'grouponly' => $cm->groupmembersonly ? get_string('yes') : get_string('no'),
                            'visible' => $cm->visible ? get_string('yes') : get_string('no')
                    );
            }
        }
    }
    
    if (!empty($changelist)) {
        $table = new stdClass();
        $table->head = array();
        $table->width = '100%';
        
        $table->head[]  = get_string('course');
        $table->align[] = 'center';
        $table->wrap[]  = 'nowrap';
        $table->size[]  = '*';

        $table->head[]= get_string('activity');
        $table->align[] = 'center';
        $table->wrap[] = 'nowrap';
        $table->size[] = '*';

        $table->head[]= get_string('groupmode','group');
        $table->align[] = 'center';
        $table->wrap[] = 'nowrap';
        $table->size[] = '*';

        $table->head[]= get_string('grouping','group');
        $table->align[] = 'center';
        $table->wrap[] = 'nowrap';
        $table->size[] = '*';

        $table->head[]= get_string('groupmembersonly','group');
        $table->align[] = 'center';
        $table->wrap[] = '';
        $table->size[] = '*';

        $table->head[]= get_string('visible');
        $table->align[] = 'center';
        $table->wrap[] = 'nowrap';
        $table->size[] = '*';
        
        $table->data = $changelist;
        print_table($table);
    }

    admin_externalpage_print_footer();
?>
