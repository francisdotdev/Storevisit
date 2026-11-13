<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ratings Matrix Configuration (FR2005 / FR3004-B)
    |--------------------------------------------------------------------------
    */

    // Timezone for calendar bucketing and dates
    'timezone' => 'Asia/Manila',

    // Strategy for splitting month into 4 weeks: 'day_of_month' (W1: 1-7, W2: 8-14, W3: 15-21, W4: 22-end)
    'week_strategy' => 'day_of_month',

    // Aggregation strategy when a store has multiple audits in the same week: 'average' or 'latest'
    'multiple_visit_aggregation' => 'average',
];

