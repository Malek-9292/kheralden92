<?php

use Modules\AccessControl\Providers\AccessControlServiceProvider;
use Modules\Audit\Providers\AuditServiceProvider;
use Modules\BackupOps\Providers\BackupOpsServiceProvider;
use Modules\Branches\Providers\BranchesServiceProvider;
use Modules\Cart\Providers\CartServiceProvider;
use Modules\Cashier\Providers\CashierServiceProvider;
use Modules\Catalog\Providers\CatalogServiceProvider;
use Modules\Content\Providers\ContentServiceProvider;
use Modules\CustomerService\Providers\CustomerServiceServiceProvider;
use Modules\Delivery\Providers\DeliveryServiceProvider;
use Modules\Identity\Providers\IdentityServiceProvider;
use Modules\Kitchen\Providers\KitchenServiceProvider;
use Modules\Notifications\Providers\NotificationsServiceProvider;
use Modules\Orders\Providers\OrdersServiceProvider;
use Modules\Payments\Providers\PaymentsServiceProvider;
use Modules\Printing\Providers\PrintingServiceProvider;
use Modules\Promotions\Providers\PromotionsServiceProvider;
use Modules\Reporting\Providers\ReportingServiceProvider;
use Modules\Reviews\Providers\ReviewsServiceProvider;
use Modules\Storefront\Providers\StorefrontServiceProvider;
use Modules\SystemSettings\Providers\SystemSettingsServiceProvider;

return [
    'Identity' => [
        'enabled' => true,
        'core' => true,
        'depends_on' => [],
        'provider' => IdentityServiceProvider::class,
    ],
    'AccessControl' => [
        'enabled' => true,
        'core' => true,
        'depends_on' => ['Identity'],
        'provider' => AccessControlServiceProvider::class,
    ],
    'Branches' => [
        'enabled' => true,
        'core' => true,
        'depends_on' => ['Identity'],
        'provider' => BranchesServiceProvider::class,
    ],
    'Audit' => [
        'enabled' => true,
        'core' => true,
        'depends_on' => ['Identity'],
        'provider' => AuditServiceProvider::class,
    ],
    'Catalog' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Branches'],
        'provider' => CatalogServiceProvider::class,
    ],
    'Storefront' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Catalog', 'Cart', 'Orders', 'Delivery'],
        'provider' => StorefrontServiceProvider::class,
    ],
    'Cart' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Catalog'],
        'provider' => CartServiceProvider::class,
    ],
    'Orders' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Catalog', 'Branches', 'Identity'],
        'provider' => OrdersServiceProvider::class,
    ],
    'Delivery' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Branches'],
        'provider' => DeliveryServiceProvider::class,
    ],
    'Payments' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Orders'],
        'provider' => PaymentsServiceProvider::class,
    ],
    'Promotions' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Catalog', 'Orders'],
        'provider' => PromotionsServiceProvider::class,
    ],
    'Cashier' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Orders', 'Payments', 'Identity'],
        'provider' => CashierServiceProvider::class,
    ],
    'Kitchen' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Orders'],
        'provider' => KitchenServiceProvider::class,
    ],
    'Printing' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Orders', 'Kitchen'],
        'provider' => PrintingServiceProvider::class,
    ],
    'Notifications' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Orders'],
        'provider' => NotificationsServiceProvider::class,
    ],
    'Reviews' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Orders'],
        'provider' => ReviewsServiceProvider::class,
    ],
    'Reporting' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Orders', 'Branches'],
        'provider' => ReportingServiceProvider::class,
    ],
    'Content' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Branches'],
        'provider' => ContentServiceProvider::class,
    ],
    'CustomerService' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['Identity'],
        'provider' => CustomerServiceServiceProvider::class,
    ],
    'BackupOps' => [
        'enabled' => true,
        'core' => false,
        'depends_on' => ['SystemSettings'],
        'provider' => BackupOpsServiceProvider::class,
    ],
    'SystemSettings' => [
        'enabled' => true,
        'core' => true,
        'depends_on' => [],
        'provider' => SystemSettingsServiceProvider::class,
    ],
];
