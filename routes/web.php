<?php

use App\Livewire\ProductList;
use Illuminate\Support\Facades\Route;

Route::get('/', \App\Livewire\ProductList::class);