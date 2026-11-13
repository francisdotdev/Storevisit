<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Allowed Date Window for Starting an Audit
    |--------------------------------------------------------------------------
    | Default: 'scheduled_date_only'.
    | Options: 'scheduled_date_only', 'allow_anytime', 'allow_same_month'
    | TODO: confirm with requestor
    */
    'allowed_date_window' => 'scheduled_date_only',

    /*
    |--------------------------------------------------------------------------
    | Audit Grade Thresholds
    |--------------------------------------------------------------------------
    | Read thresholds from configuration. By default, this array is EMPTY.
    | If thresholds are not configured, the audit grade MUST remain null.
    | Do NOT invent grade bands.
    | Example when configured:
    | [
    |     ['min' => 90, 'max' => 100, 'grade' => 'Excellent'],
    |     ['min' => 75, 'max' => 89.99, 'grade' => 'Good'],
    | ]
    */
    'grade_thresholds' => [],
];

