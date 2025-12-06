# AI BTC BOT - Neural Referral Platform (NRP)

## Project Overview

**AI BTC BOT** is a blockchain-based Network Referral Platform that enables participants to accumulate Bitcoin through a dual-package membership system and binary tree organization structure. Starting with as little as $50 USDT, members can systematically build their Bitcoin portfolio through community growth and automated reward distribution.

### Mission

To democratize Bitcoin accumulation by providing a structured, transparent, and automated path for individuals to achieve 1 BTC through network growth and smart technology.

### Key Differentiators

- **Low Entry Barrier**: Start with just $50 USDT
- **Blockchain-Based**: Transparent USDT (TRC20) payments on Tron network
- **Automated Distribution**: Real-time commission calculations with 15-level bonus system
- **Binary Tree Structure**: Fair spillover placement ensures growth for all members
- **Avatar System**: Automated bonus account generation for forced BTC savings
- **Dual Currency Model**: USDT for liquidity, BTC for long-term accumulation

---

## Directory Structure

```
/template_nrp/
├── README.md                          # This file
├── docs/
│   ├── SYSTEM_DESIGN.md              # System architecture and design
│   └── CONFIG_TEMPLATE.md            # Configuration guide
├── api/                               # Backend API endpoints
│   ├── auth/                          # Authentication APIs
│   ├── user/                          # User management
│   ├── sales/                         # Package purchases
│   ├── bonus/                         # Bonus distribution
│   ├── withdrawal/                    # Withdrawal processing
│   ├── organization/                  # Binary tree management
│   └── admin/                         # Administration APIs
├── html/                              # Frontend pages
│   ├── index.html                     # Landing page
│   ├── login.html                     # User login
│   ├── signup.html                    # Registration
│   ├── dashboard.html                 # User dashboard
│   ├── organization.html              # Binary tree visualization
│   ├── purchase.html                  # Package purchase
│   ├── purchase-avatar.html           # Avatar purchase
│   ├── withdrawal.html                # Withdrawal request
│   └── admin/                         # Admin pages
├── js/                                # JavaScript modules
│   ├── api-client.js                  # API communication
│   ├── popup-menu.js                  # Navigation menu
│   ├── language.js                    # Multi-language support
│   └── organization-modal.js          # Tree visualization
├── css/                               # Stylesheets
│   ├── neural-pulse.css               # Main theme
│   └── organization-modal.css         # Tree modal styles
├── database/                          # Database files
│   ├── schema.sql                     # Complete database schema
│   ├── create_bonuses_table.sql       # Bonus system tables
│   └── create_withdrawals_table.sql   # Withdrawal tables
└── config/                            # Configuration files
    ├── database.php                   # Database connection
    └── config.php                     # Application settings
```

---

## Features

### 1. Organization System (Binary Tree)

- **Binary Tree Structure**: Each member can have maximum 2 direct downlines
- **Automatic Spillover**: New members automatically placed in first available position
- **BFS Algorithm**: Breadth-First Search ensures fair distribution
- **Multi-Level Tracking**: Track up to 15 levels deep
- **Visual Organization Chart**: Interactive 3D tree visualization

**Referral Depth Requirements:**
- 1 Direct Referral: Earn from 5 levels
- 2 Direct Referrals: Earn from 10 levels
- 3+ Direct Referrals: Earn from all 15 levels

### 2. Purchase System (Dual Packages)

**Package $50**
- Investment: $50 USDT
- Level 1-5 Commission: $4 USDT per member
- Level 6-15 Commission: $2 USDT per member
- Avatar Generation: Every $150 earned
- Avatar Multiplier: 1 Avatar per trigger
- Maximum Potential Earnings: $131,192 USDT

**Package $100**
- Investment: $100 USDT
- Level 1-5 Commission: $9 USDT per member
- Level 6-15 Commission: $4.50 USDT per member
- Avatar Generation: Every $300 earned
- Avatar Multiplier: 2 Avatars per trigger
- Maximum Potential Earnings: $295,182 USDT

**Payment Method:**
- USDT (TRC20) via Tron Network
- System Address: TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q
- Transaction verification via blockchain API
- Automatic activation upon confirmation

### 3. Withdrawal System

**Withdrawal Rules:**
- Minimum Withdrawal: $50 USDT equivalent
- Processing Time: 24-48 hours
- Withdrawal Fee: 5% platform fee
- Destination: User's registered BNB Smart Chain address
- Status Tracking: Pending → Approved → Completed

**Withdrawal Types:**
- USDT Direct Withdrawal
- BTC Accumulation (Avatar earnings)
- Point-to-Crypto Swap (TRX, BNB, XRP, VCDAO, DOBUY)

### 4. Bonus System (15-Level)

**Bonus Distribution:**
- Automatic calculation on every package purchase
- Real-time distribution to upline members
- Separate tracking for main account vs. avatar bonuses
- Two-tier bonus rates: Higher for levels 1-5, reduced for 6-15

**Bonus Calculation Example (Package $100):**

