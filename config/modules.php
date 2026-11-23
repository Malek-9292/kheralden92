<?php
return [
 'Identity'=>['enabled'=>true,'provider'=>Modules\Identity\Providers\IdentityServiceProvider::class],
 'Branches'=>['enabled'=>true,'provider'=>Modules\Branches\Providers\BranchesServiceProvider::class],
 'Catalog'=>['enabled'=>true,'provider'=>Modules\Catalog\Providers\CatalogServiceProvider::class],
 'Orders'=>['enabled'=>true,'provider'=>Modules\Orders\Providers\OrdersServiceProvider::class],
 'Cashier'=>['enabled'=>true,'provider'=>Modules\Cashier\Providers\CashierServiceProvider::class],
 'Delivery'=>['enabled'=>true,'provider'=>Modules\Delivery\Providers\DeliveryServiceProvider::class],
 'Promotions'=>['enabled'=>true,'provider'=>Modules\Promotions\Providers\PromotionsServiceProvider::class],
 'Notifications'=>['enabled'=>true,'provider'=>Modules\Notifications\Providers\NotificationsServiceProvider::class],
 'Printing'=>['enabled'=>true,'provider'=>Modules\Printing\Providers\PrintingServiceProvider::class],
 'Reporting'=>['enabled'=>true,'provider'=>Modules\Reporting\Providers\ReportingServiceProvider::class],
 'Reviews'=>['enabled'=>true,'provider'=>Modules\Reviews\Providers\ReviewsServiceProvider::class],
];