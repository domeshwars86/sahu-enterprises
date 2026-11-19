<?php

return [
    'pib' => [
        'enabled' => (bool) env('PIB_NEWS_ENABLED', true),
        'feed_url' => 'https://www.pib.gov.in/RssMain.aspx?ModId=6&Lang=1&Regid=1',
        'allowed_hosts' => ['pib.gov.in', 'www.pib.gov.in'],
        'source_name' => 'Press Information Bureau',
        'max_response_bytes' => 2_000_000,
        'max_items' => 50,
    ],
];
