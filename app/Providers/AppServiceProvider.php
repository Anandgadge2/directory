<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\Traits\FilterDataTrait;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

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
    public function boot()
{
    View::composer('*', function ($view) {
        try {
            // [DEV MODE] Ensure a default user with company_id 56 exists in session if not present
            if (!session()->has('user')) {
                 session()->put('user', (object)[
                    "company_id" => 56,
                    "id" => 1,
                    "isActive" => 1,
                    "role_id" => 1,
                    "name" => "Dev Admin"
                ]);
            }

            $provider = new class {
                use FilterDataTrait;
            };

            $filterData = $provider->filterData();
            $view->with($filterData);
        } catch (\Exception $e) {
            // If filterData fails (e.g., database connection error), provide empty data
            // This prevents redirect loops and allows the page to render with empty filters
            \Log::error('View Composer Error: ' . $e->getMessage());
            $view->with([
                'ranges' => collect(),
                'beats' => collect(),
                'users' => collect(),
            ]);
        }
    });

    // Register Blade directive for formatting names
    Blade::directive('formatName', function ($expression) {
        return "<?php echo App\Helpers\FormatHelper::formatName($expression); ?>";
    });
}
}
