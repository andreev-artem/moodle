<?php

$gradereport_synchronize_capabilities = array(

    'gradereport/synchronize:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'teacher' => CAP_PREVENT,
            'editingteacher' => CAP_ALLOW,
            'student' => CAP_PREVENT,
            'admin' => CAP_ALLOW
        )
    )

);

?>
