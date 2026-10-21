<?php

return [
    /** Public production URL (canonical, Open Graph). */
    'url' => env('SITE_URL', 'https://cabmedia.in'),

    /** Keep false while developing (noindex). Set SITE_INDEXABLE=true when the site goes live. */
    'indexable' => (bool) env('SITE_INDEXABLE', false),

    'whatsapp' => env('SITE_WHATSAPP', '919820446872'),
    'contact_email' => env('SITE_CONTACT_EMAIL', 'info@cabmedia.in'),
    'og_image' => '/img/og-cabmedia.jpg',
];
