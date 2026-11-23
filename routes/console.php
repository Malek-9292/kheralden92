<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('fatoosh:about', function () {
    $this->info('Fatoosh Restaurant System foundation is ready.');
})->purpose('Display basic Fatoosh system information.');
