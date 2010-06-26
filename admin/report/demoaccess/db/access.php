<?php

$report_demoaccess_capabilities = array(

    'report/demoaccess:view' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
			'teacher' => CAP_ALLOW
        ),
        
        'clonepermissionsfrom' => 'moodle/site:viewreports',
        
    )
);
