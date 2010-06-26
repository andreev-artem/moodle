<?php
require_once($CFG->libdir.'/formslib.php');

class recent_form extends moodleform {
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('date_selector', 'date', get_string('since'), array('optional'=>true));

        $this->add_action_buttons(false, get_string('showrecent'));
    }
}

?>
