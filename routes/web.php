<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// curl -X GET "https://polnes-news.b4its.tech/api/news/get/dashboardAdmin" \
// -H "X-Api-Key: hrhr"