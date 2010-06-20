<?php
require_once "$CFG->libdir/formslib.php";
class mod_scorm_report_settings extends moodleform {
    
    function definition() {
        global $COURSE;
        $mform    =& $this->_form;
        
        $mform->addElement('header', 'preferencesuser', get_string('preferencesuser', 'scorm'));

        $mform->addElement('text', 'pagesize', get_string('pagesize', 'scorm'));
        $mform->setType('pagesize', PARAM_INT);

        $mform->addElement('selectyesno', 'detailedrep', get_string('details', 'scorm'));

        $this->add_action_buttons(false, get_string('savepreferences'));
    }
    
}
?>
