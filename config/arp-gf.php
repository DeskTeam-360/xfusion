<?php

/**
 * Gravity Forms field mapping for ARP steps 1, 2, 5.
 * Keep in sync with app/Http/Plugin/.../arp-gf-mapping.php
 */
return [
    'foundation' => [
        'form_id' => (int) env('ARP_GF_FORM_FOUNDATION', 515),
        'hidden' => [
            'arp_id' => (int) env('ARP_GF_FIELD_ARP_ID', 10),
        ],
        'fields' => [
            'mission' => 1,
            'vision' => 3,
            'core_values' => 4,
            'organizational_description' => 5,
            'business_environment' => 6,
            'executive_narrative' => 7,
        ],
    ],
    'future_state' => [
        'form_id' => (int) env('ARP_GF_FORM_FUTURE_STATE', 516),
        'hidden' => [
            'arp_id' => (int) env('ARP_GF_FIELD_ARP_ID', 10),
        ],
        'fields' => [
            'future_state_narrative' => 1,
            'future_characteristics' => 3,
            'desired_culture' => 4,
            'desired_customer_experience' => 5,
            'desired_employee_experience' => 6,
            'desired_leadership_environment' => 7,
        ],
    ],
    'learning' => [
        'form_id' => (int) env('ARP_GF_FORM_LEARNING', 517),
        'hidden' => [
            'arp_id' => (int) env('ARP_GF_FIELD_ARP_ID', 10),
        ],
        'fields' => [
            'assumptions' => 1,
            'risks' => 3,
            'opportunities' => 4,
            'learning_objectives' => 5,
            'leadership_questions' => 6,
        ],
    ],
];