| Level | Members | Commission | Level Total | Cumulative |
|-------|---------|------------|-------------|------------|
| 1     | 2       | $9         | $18         | $18        |
| 2     | 4       | $9         | $36         | $54        |
| 3     | 8       | $9         | $72         | $126       |
| 4     | 16      | $9         | $144        | $270       |
| 5     | 32      | $9         | $288        | $558       |
| 6     | 64      | $4.50      | $288        | $846       |
| 7     | 128     | $4.50      | $576        | $1,422     |
| ...   | ...     | ...        | ...         | ...        |
| 15    | 32,768  | $4.50      | $147,456    | $295,182   |

**Total Network**: 65,535 members (complete binary tree)

### 5. Avatar System

**Avatar Generation:**
- Automatically created when earning thresholds are met
- $50 Package: 1 avatar per $150 earned
- $100 Package: 2 avatars per $300 earned
- Avatars earn independently using same commission structure
- All avatar earnings automatically convert to BTC

**Avatar Characteristics:**
- Distinct visual identity (different color coding)
- Automatic placement in binary tree
- Follow identical referral and bonus rules
- Earnings locked in Bitcoin (forced savings)
- Withdrawal available when 1 BTC accumulated

**Example Avatar Generation ($100 Package):**

| Cumulative Earnings | Avatars Generated | Total Avatars |
|---------------------|-------------------|---------------|
| $300                | 2                 | 2             |
| $600                | 2                 | 4             |
| $1,500              | 2                 | 10            |
| $3,000              | 2                 | 20            |
| $15,000             | 2                 | 100           |
| $150,000            | 2                 | 1,000         |

---

## Installation Guide

### Prerequisites

- **Web Server**: Apache 2.4+ or Nginx
- **PHP**: Version 7.4 or higher
- **Database**: MySQL 5.7+ or MariaDB 10.1+
- **SSL Certificate**: Required for HTTPS
- **PHP Extensions**:
  - PDO
  - pdo_mysql
  - mbstring
  - json
  - openssl
  - curl

### Step 1: Database Setup

