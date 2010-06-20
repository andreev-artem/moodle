<?PHP //$Id: block_search_glossaries.php,v 1.2 2006/04/15 23:57:26 stronk7 Exp $

class block_search_glossaries extends block_base {
    function init() {
        $this->title = get_string('blockname','block_search_glossaries');
        $this->version = 2005112900;
    }

    function has_config() {return true;}

    function applicable_formats() {
        return (array('site-index' => true, 'course-view-weeks' => true, 'course-view-topics' => true));
    }

    function get_content() {
        global $CFG, $USER;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance)) {
            return $this->content;
        }

        $course = get_record('course', 'id', $this->instance->pageid);

        $searchglossaries = get_string('glossariessearch', 'block_search_glossaries');

        $rowstart = '<tr><td align="center">';
        $rowend = '</td></tr>';

        $coursefield = '<input type="hidden" name="courseid" value="'.$course->id.'">';
        $pagefield = '<input type="hidden" name="page" value="0">';
        $searchbox = '<input type="text" name="query" size="20" maxlength="255" value="">';
        $submitbutton = '<br /><input type="submit" name="submit" value="'.$searchglossaries.'">';

        $row2content = $coursefield.$pagefield.$searchbox.$submitbutton;

        $row2 = $rowstart.$row2content.$rowend;

        $table = '<table>'.$row2.'</table>';
        $form = '<form method="GET" action="'.$CFG->wwwroot.'/blocks/search_glossaries/search_glossaries.php">'.$table.'</form>';
        $this->content->text = $form;

        return $this->content;
    }
}

?>
