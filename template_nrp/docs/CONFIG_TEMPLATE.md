# AI BTC BOT - Configuration Template Guide

## Table of Contents

1. [Overview](#overview)
2. [Environment Setup](#environment-setup)
3. [Database Configuration](#database-configuration)
4. [Application Settings](#application-settings)
5. [Email Configuration](#email-configuration)
6. [Payment Gateway Settings](#payment-gateway-settings)
7. [API Endpoint Configuration](#api-endpoint-configuration)
8. [Security Configuration](#security-configuration)
9. [Performance Tuning](#performance-tuning)
10. [Deployment Checklist](#deployment-checklist)

---

## Overview

This guide provides comprehensive configuration instructions for the AI BTC BOT platform. Follow these steps carefully to ensure proper system setup and optimal performance.

### Configuration Files Location

```
/config/
├── database.php              # Database connection settings
├── config.php               # Main application configuration
├── email.php                # Email/SMTP settings
├── payment.php              # Payment gateway configuration
└── constants.php            # System constants and limits
```

---

## Environment Setup

### 1. Server Requirements

**Minimum Requirements:**
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.1+
- 2GB RAM minimum
- 10GB disk space
- SSL certificate (HTTPS required)

**Recommended:**
- PHP 8.0+
- MySQL 8.0+ or MariaDB 10.5+
- 4GB RAM
- 50GB SSD storage
- Dedicated IP address

### 2. PHP Extensions

Enable the following PHP extensions in `php.ini`:

```ini
extension=pdo
extension=pdo_mysql
extension=mbstring
extension=json
extension=openssl
extension=curl
extension=gd
extension=zip
extension=xml
```

### 3. PHP Configuration

Edit `php.ini` with recommended settings:

```ini
; Maximum execution time (seconds)
max_execution_time = 300

; Maximum input time (seconds)
max_input_time = 300

; Maximum upload file size
upload_max_filesize = 10M
post_max_size = 10M

; Memory limit
memory_limit = 256M

; Error reporting (production)
error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; Session settings
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.gc_maxlifetime = 7200

; Timezone
date.timezone = Asia/Seoul
```

---

## Database Configuration

### File: `/config/database.php`

```php
<?php
/**
 * Database Configuration
 * AI BTC BOT - Neural Referral Platform
 */

class Database {
    private static $instance = null;
    private $connection;

    // Database credentials
    private $host = 'localhost';
    private $dbname = 'aibb_nrp';
    private $username = 'aibb_user';
    private $password = 'YOUR_SECURE_PASSWORD_HERE';
    private $charset = 'utf8mb4';

    private function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}",
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Database helper methods
    public function select($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function selectOne($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function insert($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);
        return $this->connection->lastInsertId();
    }

    public function update($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($params);
    }

    public function delete($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        return $stmt->execute($params);
    }

    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    public function commit() {
        return $this->connection->commit();
    }

    public function rollback() {
        return $this->connection->rollBack();
    }
}
?>
```

### Configuration Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `$host` | Database server hostname | `localhost`, `127.0.0.1`, or remote IP |
| `$dbname` | Database name | `aibb_nrp` |
| `$username` | Database username | `aibb_user` |
| `$password` | Database password | Strong password (20+ chars) |
| `$charset` | Character encoding | `utf8mb4` (required for emojis) |

### Creating Database User

```sql
-- Create database
CREATE DATABASE aibb_nrp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace PASSWORD with strong password)
CREATE USER 'aibb_user'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';

-- Grant privileges
GRANT ALL PRIVILEGES ON aibb_nrp.* TO 'aibb_user'@'localhost';

-- For remote access (optional)
CREATE USER 'aibb_user'@'%' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON aibb_nrp.* TO 'aibb_user'@'%';

-- Apply changes
FLUSH PRIVILEGES;
```

### MySQL Configuration Optimization

Edit `/etc/mysql/my.cnf` or `/etc/my.cnf`:

```ini
[mysqld]
# Connection settings
max_connections = 200
max_allowed_packet = 64M
connect_timeout = 10
wait_timeout = 28800

# Buffer sizes
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_log_buffer_size = 8M

# Character set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# Query cache (MySQL 5.7)
query_cache_type = 1
query_cache_size = 128M
query_cache_limit = 2M

# Slow query log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2

# Binary logging
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
max_binlog_size = 100M
```

---

## Application Settings

### File: `/config/config.php`

```php
<?php
/**
 * Application Configuration
 * AI BTC BOT - Neural Referral Platform
 */

// ============================================
// ENVIRONMENT SETTINGS
// ============================================
define('ENVIRONMENT', 'production'); // 'development' or 'production'
define('DEBUG_MODE', false);         // Set to false in production

// ============================================
// SITE SETTINGS
// ============================================
define('SITE_NAME', 'AI BTC BOT');
define('SITE_URL', 'https://yourdomain.com');
define('SITE_DESCRIPTION', 'The Road to 1 BTC Starting with $50');
define('ADMIN_EMAIL', 'admin@yourdomain.com');
define('SUPPORT_EMAIL', 'support@yourdomain.com');
define('NOREPLY_EMAIL', 'noreply@yourdomain.com');

// ============================================
// PATHS
// ============================================
define('ROOT_PATH', dirname(__DIR__));
define('API_PATH', ROOT_PATH . '/api');
define('CLASSES_PATH', ROOT_PATH . '/classes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');

// ============================================
// SECURITY SETTINGS
// ============================================
define('SESSION_LIFETIME', 7200);          // 2 hours (seconds)
define('PASSWORD_COST', 12);               // Bcrypt cost factor
define('MAX_LOGIN_ATTEMPTS', 5);           // Max failed login attempts
define('LOGIN_LOCKOUT_TIME', 900);         // 15 minutes (seconds)
define('CSRF_TOKEN_LENGTH', 32);           // CSRF token length
define('API_RATE_LIMIT', 100);             // Requests per minute
define('ADMIN_IP_WHITELIST', []);          // Empty = allow all

// ============================================
// PACKAGE SETTINGS
// ============================================
define('PACKAGE_50_PRICE', 50.00);
define('PACKAGE_100_PRICE', 100.00);
define('PACKAGE_DURATION_DAYS', 730);      // 2 years
define('PACKAGE_50_AVATAR_TRIGGER', 150.00);
define('PACKAGE_100_AVATAR_TRIGGER', 300.00);
define('AVATAR_COST', 50.00);

// ============================================
// BONUS SETTINGS
// ============================================
define('MAX_BONUS_LEVELS', 15);
define('PACKAGE_50_BONUS_L1_5', 4.00);     // Levels 1-5
define('PACKAGE_50_BONUS_L6_15', 2.00);    // Levels 6-15
define('PACKAGE_100_BONUS_L1_5', 9.00);    // Levels 1-5
define('PACKAGE_100_BONUS_L6_15', 4.50);   // Levels 6-15

// Earning depth by direct referrals
define('EARNING_DEPTH_1_REFERRAL', 5);
define('EARNING_DEPTH_2_REFERRALS', 10);
define('EARNING_DEPTH_3_REFERRALS', 15);

// ============================================
// WITHDRAWAL SETTINGS
// ============================================
define('MIN_WITHDRAWAL_AMOUNT', 50.00);
define('MAX_WITHDRAWAL_AMOUNT', 10000.00);
define('WITHDRAWAL_FEE_PERCENT', 5);       // 5% fee
define('WITHDRAWAL_PROCESSING_DAYS', 2);   // 2 business days
define('BTC_WITHDRAWAL_MINIMUM', 1.00);    // 1 BTC minimum

// ============================================
// PAYMENT SETTINGS
// ============================================
define('USDT_SYSTEM_ADDRESS', 'TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q');
define('PAYMENT_CONFIRMATION_BLOCKS', 1);  // Minimum confirmations
define('AUTO_VERIFY_PAYMENTS', true);      // Auto-verify via API

// ============================================
// FILE UPLOAD SETTINGS
// ============================================
define('MAX_UPLOAD_SIZE', 10485760);       // 10MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// ============================================
// LOGGING SETTINGS
// ============================================
define('LOG_ERRORS', true);
define('LOG_QUERIES', false);              // Set to true for debugging
define('LOG_API_REQUESTS', true);
define('LOG_RETENTION_DAYS', 30);

// ============================================
// EMAIL SETTINGS
// ============================================
define('EMAIL_VERIFICATION_EXPIRY', 600);  // 10 minutes
define('PASSWORD_RESET_EXPIRY', 1800);     // 30 minutes

// ============================================
// TIMEZONE
// ============================================
date_default_timezone_set('Asia/Seoul');

// ============================================
// ERROR HANDLING
// ============================================
if (ENVIRONMENT === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ============================================
// SESSION CONFIGURATION
// ============================================
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
ini_set('session.cookie_lifetime', SESSION_LIFETIME);

// ============================================
// AUTOLOAD CLASSES
// ============================================
spl_autoload_register(function ($class) {
    $file = CLASSES_PATH . '/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
?>
```

---

## Email Configuration

### File: `/config/email.php`

```php
<?php
/**
 * Email Configuration
 * Using PHPMailer for SMTP
 */

// ============================================
// SMTP SETTINGS
// ============================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);                          // 587 for TLS, 465 for SSL
define('SMTP_ENCRYPTION', 'tls');                  // 'tls' or 'ssl'
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');      // Use app-specific password

// ============================================
// EMAIL ADDRESSES
// ============================================
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'AI BTC BOT');
define('SMTP_REPLY_TO', 'support@yourdomain.com');

// ============================================
// EMAIL SETTINGS
// ============================================
define('EMAIL_DEBUG', 0);                          // 0=off, 1=client, 2=server
define('EMAIL_CHARSET', 'UTF-8');
define('EMAIL_TIMEOUT', 30);                       // Seconds
define('EMAIL_KEEP_ALIVE', true);

// ============================================
// TEMPLATE SETTINGS
// ============================================
define('EMAIL_TEMPLATE_PATH', ROOT_PATH . '/templates/email/');
define('EMAIL_LOGO_URL', SITE_URL . '/images/logo.png');

/**
 * Email Configuration Class
 */
class EmailConfig {
    public static function getSMTPConfig() {
        return [
            'host'       => SMTP_HOST,
            'port'       => SMTP_PORT,
            'encryption' => SMTP_ENCRYPTION,
            'username'   => SMTP_USERNAME,
            'password'   => SMTP_PASSWORD,
            'from_email' => SMTP_FROM_EMAIL,
            'from_name'  => SMTP_FROM_NAME,
            'reply_to'   => SMTP_REPLY_TO,
            'charset'    => EMAIL_CHARSET,
            'timeout'    => EMAIL_TIMEOUT,
            'debug'      => EMAIL_DEBUG,
        ];
    }

    public static function getVerificationTemplate($code, $userName) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; background: #0a0a1f; color: #fff; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { text-align: center; padding: 20px; }
                .code-box {
                    background: linear-gradient(135deg, #00ffff, #ff00ff);
                    padding: 20px;
                    text-align: center;
                    font-size: 32px;
                    font-weight: bold;
                    letter-spacing: 5px;
                    margin: 20px 0;
                    border-radius: 10px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>AI BTC BOT</h1>
                    <p>Email Verification</p>
                </div>
                <p>Hello {$userName},</p>
                <p>Your email verification code is:</p>
                <div class='code-box'>{$code}</div>
                <p>This code will expire in 10 minutes.</p>
                <p>If you did not request this code, please ignore this email.</p>
                <hr>
                <p style='font-size: 12px; color: #888;'>
                    This is an automated email from AI BTC BOT. Please do not reply.
                </p>
            </div>
        </body>
        </html>
        ";
    }
}
?>
```

### Gmail Configuration (Example)

1. Enable 2-Step Verification in your Google Account
2. Generate App Password:
   - Go to Google Account Settings
   - Security → 2-Step Verification → App passwords
   - Generate password for "Mail"
   - Use this password in `SMTP_PASSWORD`

### Alternative: SendGrid Configuration

```php
define('SMTP_HOST', 'smtp.sendgrid.net');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USERNAME', 'apikey');
define('SMTP_PASSWORD', 'YOUR_SENDGRID_API_KEY');
```

---

## Payment Gateway Settings

### File: `/config/payment.php`

```php
<?php
/**
 * Payment Gateway Configuration
 * USDT TRC20 (Tron Network)
 */

// ============================================
// TRON NETWORK SETTINGS
// ============================================
define('TRON_NETWORK', 'mainnet');                 // 'mainnet' or 'testnet'
define('TRON_API_URL', 'https://api.trongrid.io');
define('TRON_API_KEY', 'YOUR_TRONGRID_API_KEY');   // Get from https://www.trongrid.io
define('TRON_API_TIMEOUT', 30);

// ============================================
// USDT TRC20 SETTINGS
// ============================================
define('USDT_CONTRACT_ADDRESS', 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t');
define('USDT_SYSTEM_ADDRESS', 'TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q');
define('USDT_DECIMALS', 6);

// ============================================
// PAYMENT VERIFICATION
// ============================================
define('MIN_CONFIRMATIONS', 1);
define('AUTO_VERIFY_ENABLED', true);
define('VERIFY_INTERVAL', 60);                     // Check every 60 seconds
define('PAYMENT_TIMEOUT', 3600);                   // 1 hour timeout
define('MAX_VERIFICATION_ATTEMPTS', 10);

// ============================================
// BNB SMART CHAIN (BTC WITHDRAWAL)
// ============================================
define('BNB_NETWORK', 'mainnet');
define('BNB_RPC_URL', 'https://bsc-dataseed.binance.org/');
define('BNB_CHAIN_ID', 56);                        // 56 for mainnet, 97 for testnet

/**
 * Payment Configuration Class
 */
class PaymentConfig {
    public static function getTronConfig() {
        return [
            'network'         => TRON_NETWORK,
            'api_url'         => TRON_API_URL,
            'api_key'         => TRON_API_KEY,
            'api_timeout'     => TRON_API_TIMEOUT,
            'system_address'  => USDT_SYSTEM_ADDRESS,
            'contract_address'=> USDT_CONTRACT_ADDRESS,
            'decimals'        => USDT_DECIMALS,
        ];
    }

    public static function verifyTransaction($txHash) {
        $config = self::getTronConfig();
        $url = "{$config['api_url']}/v1/transactions/{$txHash}";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $config['api_timeout'],
            CURLOPT_HTTPHEADER     => [
                "TRON-PRO-API-KEY: {$config['api_key']}"
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'message' => 'API request failed'];
        }

        $data = json_decode($response, true);

        // Verify transaction details
        if (isset($data['ret'][0]['contractRet'])
            && $data['ret'][0]['contractRet'] === 'SUCCESS') {
            return [
                'success'      => true,
                'amount'       => self::parseUSDTAmount($data),
                'from_address' => $data['raw_data']['contract'][0]['parameter']['value']['owner_address'],
                'to_address'   => $data['raw_data']['contract'][0]['parameter']['value']['to_address'],
                'timestamp'    => $data['raw_data']['timestamp'],
            ];
        }

        return ['success' => false, 'message' => 'Transaction verification failed'];
    }

    private static function parseUSDTAmount($txData) {
        // Extract amount from contract data
        // USDT uses 6 decimals
        $value = $txData['raw_data']['contract'][0]['parameter']['value']['amount'] ?? 0;
        return $value / pow(10, USDT_DECIMALS);
    }
}
?>
```

### TronGrid API Setup

1. Visit https://www.trongrid.io
2. Create free account
3. Generate API key
4. Copy key to `TRON_API_KEY`
5. Free tier: 1000 requests/day

---

## API Endpoint Configuration

### File: `/config/api.php`

```php
<?php
/**
 * API Configuration
 */

// ============================================
// API SETTINGS
// ============================================
define('API_VERSION', 'v1');
define('API_BASE_PATH', '/api');
define('API_TIMEOUT', 30);
define('API_MAX_REQUESTS_PER_MINUTE', 100);

// ============================================
// CORS SETTINGS
// ============================================
define('CORS_ENABLED', true);
define('CORS_ALLOWED_ORIGINS', [
    'https://yourdomain.com',
    'https://www.yourdomain.com',
]);
define('CORS_ALLOWED_METHODS', ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']);
define('CORS_ALLOWED_HEADERS', ['Content-Type', 'Authorization', 'X-Requested-With']);
define('CORS_MAX_AGE', 86400); // 24 hours

// ============================================
// RESPONSE FORMATS
// ============================================
define('API_DEFAULT_FORMAT', 'json');
define('API_PRETTY_PRINT', false);

// ============================================
// RATE LIMITING
// ============================================
define('RATE_LIMIT_ENABLED', true);
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_PERIOD', 60); // seconds
define('RATE_LIMIT_STORAGE', 'database'); // 'database' or 'redis'

/**
 * API Helper Class
 */
class APIConfig {
    public static function setCorsHeaders() {
        if (!CORS_ENABLED) return;

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, CORS_ALLOWED_ORIGINS)) {
            header("Access-Control-Allow-Origin: $origin");
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', CORS_ALLOWED_METHODS));
        header('Access-Control-Allow-Headers: ' . implode(', ', CORS_ALLOWED_HEADERS));
        header('Access-Control-Max-Age: ' . CORS_MAX_AGE);

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    public static function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');

        if (API_PRETTY_PRINT) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    public static function errorResponse($message, $code = 'ERROR', $statusCode = 400) {
        self::jsonResponse([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $statusCode);
    }

    public static function successResponse($data, $message = 'Success') {
        self::jsonResponse([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], 200);
    }
}
?>
```

---

## Security Configuration

### File: `/config/security.php`

```php
<?php
/**
 * Security Configuration
 */

// ============================================
// PASSWORD POLICY
// ============================================
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', false);
define('PASSWORD_REQUIRE_LOWERCASE', false);
define('PASSWORD_REQUIRE_NUMBER', false);
define('PASSWORD_REQUIRE_SPECIAL', false);
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 12);

// ============================================
// SESSION SECURITY
// ============================================
define('SESSION_NAME', 'AIBB_SESSION');
define('SESSION_COOKIE_SECURE', true);
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SAMESITE', 'Lax');
define('SESSION_REGENERATE_INTERVAL', 300); // 5 minutes

// ============================================
// CSRF PROTECTION
// ============================================
define('CSRF_TOKEN_NAME', '_csrf_token');
define('CSRF_TOKEN_LENGTH', 32);
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour

// ============================================
// INPUT VALIDATION
// ============================================
define('MAX_INPUT_LENGTH', 1000);
define('ALLOWED_HTML_TAGS', '<b><i><u><a><p><br>');

// ============================================
// IP SECURITY
// ============================================
define('BLOCK_TOR_EXITS', false);
define('BLOCK_PROXIES', false);
define('ADMIN_IP_WHITELIST', [
    // '192.168.1.100',
    // '10.0.0.50',
]);

// ============================================
// FILE UPLOAD SECURITY
// ============================================
define('UPLOAD_MAX_SIZE', 10485760); // 10MB
define('UPLOAD_ALLOWED_TYPES', [
    'image/jpeg',
    'image/png',
    'application/pdf',
]);
define('UPLOAD_CHECK_MIME', true);
define('UPLOAD_QUARANTINE', true);

/**
 * Security Helper Class
 */
class SecurityConfig {
    public static function generateCSRFToken() {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
            $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    public static function validateCSRFToken($token) {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }

        $sessionToken = $_SESSION[CSRF_TOKEN_NAME];
        $tokenTime = $_SESSION[CSRF_TOKEN_NAME . '_time'] ?? 0;

        if (time() - $tokenTime > CSRF_TOKEN_EXPIRY) {
            unset($_SESSION[CSRF_TOKEN_NAME]);
            unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
    }

    public static function validatePassword($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            return false;
        }

        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            return false;
        }

        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            return false;
        }

        if (PASSWORD_REQUIRE_NUMBER && !preg_match('/[0-9]/', $password)) {
            return false;
        }

        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            return false;
        }

        return true;
    }
}
?>
```

---

## Performance Tuning

### File: `/config/performance.php`

```php
<?php
/**
 * Performance Configuration
 */

// ============================================
// CACHING
// ============================================
define('CACHE_ENABLED', true);
define('CACHE_DRIVER', 'file');  // 'file', 'redis', 'memcached'
define('CACHE_TTL', 3600);       // 1 hour default
define('CACHE_PATH', ROOT_PATH . '/cache');

// Redis settings (if using Redis)
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', '');
define('REDIS_DATABASE', 0);

// ============================================
// DATABASE OPTIMIZATION
// ============================================
define('DB_PERSISTENT_CONNECTIONS', false);
define('DB_QUERY_CACHE_ENABLED', true);
define('DB_SLOW_QUERY_LOG', true);
define('DB_SLOW_QUERY_TIME', 2); // seconds

// ============================================
// COMPRESSION
// ============================================
define('GZIP_ENABLED', true);
define('GZIP_LEVEL', 6); // 1-9, higher = more compression

// ============================================
// ASSET OPTIMIZATION
// ============================================
define('MINIFY_HTML', true);
define('MINIFY_CSS', true);
define('MINIFY_JS', true);
define('COMBINE_CSS', false);
define('COMBINE_JS', false);

// ============================================
// CDN SETTINGS
// ============================================
define('CDN_ENABLED', false);
define('CDN_URL', 'https://cdn.yourdomain.com');
define('CDN_ASSETS', ['css', 'js', 'images']);

/**
 * Cache Helper Class
 */
class CacheConfig {
    private static $instance = null;

    public static function get($key) {
        if (!CACHE_ENABLED) return null;

        $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';

        if (!file_exists($cacheFile)) {
            return null;
        }

        $data = unserialize(file_get_contents($cacheFile));

        if ($data['expires'] < time()) {
            unlink($cacheFile);
            return null;
        }

        return $data['value'];
    }

    public static function set($key, $value, $ttl = CACHE_TTL) {
        if (!CACHE_ENABLED) return false;

        if (!is_dir(CACHE_PATH)) {
            mkdir(CACHE_PATH, 0755, true);
        }

        $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';

        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
        ];

        return file_put_contents($cacheFile, serialize($data)) !== false;
    }

    public static function delete($key) {
        $cacheFile = CACHE_PATH . '/' . md5($key) . '.cache';

        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }

        return false;
    }

    public static function clear() {
        if (!is_dir(CACHE_PATH)) return true;

        $files = glob(CACHE_PATH . '/*.cache');

        foreach ($files as $file) {
            unlink($file);
        }

        return true;
    }
}
?>
```

---

## Deployment Checklist

### Pre-Deployment

- [ ] Update all configuration files with production values
- [ ] Set `ENVIRONMENT = 'production'`
- [ ] Set `DEBUG_MODE = false`
- [ ] Update database credentials
- [ ] Configure SMTP settings
- [ ] Set up TronGrid API key
- [ ] Update `SITE_URL` to production domain
- [ ] Generate strong passwords (20+ characters)
- [ ] Review security settings

### Database

- [ ] Create production database
- [ ] Import schema from `/database/schema.sql`
- [ ] Create database user with limited privileges
- [ ] Set up database backups (daily)
- [ ] Configure binary logging
- [ ] Optimize MySQL/MariaDB settings

### Web Server

- [ ] Install SSL certificate (HTTPS required)
- [ ] Configure virtual host
- [ ] Set proper file permissions (755 folders, 644 files)
- [ ] Disable directory listing
- [ ] Configure `.htaccess` or Nginx config
- [ ] Set up log rotation
- [ ] Configure firewall rules

### PHP

- [ ] Install required PHP extensions
- [ ] Update `php.ini` settings
- [ ] Set proper timezone
- [ ] Configure error logging
- [ ] Disable dangerous functions
- [ ] Set memory and execution limits

### Security

- [ ] Enable HTTPS redirect
- [ ] Set secure session cookies
- [ ] Configure CORS properly
- [ ] Enable rate limiting
- [ ] Set up intrusion detection (optional)
- [ ] Configure backup system
- [ ] Set up monitoring/alerts

### Testing

- [ ] Test user registration
- [ ] Test email verification
- [ ] Test package purchase flow
- [ ] Test bonus distribution
- [ ] Test withdrawal requests
- [ ] Test admin dashboard
- [ ] Test binary tree placement
- [ ] Load testing (optional)

### Post-Deployment

- [ ] Monitor error logs
- [ ] Check database performance
- [ ] Verify email delivery
- [ ] Test payment verification
- [ ] Monitor API response times
- [ ] Set up automated backups
- [ ] Create admin account
- [ ] Document any custom changes

---

## Environment-Specific Configurations

### Development Environment

```php
define('ENVIRONMENT', 'development');
define('DEBUG_MODE', true);
define('SITE_URL', 'http://localhost');
error_reporting(E_ALL);
ini_set('display_errors', '1');
```

### Staging Environment

```php
define('ENVIRONMENT', 'staging');
define('DEBUG_MODE', true);
define('SITE_URL', 'https://staging.yourdomain.com');
define('AUTO_VERIFY_PAYMENTS', false); // Manual verification
```

### Production Environment

```php
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);
define('SITE_URL', 'https://yourdomain.com');
define('AUTO_VERIFY_PAYMENTS', true);
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', '0');
```

---

## Troubleshooting

### Common Issues

**Database Connection Failed:**
- Check database credentials
- Verify MySQL service is running
- Check firewall allows port 3306
- Verify user has proper privileges

**Email Not Sending:**
- Check SMTP credentials
- Verify port 587/465 is open
- Check spam folder
- Enable less secure apps (Gmail)
- Use app-specific password

**Payment Verification Failed:**
- Verify TronGrid API key
- Check API rate limits
- Verify transaction hash format
- Check system address matches

**Session Expired:**
- Increase `SESSION_LIFETIME`
- Check server time sync
- Verify session directory writable

---

**AI BTC BOT Configuration Guide v1.0**

*Last Updated: 2025-10-31*

For additional support, contact: support@yourdomain.com