1. Create a new MySQL database:
```sql
CREATE DATABASE aibb_nrp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Create database user:
```sql
CREATE USER 'aibb_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON aibb_nrp.* TO 'aibb_user'@'localhost';
FLUSH PRIVILEGES;
```

3. Import database schema:
```bash
mysql -u aibb_user -p aibb_nrp < database/schema.sql
```

### Step 2: File Upload

1. Upload all files to your web server:
```bash
# Using FTP or direct server access
/var/www/html/aibb/
├── api/
├── html/
├── js/
├── css/
├── config/
└── ...
```

2. Set proper permissions:
```bash
chmod 755 /var/www/html/aibb
chmod 644 /var/www/html/aibb/*.php
chmod 600 /var/www/html/aibb/config/database.php
```

### Step 3: Configuration

1. Copy configuration template:
```bash
cp config/database.example.php config/database.php
```

2. Edit database configuration:
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'aibb_nrp');
define('DB_USER', 'aibb_user');
define('DB_PASS', 'your_secure_password');
define('DB_CHARSET', 'utf8mb4');
```

3. Edit application settings:
```php
// config/config.php
define('SITE_URL', 'https://yourdomain.com');
define('ADMIN_EMAIL', 'admin@yourdomain.com');
define('USDT_SYSTEM_ADDRESS', 'TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q');
```

### Step 4: Admin Setup

1. Create admin account via command line:
```bash
php admin/setup-admin.php
```

2. Or manually insert into database:
```sql
INSERT INTO users (user_id, password, name, email, phone, usdt_address, email_verified, status, role)
VALUES ('admin', '$2y$12$...', 'Administrator', 'admin@example.com', '010-0000-0000', '0x0000000000000000000000000000000000000000', 1, 'active', 'super');
```

### Step 5: Web Server Configuration

**Apache (.htaccess):**
```apache
RewriteEngine On
RewriteBase /

# Redirect to HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# API routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1 [L,QSA]
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /var/www/html/aibb/html;
    index index.html;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    location /api/ {
        try_files $uri $uri/ /api/index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}
```

### Step 6: Testing

1. Access the application:
   - User: `https://yourdomain.com/`
   - Admin: `https://yourdomain.com/admin/`

2. Test registration:
   - Create test user account
   - Verify email functionality
   - Test USDT address validation

3. Test purchase flow:
   - Mock USDT transaction
   - Verify bonus distribution
   - Check avatar generation

---

## Configuration Instructions

### Database Connection

Edit `/config/database.php`:

```php
<?php
class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $host = 'localhost';
        $dbname = 'aibb_nrp';
        $username = 'aibb_user';
        $password = 'your_password';

        try {
            $this->connection = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('Database connection error');
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
}
?>
```

### Email Configuration

Edit `/config/email.php`:

```php
<?php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'AI BTC BOT');
?>
```

### Payment Gateway

Edit `/config/payment.php`:

```php
<?php
// USDT TRC20 Configuration
define('USDT_SYSTEM_ADDRESS', 'TSMZdowTbRC7wMKKkEjSL3LXss9coZW16Q');
define('TRON_API_KEY', 'your-trongrid-api-key');
define('TRON_API_URL', 'https://api.trongrid.io');

// Payment verification
define('AUTO_VERIFY_ENABLED', true);
define('MIN_CONFIRMATIONS', 1);
?>
```

---

## Usage Examples

### User Registration

```javascript
// API Call Example
const response = await fetch('/api/auth/register.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        user_id: 'john_doe',
        password: 'SecurePass123!',
        name: 'John Doe',
        email: 'john@example.com',
        phone: '+1-555-0123',
        usdt_address: '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb',
        referral_id: 'sponsor_user'  // Optional
    })
});

const result = await response.json();
console.log(result);
// {success: true, message: "Registration successful", user_id: 123}
```

### Package Purchase

```javascript
// Step 1: Initiate purchase
const purchaseResponse = await fetch('/api/sales/purchase.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + userToken
    },
    body: JSON.stringify({
        package_id: 1,  // Package $50 or Package $100
        payment_method: 'USDT_TRC20'
    })
});

const purchase = await purchaseResponse.json();
// {success: true, purchase_id: 456, payment_address: "TSM...", amount: 50}

// Step 2: User sends USDT to payment_address
// Step 3: System verifies transaction automatically
```

### View Bonus History

```javascript
const bonusResponse = await fetch('/api/bonus/list.php', {
    headers: {
        'Authorization': 'Bearer ' + userToken
    }
});

const bonuses = await bonusResponse.json();
console.log(bonuses);
// {
//   success: true,
//   bonuses: [
//     {id: 1, from_user: "john_doe", level: 3, amount: 9, date: "2025-01-15"},
//     {id: 2, from_user: "jane_smith", level: 5, amount: 9, date: "2025-01-16"}
//   ]
// }
```

### Request Withdrawal

```javascript
const withdrawalResponse = await fetch('/api/withdrawal/request.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer ' + userToken
    },
    body: JSON.stringify({
        amount: 100,
        withdrawal_address: '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb',
        currency: 'USDT'
    })
});

const withdrawal = await withdrawalResponse.json();
// {success: true, withdrawal_id: 789, status: "pending", fee: 5}
```

---

## Feature List

### Core Features

- [x] **User Authentication**
  - Email verification with 6-digit code
  - Secure password hashing (bcrypt)
  - Session management with token-based auth
  - Multi-device login support

- [x] **Binary Tree Organization**
  - Automatic BFS placement algorithm
  - Real-time tree visualization
  - 15-level depth tracking
  - Spillover management

- [x] **Package System**
  - $50 USDT Package
  - $100 USDT Package
  - USDT (TRC20) payment integration
  - Blockchain transaction verification

- [x] **Bonus Distribution**
  - 15-level commission structure
  - Automatic bonus calculation
  - Real-time distribution
  - Separate main/avatar bonus tracking

- [x] **Avatar System**
  - Automatic avatar generation
  - Threshold-based triggers ($150/$300)
  - Independent earning accounts
  - BTC accumulation tracking

- [x] **Withdrawal System**
  - USDT withdrawal requests
  - BTC withdrawal (1 BTC minimum)
  - Multi-currency swap options
  - Automated processing queue

- [x] **Admin Dashboard**
  - User management
  - Purchase approval
  - Bonus monitoring
  - Withdrawal processing
  - System statistics
  - Activity logs

### Advanced Features

- [x] **Multi-Language Support**
  - English
  - Korean (한국어)
  - Chinese (中文)
  - Vietnamese (Tiếng Việt)
  - Tagalog (Filipino)

- [x] **Responsive Design**
  - Mobile-first approach
  - Desktop optimization
  - Tablet support
  - Neural Pulse Network theme

- [x] **Security**
  - SQL injection prevention (PDO)
  - XSS protection
  - CSRF tokens
  - Password strength validation
  - Rate limiting

- [x] **Analytics**
  - Real-time statistics
  - Earning projections
  - Network growth tracking
  - Performance metrics

---

## Support and Documentation

### Additional Resources

- **System Design**: See `/docs/SYSTEM_DESIGN.md` for architecture details
- **Configuration**: See `/docs/CONFIG_TEMPLATE.md` for setup guide
- **API Documentation**: See individual API endpoint files for usage
- **Database Schema**: See `/database/schema.sql` for complete structure

### Getting Help

- **Technical Support**: support@yourdomain.com
- **Admin Support**: admin@yourdomain.com
- **Community Forum**: https://forum.yourdomain.com
- **Video Tutorials**: https://yourdomain.com/tutorials

---

## License

Copyright 2025 AI BTC BOT. All Rights Reserved.

This software is proprietary and confidential. Unauthorized copying, distribution, or modification is strictly prohibited.

---

## Changelog

### Version 1.0.0 (2025-01-31)

Initial release featuring:
- Complete user authentication system
- Binary tree organization with BFS placement
- Dual package system ($50 and $100)
- 15-level bonus distribution
- Avatar generation and management
- Withdrawal processing
- Admin dashboard
- Multi-language support
- Neural Pulse Network UI theme

---

**AI BTC BOT - Building Bitcoin Wealth, One Block at a Time**

*The Road to 1 BTC Starts with $50*
