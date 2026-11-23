<?php
return [
 'platform'=>['name'=>'Platform & Operations','core'=>true,'enabled_by_default'=>true,'dependencies'=>[]],
 'identity'=>['name'=>'Identity & Access','core'=>true,'enabled_by_default'=>true,'dependencies'=>['platform']],
 'branches'=>['name'=>'Branches','core'=>true,'enabled_by_default'=>true,'dependencies'=>['platform']],
 'catalog'=>['name'=>'Catalog','enabled_by_default'=>true,'dependencies'=>['platform','branches']],
 'delivery'=>['name'=>'Delivery','enabled_by_default'=>true,'dependencies'=>['branches']],
 'cart_pricing'=>['name'=>'Cart & Pricing','enabled_by_default'=>true,'dependencies'=>['catalog','delivery']],
 'ordering'=>['name'=>'Ordering','enabled_by_default'=>true,'dependencies'=>['identity','branches','cart_pricing']],
 'cashier'=>['name'=>'Cashier Operations','enabled_by_default'=>true,'dependencies'=>['identity','ordering']],
 'printing'=>['name'=>'Printing','enabled_by_default'=>true,'dependencies'=>['ordering']],
 'reporting_audit'=>['name'=>'Reporting & Audit','enabled_by_default'=>true,'dependencies'=>['platform','ordering']],
 'customer_experience'=>['name'=>'Customer Experience','enabled_by_default'=>true,'dependencies'=>['catalog','ordering']],
 'notifications'=>['name'=>'Notifications','enabled_by_default'=>true,'dependencies'=>['platform']],
 'reviews'=>['name'=>'Reviews','enabled_by_default'=>true,'dependencies'=>['ordering','identity']],
 'admin_content'=>['name'=>'Admin & Content','enabled_by_default'=>true,'dependencies'=>['identity','catalog']],
];
