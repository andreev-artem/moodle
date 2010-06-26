<?php

$report_recentactivities_capabilities = array(

    'report/recentactivities:view' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'legacy' => array(
            'admin' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        ),
        
        'clonepermissionsfrom' => 'moodle/site:viewreports',
        
    )
);
