<?php
// Single source of truth for admin forms AND the column whitelist used by the API.
$icons = [
    'fa-solid fa-link',
    'fa-brands fa-linkedin', 'fa-brands fa-github', 'fa-brands fa-instagram', 'fa-brands fa-telegram',
    'fa-brands fa-x-twitter', 'fa-brands fa-youtube', 'fa-brands fa-whatsapp', 'fa-brands fa-facebook',
    'fa-brands fa-tiktok', 'fa-brands fa-discord', 'fa-solid fa-envelope', 'fa-solid fa-globe',
    'fa-solid fa-store', 'fa-solid fa-cart-shopping', 'fa-solid fa-bag-shopping', 'fa-solid fa-basket-shopping',
    'fa-solid fa-credit-card', 'fa-solid fa-wallet', 'fa-solid fa-money-bill-wave', 'fa-solid fa-coins',
    'fa-solid fa-receipt', 'fa-solid fa-tag', 'fa-solid fa-percent', 'fa-solid fa-gift',
    'fa-solid fa-truck', 'fa-solid fa-box', 'fa-solid fa-qrcode', 'fa-solid fa-building-columns',
    'fa-solid fa-hand-holding-dollar',
    'fa-brands fa-paypal', 'fa-brands fa-stripe', 'fa-brands fa-cc-visa', 'fa-brands fa-cc-mastercard',
    'fa-brands fa-apple-pay', 'fa-brands fa-google-pay', 'fa-brands fa-amazon', 'fa-brands fa-shopify',
    'fa-brands fa-bitcoin',
];
$title = ['title_fa' => ['Title (Persian)', 'text'], 'title_en' => ['Title (English)', 'text']];
$tail = ['url' => ['URL', 'url'], 'sort' => ['Order (small first)', 'number'], 'active' => ['Visible', 'check']];

return [
    'settings' => ['label' => 'Settings', 'icon' => 'fa-gear', 'fields' => [
        'name_fa' => ['Name (Persian)', 'text'],
        'name_en' => ['Name (English)', 'text'],
        'bio_fa' => ['Bio (Persian)', 'textarea'],
        'bio_en' => ['Bio (English)', 'textarea'],
        'avatar' => ['Avatar', 'image'],
        'default_lang' => ['Default language', 'select', ['fa', 'en']],
        'show_fa' => ['Persian enabled', 'check'],
        'show_en' => ['English enabled', 'check'],
        'app_name' => ['PWA name', 'text'],
        'short_name' => ['PWA short name', 'text'],
        'theme_color' => ['PWA theme color', 'color'],
        'icon' => ['PWA icon (square, 512px or larger)', 'image'],
    ]],
    'slides' => ['label' => 'Slider', 'icon' => 'fa-images', 'fields' => $title + ['image' => ['Image', 'image']] + $tail],
    'links' => ['label' => 'Links', 'icon' => 'fa-link', 'fields' => ['icon' => ['Icon', 'select', $icons]] + $title + $tail],
    'projects' => ['label' => 'Projects', 'icon' => 'fa-diagram-project', 'fields' => $title + [
        'desc_fa' => ['Description (Persian)', 'textarea'],
        'desc_en' => ['Description (English)', 'textarea'],
        'image' => ['Image', 'image'],
    ] + $tail],
];
