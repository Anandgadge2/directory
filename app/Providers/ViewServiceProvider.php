<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Traits\FilterDataTrait;

class ViewServiceProvider extends ServiceProvider
{
    use FilterDataTrait;

    public function boot()
    {
        View::composer('partials.global-filters', function ($view) {
            $view->with($this->filterData());
        });
    }
}
