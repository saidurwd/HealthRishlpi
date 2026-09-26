<?php

namespace App\Providers;

use App\Models\Menu;
use App\Support\LegacyRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $view->with('menu', Menu::tree(...LegacyRoute::current()));
        });

        /*
         * Route::crud('city', CityController::class) registers the four actions
         * a Gii-generated Yii controller let logged-in users reach:
         * /city/admin, /city/create, /city/update/{id} and POST /city/delete/{id}.
         */
        Route::macro('crud', function (string $id, string $controller) {
            Route::controller($controller)->prefix($id)->name($id.'.')->group(function () {
                Route::get('admin', 'admin')->name('admin');
                Route::match(['get', 'post'], 'create', 'create')->name('create');
                Route::match(['get', 'post'], 'update/{id}', 'update')->name('update')->whereNumber('id');
                Route::post('delete/{id}', 'delete')->name('delete')->whereNumber('id');
            });
        });
    }
}
