<?php
/**
 * DATABASE CONFIGURATION
 * 
 * Purpose: Database connection settings
 * 
 * Configuration:
 * - host: Database server hostname
 * - port: Database server port
 * - database: Database name
 * - username: Database username
 * - password: Database password
 * - charset: Character encoding
 * 
 * SECURITY:
 * - Load credentials from environment variables
 * - NEVER commit credentials to version control
 * - Use .env file for local development
 * - Use environment variables in production
 */

return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'simplebiz_mvc',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
