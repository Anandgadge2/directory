<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\IncidentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Re-register session-based mock user for testing if needed
// Or just mock session directly after boot
$response = $kernel->handle(
    $request = Request::create('/incidents/type/water_source', 'GET', ['source' => 'patrol_logs', 'start_date' => '2024-01-01', 'end_date' => '2025-12-31'])
);

echo $response->getContent();
