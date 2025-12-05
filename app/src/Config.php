<?php

class Config
{
    private static $instance = null;
    private $config = [];

    private function __construct()
    {
        // Load environment variables from .env file if it exists
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) {
                    continue; // Skip comments and empty lines
                }
                if (strpos($line, '=') === false) {
                    continue; // Skip lines without equals sign
                }
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }

        // Redis configuration
        $this->config['redis'] = [
            'host' => $_ENV['REDIS_HOST'] ?? getenv('REDIS_HOST') ?: 'redis',
            'port' => $_ENV['REDIS_PORT'] ?? getenv('REDIS_PORT') ?: 6379,
        ];

        // Database configuration
        $this->config['database'] = [
            'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'mariadb',
            'user' => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root',
            'pass' => $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: 'password',
            'name' => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'ads',
        ];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getRedisConfig()
    {
        return $this->config['redis'];
    }

    public function getDatabaseConfig()
    {
        return $this->config['database'];
    }

    public function get($key)
    {
        return $this->config[$key] ?? null;
    }
}
