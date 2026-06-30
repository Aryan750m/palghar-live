<?php

// Cache Configuration: app/Config/cache.php

return [
    // Enable/disable the cache
    'enabled' => true,

    // In-memory static cache settings
    'store' => 'array',

    // Cached queries default lifetime (in seconds, for memory cache replication if needed)
    'ttl' => 3600,
];
