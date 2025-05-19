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

---

## Installation

```bash
composer require rais/momo-suite
```

---

## Configuration

You should publish the migration and the config/momo-suite.php config file with:

```bash
php artisan vendor:publish --provider="Rais\MomoSuite\MomoSuiteServiceProvider"
```

1. **Set up your provider credentials** in `config/momo-suite.php`.
2. **Run migrations:**
    ```bash
    php artisan migrate
    ```
3. **Dashboard UI (Optional)**

    - Enable the dashboard by setting `MOMO_SUITE_LOAD_DASHBOARD=true` in your `.env` file
    - To customize the dashboard views, publish them with:

    ```bash
    php artisan momo-suite:install --views
    ```

    - The views will be published to `resources/views/vendor/momo-suite`
    - Note: Publishing views will make it harder to receive view updates from the package

4. **Other Customizations (Optional)**
    ```bash
    php artisan momo-suite:install --routes    # Customize routes
    php artisan momo-suite:install --all       # Publish everything
    ```

---

## Usage

### Sending Money

```php
use Rais\MomoSuite\Facades\Momo;

$result = Momo::send([
    'phone' => '0240000000',
    'amount' => 10.00,
    'network' => 'MTN',
    'reference' => 'Payment for Order #123',
    // 'provider' => 'paystack', // Optional: set provider
    // 'email' => 'user@example.com', // Required for Paystack
    // 'customer_name' => 'John Doe', // Required for some providers
]);
```

### Receiving Money

```php
$result = Momo::receive([
    'phone' => '0240000000',
    'amount' => 5.00,
    'network' => 'MTN',
    'reference' => 'Collect for Invoice #456',
]);
```

### Setting Provider

```php
Momo::setProvider('hubtel');
```

---

## Webhooks

-   Set your webhook URLs in your provider dashboard (e.g., Paystack).
-   The package will handle incoming callbacks and update transaction statuses automatically.

---

## Testing

You can use the included API routes for local testing (see `routes/api.php`).

---

## Contributing

1. Fork the repo
2. Create your feature branch (`git checkout -b feature/my-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/my-feature`)
5. Create a new Pull Request

---

## License

MIT © [Rais](mailto:osumanurais@gmail.com)

---

## More Information

For detailed documentation, guides, and examples, visit our [Getting Started Guide](https://rais.gitbook.io/momo-suite/getting-started).
