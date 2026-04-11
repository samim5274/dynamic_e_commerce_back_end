<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('https://dynamicbazarmerchantbd.com/');
});

Route::get('/clear', function () {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('optimize:clear');
        Artisan::call('optimize');

        return redirect()->back()->with('success','Caches cleared successfully.');
    });
