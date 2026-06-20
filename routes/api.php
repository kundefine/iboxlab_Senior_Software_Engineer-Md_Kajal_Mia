<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::get('flights/search', [\App\Http\Controllers\FlightController::class, 'search']);




Route::get('/test', function (\App\FlightProviders\ProviderBimanBangla $usb) {
    dd($usb->fetch());
});
