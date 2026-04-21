<?php

return [
    'allowed_emails' => array_filter(array_map('trim', explode(',', (string) env('ALLOWED_EMAILS', '')))),
    'bot_db_path'    => env('BOT_DB_PATH', '/home/ubuntu/SafaryEngine/live/data/live_dashboard.db'),
    'bot_configs_path' => env('BOT_CONFIGS_PATH', '/home/ubuntu/SafaryEngine/live/configs'),
];
