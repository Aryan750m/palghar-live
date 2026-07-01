<?php

// SEO Configuration: app/Config/seo.php

$configApp = require __DIR__ . '/app.php';
$baseUrl = $configApp['url'] ?? 'https://palghar-live.onrender.com';

return [
    'default_title' => 'Palghar LIVE - The Strong Voice of the Common People',
    'default_description' => "Palghar district's fastest and most trusted digital news portal. Get local updates, monsoon forecasts, sports championships, and political news live.",
    'default_keywords' => 'Palghar News, Palghar Live, Palghar Updates, Jawhar, Dahanu, Wada, Boisar, Vasai, Local News Maharashtra',
    'default_og_image' => '/assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg',

    // Social metadata
    'facebook_url' => 'https://www.facebook.com/share/1DzefDPcC2/',
    'youtube_channel_url' => 'https://www.youtube.com/@palgharlivenews',

    // Organisation details for JSON-LD Schemas
    'organisation' => [
        'name' => 'Palghar LIVE',
        'url' => $baseUrl,
        'logo' => rtrim($baseUrl, '/') . '/assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg',
        'same_as' => [
            'https://www.facebook.com/share/1DzefDPcC2/',
            'https://youtube.com/@palgharlivenews'
        ]
    ]
];
