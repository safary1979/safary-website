<?php

return [
    'allowed_emails' => array_filter(array_map('trim', explode(',', (string) env('ALLOWED_EMAILS', '')))),
    'bot_db_path'    => env('BOT_DB_PATH', '/home/ubuntu/SafaryEngine/live/data/live_dashboard.db'),
    'bot_configs_path' => env('BOT_CONFIGS_PATH', '/home/ubuntu/SafaryEngine/live/configs'),

    // Account names defined in /home/ubuntu/SafaryEngine/secrets/accounts.yaml.
    // Laravel shows these in the Launch modal; secrets stay on the bot host.
    'accounts' => array_values(array_filter(array_map('trim', explode(',', (string) env('ACCOUNTS', 'bybit-demo'))))),
];
