# AI BTC BOT - System Design Documentation

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Binary Tree Organization Structure](#binary-tree-organization-structure)
3. [15-Level Bonus System](#15-level-bonus-system)
4. [Package System](#package-system)
5. [Avatar Purchasing Mechanism](#avatar-purchasing-mechanism)
6. [Withdrawal Rules](#withdrawal-rules)
7. [Database Schema Overview](#database-schema-overview)
8. [API Architecture](#api-architecture)
9. [Security Design](#security-design)
10. [Performance Optimization](#performance-optimization)

---

## Architecture Overview

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     CLIENT LAYER                             │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Web Browser │  │ Mobile Web   │  │  Admin Panel │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                  PRESENTATION LAYER                          │
│  ┌──────────────────────────────────────────────────┐       │
│  │  HTML5 + CSS3 + Vanilla JavaScript               │       │
│  │  • Neural Pulse Network Theme                    │       │
│  │  • Responsive Design (Mobile-First)              │       │
│  │  • Multi-Language Support (5 languages)          │       │
│  └──────────────────────────────────────────────────┘       │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                   APPLICATION LAYER                          │
│  ┌──────────────────────────────────────────────────┐       │
│  │  PHP 7.4+ RESTful API                            │       │
│  │  • Authentication Service                        │       │
│  │  • User Management Service                       │       │
│  │  • Sales/Purchase Service                        │       │
│  │  • Bonus Distribution Service                    │       │
│  │  • Withdrawal Processing Service                 │       │
│  │  • Organization Tree Service                     │       │
│  │  • Admin Management Service                      │       │
│  └──────────────────────────────────────────────────┘       │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    BUSINESS LOGIC LAYER                      │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────┐  │
│  │ BinaryTree     │  │ BonusSystem    │  │ Avatar       │  │
│  │ Placement      │  │ Calculator     │  │ Generator    │  │
│  └────────────────┘  └────────────────┘  └──────────────┘  │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────┐  │
│  │ User           │  │ EmailVerify    │  │ Withdrawal   │  │
│  │ Manager        │  │ Service        │  │ Processor    │  │
│  └────────────────┘  └────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      DATA LAYER                              │
│  ┌──────────────────────────────────────────────────┐       │
│  │  MySQL/MariaDB Database (InnoDB Engine)          │       │
│  │  • users, sessions, packages, bonuses            │       │
│  │  • package_purchases, withdrawals                │       │
│  │  • avatar_purchases, user_bonus_balance          │       │
│  │  • bonus_settings, activity_logs                 │       │
│  └──────────────────────────────────────────────────┘       │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                 EXTERNAL SERVICES                            │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────┐  │
│  │ TronGrid API   │  │ SMTP Email     │  │ BNB Chain    │  │
│  │ (USDT TRC20)   │  │ Service        │  │ (BTC Wallet) │  │
│  └────────────────┘  └────────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### Technology Stack

**Frontend:**
- HTML5, CSS3 (Neural Pulse Network Theme)
- Vanilla JavaScript (ES6+)
- Fetch API for AJAX
- LocalStorage for client-side session

**Backend:**
- PHP 7.4+ (Object-Oriented)
- PDO for database access
- RESTful API architecture
- JSON data exchange

**Database:**
- MySQL 5.7+ / MariaDB 10.1+
- InnoDB engine (ACID compliance)
- UTF8MB4 character set
- Foreign key constraints

**Blockchain:**
- Tron Network (USDT TRC20)
- BNB Smart Chain (BTC withdrawal)
- TronGrid API integration

---

## Binary Tree Organization Structure

### Core Concepts

The binary tree organization is the foundation of the network structure. Each member can have a maximum of 2 direct downlines, creating a balanced binary tree.

### Key Properties

1. **Dual Tracking System:**
   - `referral_org`: Original sponsor (commission eligibility)
   - `referral_id`: Actual parent in tree (placement position)

2. **Automatic Placement:**
   - Uses Breadth-First Search (BFS) algorithm
   - Finds first available slot in sponsor's subtree
   - Ensures balanced tree growth

3. **Maximum Depth:**
   - 15 levels deep
   - Level 1 = 2 members, Level 15 = 32,768 members
   - Total network capacity per root: 65,535 members

### BFS Placement Algorithm

```
Input: referral_org (sponsor's user_id)
Output: referral_id (actual parent's user_id)

Algorithm BinaryTreePlacement:
1. Initialize queue with referral_org
2. While queue is not empty:
   a. Dequeue current_node
   b. Get children count of current_node
   c. If children count < 2:
      - Return current_node as placement position
   d. Else:
      - Enqueue all children of current_node
3. If no position found (should never happen):
   - Return error
```

### Implementation (PHP)

```php
class BinaryTreePlacement {
    public function findPlacement($referralOrg) {
        // Check if sponsor has less than 2 direct referrals
        $directCount = $this->getDirectReferralsCount($referralOrg);

        if ($directCount < 2) {
            return [
                'success' => true,
                'referral_id' => $referralOrg,
                'is_direct' => true
            ];
        }

        // BFS algorithm to find first available position
        $placement = $this->findEmptySlotBFS($referralOrg);

        return [
            'success' => true,
            'referral_id' => $placement,
            'is_direct' => false
        ];
    }

    private function findEmptySlotBFS($referralOrg) {
        $queue = [$referralOrg];
        $visited = [];

        while (!empty($queue)) {
            $currentUserId = array_shift($queue);

            if (in_array($currentUserId, $visited)) {
                continue;
            }
            $visited[] = $currentUserId;

            $children = $this->getChildren($currentUserId);

            if (count($children) < 2) {
                return $currentUserId; // Found available position
            }

            foreach ($children as $child) {
                $queue[] = $child['user_id'];
            }
        }

        return null;
    }
}
```

### Tree Visualization

```
                    SPONSOR (Level 0)
                   /                 \
              Member A             Member B
              (Level 1)            (Level 1)
             /        \           /        \
        Member C  Member D   Member E  Member F
        (Level 2) (Level 2) (Level 2) (Level 2)
       /      \
  Member G  Member H
  (Level 3) (Level 3)
```

**Direct Referrals**: Members A & B (under sponsor)
**Spillover Placements**: Members C-H (placed automatically)

---

## 15-Level Bonus System

### Commission Structure

The bonus system distributes commissions across 15 levels with two-tier rates:

**Package $50:**
- Levels 1-5: $4 USDT per new member
- Levels 6-15: $2 USDT per new member

**Package $100:**
- Levels 1-5: $9 USDT per new member
- Levels 6-15: $4.50 USDT per new member

### Earning Depth Requirements

Members must have direct referrals to unlock deeper levels:

| Direct Referrals | Earning Depth |
|------------------|---------------|
| 0                | No earnings   |
| 1                | 5 levels      |
| 2                | 10 levels     |
| 3+               | 15 levels     |

### Bonus Calculation Algorithm

```
Input: purchase_id (new package purchase)
Output: Array of bonuses distributed

Algorithm BonusDistribution:
1. Get purchase details (user_id, package_id, amount)
2. If purchase is_avatar = 1:
   - Skip bonus distribution (avatars don't generate bonuses)
   - Return success
3. Get bonus settings for package_id
4. Get upline users (max 15 levels):
   - Start from purchaser's referral_id
   - Recursively traverse up the tree
   - Stop at 15 levels or root
5. For each upline user (level 1 to 15):
   a. Get user's direct_referrals_count
   b. Calculate max_earning_depth:
      - 1 referral = 5 levels
      - 2 referrals = 10 levels
      - 3+ referrals = 15 levels
   c. If current_level <= max_earning_depth:
      - Get bonus_amount for current_level
      - Check if upline is avatar:
        * If avatar: credit to avatar owner
        * If regular: credit to user directly
      - Create bonus record
      - Update user_bonus_balance
6. Commit transaction
7. Return bonus distribution results
```

### Implementation (PHP)

```php
class BonusSystem {
    const DEPTH_BY_REFERRALS = [
        1 => 5,   // 1 referral: 5 levels
        2 => 10,  // 2 referrals: 10 levels
        3 => 15   // 3+ referrals: 15 levels
    ];

    public function calculateAndDistributeBonus($purchaseId) {
        $this->db->beginTransaction();

        try {
            $purchase = $this->getPurchaseInfo($purchaseId);

            if ($purchase['is_avatar'] == 1) {
                // Avatars don't generate bonuses for upline
                return ['success' => true, 'bonuses_created' => 0];
            }

            $bonusSettings = $this->getBonusSettings($purchase['package_id']);
            $uplineUsers = $this->getUplineUsers($purchase['user_id'], 15);

            $bonusesCreated = 0;
            $level = 1;

            foreach ($uplineUsers as $upline) {
                $maxDepth = $this->getMaxDepthByReferrals(
                    $upline['direct_referrals_count']
                );

                if ($level > $maxDepth) {
                    $level++;
                    continue;
                }

                $bonusAmount = $this->getBonusAmountByLevel(
                    $bonusSettings,
                    $level
                );

                if ($bonusAmount > 0) {
                    $avatarInfo = $this->getAvatarInfo($upline['user_id']);
                    $targetUserId = $avatarInfo
                        ? $avatarInfo['owner_id']
                        : $upline['user_id'];

                    $this->createBonus([
                        'user_id' => $upline['user_id'],
                        'from_user_id' => $purchase['user_id'],
                        'level' => $level,
                        'bonus_amount' => $bonusAmount,
                        'is_avatar_bonus' => $avatarInfo ? 1 : 0,
                        'avatar_owner_id' => $avatarInfo
                            ? $avatarInfo['owner_id']
                            : null
                    ]);

                    $this->updateUserBonusBalance(
                        $targetUserId,
                        $bonusAmount,
                        $avatarInfo ? true : false
                    );

                    $bonusesCreated++;
                }

                $level++;
            }

            $this->db->commit();
            return ['success' => true, 'bonuses_created' => $bonusesCreated];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
```

### Bonus Tables Structure

**bonus_settings:**
```sql
CREATE TABLE bonus_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    package_id INT NOT NULL,
    level_from INT NOT NULL,
    level_to INT NOT NULL,
    bonus_amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (package_id) REFERENCES packages(id)
);

-- Example data for Package $100
INSERT INTO bonus_settings VALUES
(1, 1, 1, 5, 9.00),   -- Levels 1-5: $9
(2, 1, 6, 15, 4.50);  -- Levels 6-15: $4.50
```

**bonuses:**
```sql
CREATE TABLE bonuses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) NOT NULL,
    from_user_id VARCHAR(50) NOT NULL,
    purchase_id INT NOT NULL,
    package_id INT NOT NULL,
    level INT NOT NULL,
    bonus_amount DECIMAL(10,2) NOT NULL,
    is_avatar_bonus TINYINT(1) DEFAULT 0,
    avatar_owner_id VARCHAR(50) NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

## Package System

### Available Packages

**Package $50 (Starter)**
- Price: $50 USDT
- Duration: 2 years active membership
- Commission Rates:
  - Level 1-5: $4 per member
  - Level 6-15: $2 per member
- Avatar Trigger: Every $150 earned
- Avatar Count: 1 avatar per trigger
- Max Earnings (Full Tree): $131,192 USDT

**Package $100 (Premium)**
- Price: $100 USDT
- Duration: 2 years active membership
- Commission Rates:
  - Level 1-5: $9 per member
  - Level 6-15: $4.50 per member
- Avatar Trigger: Every $300 earned
- Avatar Count: 2 avatars per trigger
- Max Earnings (Full Tree): $295,182 USDT

### Package Database Schema

```sql
CREATE TABLE packages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'USDT',
    duration_days INT DEFAULT 730,  -- 2 years
    avatar_trigger_interval DECIMAL(10,2) NOT NULL,
    avatar_purchase_amount DECIMAL(10,2) DEFAULT 50.00,
    avatar_count INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO packages VALUES
(1, 'Package $50', 'Starter Package', 50.00, 'USDT', 730, 150.00, 50.00, 1, 'active', NOW()),
(2, 'Package $100', 'Premium Package', 100.00, 'USDT', 730, 300.00, 50.00, 2, 'active', NOW());
```

### Purchase Flow

```
1. User Selection
   ↓
2. Payment Initiation
   - Display USDT system address
   - Show required amount
   - Generate unique reference ID
   ↓
3. User Sends USDT (TRC20)
   - From user wallet
   - To system address
   - Include reference in memo (optional)
   ↓
4. Transaction Verification
   - Monitor blockchain via TronGrid API
   - Check transaction hash
   - Verify amount and destination
   - Minimum 1 confirmation
   ↓
5. Purchase Activation
   - Update payment_status to 'completed'
   - Trigger bonus distribution
   - Check avatar generation threshold
   - Update user statistics
   ↓
6. Confirmation
   - Email notification
   - Dashboard update
   - Show new organization position
```

### Package Purchase Table

```sql
CREATE TABLE package_purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) NOT NULL,
    package_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'USDT_TRC20',
    transaction_hash VARCHAR(255) NULL,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    is_avatar TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    confirmed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (package_id) REFERENCES packages(id)
);
```

---

## Avatar Purchasing Mechanism

### Avatar Concept

Avatars are automated bonus accounts that:
- Generate automatically when earning thresholds are met
- Earn independently using the same commission structure
- Have all earnings automatically converted to BTC
- Function as forced savings mechanism toward 1 BTC goal

### Generation Triggers

**Package $50:**
- Trigger Interval: $150 earned
- Avatars Generated: 1 per trigger
- Avatar Cost: $50 (deducted from bonus balance)

**Package $100:**
- Trigger Interval: $300 earned
- Avatars Generated: 2 per trigger
- Avatar Cost: $50 each (total $100 deducted)

### Avatar Generation Algorithm

```
Input: user_id, package_purchases (completed)
Output: Avatar generation count

Algorithm AvatarGeneration:
1. Get user's total package purchased (exclude avatars)
2. Determine package_type:
   - If total >= $100: type = '100'
   - Else: type = '50'
3. Get package settings:
   - avatar_trigger_interval
   - avatar_purchase_amount
   - avatar_count
4. Get user bonus balance for package_type
5. Calculate available avatars:
   - current_balance = user's bonus balance
   - last_trigger_balance = last avatar purchase balance
   - should_have_purchased = floor(current_balance / trigger_interval) * avatar_count
   - already_purchased = floor(last_trigger_balance / trigger_interval) * avatar_count
   - available_avatars = should_have_purchased - already_purchased
6. If available_avatars > 0:
   - Return purchase availability = true
7. Else:
   - Return purchase availability = false
```

### Implementation (PHP)

```php
class BonusSystem {
    public function checkAvatarPurchaseAvailability($userId) {
        $totalPurchased = $this->getUserTotalPackagePurchased($userId);
        $packageType = ($totalPurchased >= 100) ? '100' : '50';

        $package = $this->getPackageByType($packageType);
        $triggerInterval = $package['avatar_trigger_interval'];
        $avatarAmount = $package['avatar_purchase_amount'];
        $avatarCountPerTrigger = $package['avatar_count'];

        $balance = $this->db->selectOne(
            "SELECT * FROM user_bonus_balance WHERE user_id = ?",
            [$userId]
        );

        $balanceColumn = ($packageType == '50')
            ? 'package_50_balance'
            : 'package_100_balance';
        $currentBalance = $balance[$balanceColumn];

        $lastTriggerColumn = ($packageType == '50')
            ? 'last_avatar_trigger_balance_50'
            : 'last_avatar_trigger_balance_100';
        $lastTriggerBalance = $balance[$lastTriggerColumn];

        $shouldHavePurchased = floor($currentBalance / $triggerInterval)
            * $avatarCountPerTrigger;
        $alreadyPurchased = floor($lastTriggerBalance / $triggerInterval)
            * $avatarCountPerTrigger;
        $availableAvatars = $shouldHavePurchased - $alreadyPurchased;

        return [
            'can_purchase' => $availableAvatars > 0,
            'avatar_count' => $availableAvatars,
            'package_type' => $packageType,
            'avatar_price' => $avatarAmount,
            'total_price' => $avatarAmount * $availableAvatars
        ];
    }

    public function purchaseAvatar($userId, $avatarCount) {
        $this->db->beginTransaction();

        try {
            $availability = $this->checkAvatarPurchaseAvailability($userId);

            if (!$availability['can_purchase']) {
                throw new Exception('Insufficient bonus balance');
            }

            for ($i = 0; $i < $avatarCount; $i++) {
                $avatarUserId = $this->generateAvatarUserId($userId);

                // Create avatar purchase record
                $this->createAvatarPurchaseRecord($userId, $avatarUserId);

                // Create package purchase (avatar)
                $this->createPackagePurchaseRecord($avatarUserId, true);

                // Auto-place avatar in binary tree
                $this->autoPlaceAvatar($avatarUserId, $userId);
            }

            // Deduct bonus balance
            $this->deductAvatarCost($userId, $avatarCount);

            $this->db->commit();
            return ['success' => true, 'avatars_purchased' => $avatarCount];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
```

### Avatar Tables

**avatar_purchases:**
```sql
CREATE TABLE avatar_purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) NOT NULL,
    avatar_user_id VARCHAR(50) NOT NULL,
    package_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    trigger_balance DECIMAL(10,2) NOT NULL,
    purchase_record_id INT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (package_id) REFERENCES packages(id)
);
```

**user_bonus_balance:**
```sql
CREATE TABLE user_bonus_balance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) NOT NULL,
    package_type ENUM('50', '100') NOT NULL,
    total_package_purchased DECIMAL(10,2) DEFAULT 0,
    package_50_balance DECIMAL(10,2) DEFAULT 0,
    package_100_balance DECIMAL(10,2) DEFAULT 0,
    total_earned DECIMAL(10,2) DEFAULT 0,
    avatar_bonus_earned DECIMAL(10,2) DEFAULT 0,
    total_withdrawn DECIMAL(10,2) DEFAULT 0,
    total_avatar_purchased DECIMAL(10,2) DEFAULT 0,
    available_balance DECIMAL(10,2) DEFAULT 0,
    last_avatar_trigger_balance_50 DECIMAL(10,2) DEFAULT 0,
    last_avatar_trigger_balance_100 DECIMAL(10,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

---

## Withdrawal Rules

### Withdrawal Types

1. **USDT Withdrawal** (Main Account)
   - Minimum: $50 USDT
   - Source: user_bonus_balance.available_balance
   - Fee: 5% platform fee
   - Destination: User's registered USDT address

2. **BTC Withdrawal** (Avatar Earnings)
   - Minimum: 1 BTC accumulated
   - Source: avatar_bonus_earned
   - Fee: Network gas fee only
   - Destination: User's BNB Smart Chain address

3. **Point Swap** (Early Withdrawal)
   - Convert points to: TRX, BNB, XRP, VCDAO, DOBUY
   - Minimum: $100 equivalent
   - Conversion fee: 10%
   - Real-time exchange rates

### Withdrawal Process Flow

```
1. User Initiation
   - Select withdrawal type
   - Enter amount
   - Confirm withdrawal address
   ↓
2. System Validation
   - Check minimum amount
   - Verify available balance
   - Validate destination address
   - Calculate fees
   ↓
3. Admin Review (Optional)
   - Large amounts flagged for review
   - Security check
   - Fraud detection
   ↓
4. Approval
   - Update withdrawal status to 'approved'
   - Deduct from user balance
   - Queue for processing
   ↓
5. Processing
   - Transfer USDT/BTC to user address
   - Record blockchain transaction hash
   - Update status to 'completed'
   ↓
6. Confirmation
   - Email notification
   - Dashboard update
   - Transaction history
```

### Withdrawal Rules

**General Rules:**
- Minimum withdrawal: $50 USDT equivalent
- Maximum per day: $10,000 USDT (configurable)
- Processing time: 24-48 hours
- Weekend withdrawals processed on Monday
- Failed withdrawals refunded to balance

**BTC Accumulation Goal:**
- Avatar earnings automatically accumulate toward 1 BTC
- Early withdrawal available with conversion fee
- 1 BTC milestone unlocks full withdrawal
- Withdrawal to personal wallet only

**Fees:**
- USDT Withdrawal: 5% platform fee
- BTC Withdrawal: Network gas fee only
- Point Swap: 10% conversion fee
- Refund: No fee (balance restored)

### Withdrawal Table Schema

```sql
CREATE TABLE withdrawals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(50) NOT NULL,
    withdrawal_type ENUM('USDT', 'BTC', 'SWAP') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) NOT NULL,
    withdrawal_address VARCHAR(255) NOT NULL,
    fee_amount DECIMAL(10,2) NOT NULL,
    net_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'processing', 'completed', 'rejected', 'refunded') DEFAULT 'pending',
    transaction_hash VARCHAR(255) NULL,
    admin_notes TEXT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
```

---

## Database Schema Overview

### Core Tables

**1. users** - User accounts
```sql
- id (PK, INT)
- user_id (UNIQUE, VARCHAR) - Login ID
- password (VARCHAR) - Bcrypt hashed
- name (VARCHAR)
- email (UNIQUE, VARCHAR)
- phone (VARCHAR)
- usdt_address (VARCHAR) - BNB Smart Chain
- referral_id (VARCHAR) - Parent in tree
- referral_org (VARCHAR) - Original sponsor
- direct_referrals_count (INT)
- email_verified (TINYINT)
- status (ENUM: active, inactive, suspended)
- role (ENUM: user, admin, super)
- created_at, updated_at, last_login
```

**2. packages** - Package definitions
```sql
- id (PK)
- name (VARCHAR: "Package $50", "Package $100")
- price (DECIMAL)
- avatar_trigger_interval (DECIMAL)
- avatar_purchase_amount (DECIMAL)
- avatar_count (INT)
- status (ENUM: active, inactive)
```

**3. package_purchases** - Purchase history
```sql
- id (PK)
- user_id (FK)
- package_id (FK)
- amount (DECIMAL)
- payment_method (VARCHAR)
- transaction_hash (VARCHAR)
- payment_status (ENUM: pending, completed, failed)
- is_avatar (TINYINT)
- created_at, confirmed_at
```

**4. bonuses** - Bonus records
```sql
- id (PK)
- user_id (FK) - Receiver
- from_user_id (FK) - Purchaser
- purchase_id (FK)
- package_id (FK)
- level (INT: 1-15)
- bonus_amount (DECIMAL)
- is_avatar_bonus (TINYINT)
- avatar_owner_id (VARCHAR)
- status (ENUM: pending, approved, rejected)
- created_at, approved_at
```

**5. user_bonus_balance** - Bonus tracking
```sql
- id (PK)
- user_id (FK)
- package_type (ENUM: '50', '100')
- total_package_purchased (DECIMAL)
- package_50_balance (DECIMAL)
- package_100_balance (DECIMAL)
- total_earned (DECIMAL)
- avatar_bonus_earned (DECIMAL)
- total_withdrawn (DECIMAL)
- available_balance (DECIMAL)
- last_avatar_trigger_balance_50/100 (DECIMAL)
```

**6. avatar_purchases** - Avatar generation
```sql
- id (PK)
- user_id (FK) - Owner
- avatar_user_id (VARCHAR) - Avatar ID
- package_id (FK)
- amount (DECIMAL)
- trigger_balance (DECIMAL)
- purchase_record_id (FK)
- status (ENUM: pending, completed)
```

**7. withdrawals** - Withdrawal requests
```sql
- id (PK)
- user_id (FK)
- withdrawal_type (ENUM: USDT, BTC, SWAP)
- amount (DECIMAL)
- currency (VARCHAR)
- withdrawal_address (VARCHAR)
- fee_amount, net_amount (DECIMAL)
- status (ENUM: pending, approved, processing, completed, rejected, refunded)
- transaction_hash (VARCHAR)
- requested_at, approved_at, completed_at
```

**8. bonus_settings** - Commission rates
```sql
- id (PK)
- package_id (FK)
- level_from (INT)
- level_to (INT)
- bonus_amount (DECIMAL)
```

### Support Tables

- **sessions** - Login sessions
- **email_verifications** - Email verification codes
- **activity_logs** - User activity tracking
- **admin_logs** - Admin action logs
- **user_statistics** - Aggregate statistics

### Entity Relationship Diagram

```
users (1) ──────────── (N) package_purchases
  │                          │
  │                          │ (1)
  │                          ▼
  │                     packages (N)
  │                          │
  │ (1)                      │ (1)
  ▼                          ▼
bonuses ◄─────────────── bonus_settings
  │
  │ (N)
  ▼
user_bonus_balance
  │
  │ (1)
  ▼
avatar_purchases
  │
  │ (N)
  ▼
withdrawals
```

---

## API Architecture

### RESTful Endpoint Structure

```
/api/
├── auth/
│   ├── register.php          # POST - User registration
│   ├── login.php             # POST - User login
│   ├── logout.php            # POST - User logout
│   ├── check-id.php          # POST - Check ID availability
│   └── check-usdt.php        # POST - Check USDT address
├── user/
│   ├── get-profile.php       # GET - Get user profile
│   └── update-profile.php    # PUT - Update profile
├── sales/
│   ├── get-products.php      # GET - List packages
│   ├── purchase.php          # POST - Purchase package
│   └── get-purchases.php     # GET - Purchase history
├── bonus/
│   └── list.php              # GET - Bonus history
├── withdrawal/
│   ├── balance.php           # GET - Get balance
│   ├── request.php           # POST - Request withdrawal
│   └── list.php              # GET - Withdrawal history
├── organization/
│   └── get-tree.php          # GET - Get organization tree
└── admin/
    ├── login.php             # POST - Admin login
    ├── users.php             # GET/PUT/DELETE - User management
    ├── purchases.php         # GET/PUT - Purchase management
    ├── withdrawals.php       # GET/PUT - Withdrawal processing
    ├── stats.php             # GET - System statistics
    └── tree.php              # GET - Full organization tree
```

### API Response Format

**Success Response:**
```json
{
    "success": true,
    "data": {
        "user_id": "john_doe",
        "name": "John Doe",
        "email": "john@example.com"
    },
    "message": "Profile retrieved successfully"
}
```

**Error Response:**
```json
{
    "success": false,
    "error": {
        "code": "AUTH_001",
        "message": "Invalid credentials",
        "details": "Username or password is incorrect"
    }
}
```

### Authentication

**Token-Based Authentication:**
```
1. Login → Receive token
2. Store token in localStorage
3. Include in subsequent requests:
   Header: Authorization: Bearer {token}
4. Token expiration: 2 hours
5. Refresh token on activity
```

---

## Security Design

### Password Security
- Bcrypt hashing (cost factor 12)
- Minimum 8 characters
- Complexity requirements (optional)
- Password reset via email

### SQL Injection Prevention
- PDO prepared statements
- Parameter binding
- Input validation
- Whitelist validation

### XSS Protection
- HTML entity encoding
- Content Security Policy headers
- Input sanitization
- Output escaping

### CSRF Protection
- Token-based CSRF protection
- Same-origin policy
- Referer validation
- Double-submit cookies

### API Security
- Rate limiting
- IP whitelisting (admin)
- Request throttling
- Input validation

---

## Performance Optimization

### Database Optimization
- Indexed columns (user_id, email, referral_id)
- Query optimization
- Connection pooling
- Prepared statement caching

### Caching Strategy
- Session caching (Redis/Memcached)
- Query result caching
- Static asset caching
- Browser caching headers

### Load Balancing
- Horizontal scaling
- Database replication
- CDN for static assets
- Reverse proxy (Nginx)

---

**AI BTC BOT System Design v1.0**

*Last Updated: 2025-10-31*
