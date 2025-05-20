# Momo Suite

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rais/momo-suite.svg?style=flat-square)](https://packagist.org/packages/rais/momo-suite)
[![Total Downloads](https://img.shields.io/packagist/dt/rais/momo-suite.svg?style=flat-square)](https://packagist.org/packages/rais/momo-suite)

📚 [Documentation](https://rais.gitbook.io/momo-suite/)

A powerful, flexible Mobile Money Payment Suite for Laravel supporting multiple providers (Paystack, Hubtel, ITC, Korba, and more).

---

## Features

-   Unified API for sending and receiving mobile money across multiple providers
-   Webhook/callback handling
-   Transaction logging and status tracking
-   Extensible provider system
-   Laravel 8, 9, 10, 11 support (PHP 8+)
-   **Korba is used as the default provider if none is set.**

---

## Installation

```bash
composer require rais/momo-suite
```

---

## Configuration

You should publish all package assets (config, migrations, views, routes, public) with:

```bash
php artisan vendor:publish --provider="Rais\MomoSuite\MomoSuiteServiceProvider"
```

1. **Set up your provider credentials** in `config/momo-suite.php`.
2. **Run migrations:**
    ```bash
    php artisan migrate
    ```
3. **Dashboard UI & Admin User**

    - Enable the dashboard by setting `MOMO_SUITE_LOAD_DASHBOARD=true` in your `.env` file
    - The views will be published to `resources/views/vendor/momo-suite`
    - Note: Publishing views will make it harder to receive view updates from the package

    ### 👤 Create an Admin User

    You can create an admin user for the system using the provided Artisan command.

    **🔹 Option 1: Create Default Admin**

    ```bash
    php artisan momo-suite:create-admin --default
    ```

    ⚠️ Note: This will create a default admin user with the following credentials:

    - Email: admin@momo-suite.com
    - Password: password
      ✅ Important: You should log in and change these credentials immediately after your first login for security reasons.

    **🔹 Option 2: Create Custom Admin**

    ```bash
    php artisan momo-suite:create-admin
    ```

    This will prompt you to enter the admin's name, email, and password manually during execution.

---

## Usage

### Sending Money

## Usage Examples

> **Available network providers for the `network` parameter:**
>
> -   MTN
> -   TELECEL
> -   AIRTELTIGO

### Receive Money (Korba, default provider)

```php
$momo = app('momo-suite');

$receive = $momo->receive([
    'phone' => '0241234567',
    'amount' => 1.00,
    'network' => 'MTN',
    'reference' => 'Testing',
    'meta' => [
        'customer_name' => 'User one',
        'customer_email' => 'userone@example.com',
    ], // optional
]);
```

### Send Money (Korba, default provider)

```php
$momo = app('momo-suite');

$send = $momo->send([
    'phone' => '0241234567',
    'amount' => 1.00,
    'network' => 'MTN',
    'reference' => 'Test sending money',
]);
```

### Use a Specific Provider (e.g., Hubtel)

```php
$momo = app('momo-suite');
$momo->setProvider('hubtel');

$send = $momo->send([
    'phone' => '0241234567',
    'amount' => 1.00,
    'network' => 'MTN',
    'reference' => 'Test Payment',
    'customer_name' => 'username', // Required for Hubtel
]);
```

### Receive Money with Hubtel

```php
$momo = app('momo-suite');
$momo->setProvider('hubtel');

$receive = $momo->receive([
    'phone' => '0241234567',
    'amount' => 1.00,
    'network' => 'MTN',
    'reference' => 'Test Payment',
    'customer_name' => 'username', // Required for Hubtel
]);
```

### Send Money using Facade (Hubtel)

```php
use Rais\MomoSuite\Facades\Momo;

Momo::setProvider('hubtel');

$send = Momo::send([
    'phone' => '0241234567',
    'amount' => 1.00,
    'network' => 'MTN',
    'reference' => 'Test Disbursement',
    'customer_name' => 'username', // Required for Hubtel
]);
```

### Receive Money with Paystack

```php
$momo = app('momo-suite');
$momo->setProvider('paystack');

$receive = $momo->receive([
    'phone' => '0241234567',
    'amount' => 1.00,
    'email' => 'user@example.com', // Required for Paystack
    'network' => 'MTN',
    'reference' => 'Test receive Payment',
]);
```

### Send Money with Paystack

```php
$momo = app('momo-suite');
$momo->setProvider('paystack');

$send = $momo->send([
    'phone' => '0241234567',
    'amount' => 1.00,
    'email' => 'user@example.com', // Required for Paystack
    'network' => 'MTN',
    'customer_name' => 'username', // Required for Paystack
    'reference' => 'Test',
]);
```

### Verify OTP with Paystack

```php
$momo = app('momo-suite');
$momo->setProvider('paystack');

$otp = $momo->verifyOtp([
    'otp' => '123456',
    'reference' => 'transaction-reference',
]);
```

### Receive Money with ITC

```php
$momo = app('momo-suite');
$momo->setProvider('itc');

$receive = $momo->receive([
    'phone' => '0241234567',
    'amount' => 1,
    'network' => 'MTN',
    'reference' => 'Testing ITC',
]);
```

### Send Money with ITC

```php
$momo = app('momo-suite');
$momo->setProvider('itc');

$send = $momo->send([
    'phone' => '0241234567',
    'amount' => 0.5,
    'network' => 'MTN',
    'reference' => 'Test Disbursement ITC',
]);
```
