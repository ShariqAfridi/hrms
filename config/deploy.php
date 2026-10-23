<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Production FTP Deployment Configuration
    |--------------------------------------------------------------------------
    |
    | Stores persistent remote FTP server connection settings for automated
    | deployments to the live server.
    |
    */
    'ftp' => [
        'host' => env('FTP_HOST', '198.54.114.248'),
        'domain' => env('FTP_DOMAIN', 'hrms.axecorptech.com'),
        'port' => (int) env('FTP_PORT', 21),
        'username' => env('FTP_USERNAME', 'devel@hrms.axecorptech.com'),
        'password' => env('FTP_PASSWORD', 'devel@2026'),
        'passive' => env('FTP_PASSIVE', true),
        'timeout' => (int) env('FTP_TIMEOUT', 60),
        
        // Critical: files and directories that must NEVER be modified or overwritten on remote
        'protected_files' => [
            '.env',
            '.env.production',
            '.env.local',
            '.env.example',
            '.git',
            'node_modules',
            'vendor',
            'storage/framework/sessions',
            'storage/logs',
            'scratch',
            '.gemini',
            'GEMINI.md',
            'AGENTS.md',
            '.agents',
            '.agent',
            '_agents',
            '_agent',
            '.system_generated',
            'database_dump.sql',
            '*.sql',
            '*.sqlite',
            'phpunit.xml',
            '.vscode',
            '.idea',
        ],


        // Directories and files to synchronize on deploy
        'sync_paths' => [
            'app',
            
            'bootstrap',
            'config',
            'database',
            'lang',
            'public',
            'resources',
            'routes',
            'artisan',
            'composer.json',
            '.htaccess',
            'index.php',
        ],
    ],
];
