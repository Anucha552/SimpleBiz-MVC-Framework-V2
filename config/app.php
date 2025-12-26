<?php
/**
 * APPLICATION CONFIGURATION
 * 
 * Purpose: Core application settings
 * 
 * Configuration Options:
 * - APP_NAME: Application name
 * - APP_ENV: Environment (development|production)
 * - APP_DEBUG: Debug mode (true|false)
 * - APP_URL: Base application URL
 * 
 * IMPORTANT:
 * - Set APP_ENV=production in production
 * - Set APP_DEBUG=false in production
 * - Load sensitive config from .env file
 */

return [
    'name' => getenv('APP_NAME') ?: 'SimpleBiz MVC Framework V2',
    'env' => getenv('APP_ENV') ?: 'development',
    'debug' => getenv('APP_DEBUG') !== 'false',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'UTC',
];
