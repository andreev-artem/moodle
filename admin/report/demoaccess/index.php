<?php

    require_once('../../../config.php');
    require_once($CFG->libdir.'/adminlib.php');
    require_once($CFG->dirroot.'/course/lib.php');
    require_once($CFG->libdir.'/tablelib.php');
    
    admin_externalpage_setup('reportdemoaccess');
    admin_externalpage_print_header();

    print_heading(get_string('title', 'report_demoaccess'));

    $tablecolumns = array('userpic', 'fullname', 'email', 'city', 'country', 'lastaccess');
    $tableheaders = array(get_string('userpic'), get_string('fullname'), get_string('email'),
                          get_string('city'), get_string('country'), get_string('lastaccess'));
    $baseurl = $CFG->wwwroot.'/admin/report/demoaccess/index.php';
    
    $countries = get_list_of_countries();

    $strnever = get_string('never');
    
    $courses = get_courses("all", "c.sortorder ASC", "c.fullname, c.id");
    foreach ($courses as $course) {
        // Демо-доступ
        if ( ($group = groups_get_group_by_name($course->id, "Демо-доступ") ) === false ) {
            continue;
        }
        $table = new flexible_table('demo-access-participants-'.$course->id);

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($baseurl);
    
        $table->sortable(true, 'lastaccess', SORT_DESC);
        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'participants-'.$course->id);
        $table->set_attribute('class', 'generaltable generalbox');
        $table->set_attribute('width', '95%');
    
        $table->setup();

        $users = groups_get_members($group, 'u.*', $table->get_sql_sort());
        
        echo '<strong><a href="'.$CFG->wwwroot.'/course/view.php?id='. $course->id. '">'. $course->fullname. '</a> ['. count($users). ']</strong><br /><br />';
        
        if ($users) {
            $counter = 0;
            foreach ($users as $user) {
    
                if ($user->lastaccess) {
                    $lastaccess = format_time(time() - $user->lastaccess);
                } else {
                    $lastaccess = $strnever;
                }
    
                if (empty($user->country)) {
                    $country = '';
                } else {
                    $country = $countries[$user->country];
                }
    
                $profilelink = '<strong><a href="'.$CFG->wwwroot.'/user/view.php?id='.$user->id.'&amp;course='.$course->id.'">'.fullname($user).'</a></strong>';
    
                $data = array ( print_user_picture($user, $course->id, $user->picture, false, true, true),
                                $profilelink, $user->email, $user->city, $country, $lastaccess);
    
                $table->add_data($data);
                if (++$counter == 5) {
                    break;
                }
            }
        }
        
        $table->print_html();
        echo '<br />';        
    }

    admin_externalpage_print_footer();
?>
