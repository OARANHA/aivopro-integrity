<?php

/**
 * Exemplo completo de rotas para Laravel
 * Cole este código no seu routes/api.php
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;

// ===========================
// 1. HEALTH CHECK (OBRIGATÓRIO)
// ===========================
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'uptime' => exec('uptime -p') ?: 'N/A'
    ]);
});

// ===========================
// 2. VERSION INFO
// ===========================
Route::get('/version', function () {
    return response()->json([
        'version' => config('app.version', '1.0.0'),
        'api_name' => '28Fácil API',
        'environment' => config('app.env'),
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION
    ]);
});

// ===========================
// 3. ROOT ENDPOINT
// ===========================
Route::get('/', function () {
    return response()->json([
        'name' => '28Fácil API',
        'version' => config('app.version', '1.0.0'),
        'status' => 'running',
        'documentation' => url('/docs')
    ]);
});

// ===========================
// 4. AUTH VALIDATION
// ===========================
Route::get('/auth/validate', function (Request $request) {
    // Extrair API key
    $apiKey = $request->header('X-API-Key') 
           ?? str_replace('Bearer ', '', $request->header('Authorization', ''));
    
    if (!$apiKey) {
        return response()->json([
            'valid' => false,
            'error' => 'API key missing'
        ], 401);
    }
    
    // SUBSTITUA pela sua lógica de validação
    // Exemplo com tabela users:
    $user = DB::table('users')
        ->where('api_key', $apiKey)
        ->first();
    
    if ($user) {
        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'permissions' => ['read', 'write'] // Ajuste conforme seu sistema
        ]);
    }
    
    return response()->json([
        'valid' => false,
        'error' => 'Invalid API key'
    ], 401);
});

// ===========================
// 5. DEPENDENCIES STATUS
// ===========================
Route::get('/status/dependencies', function (Request $request) {
    // OPCIONAL: Proteger endpoint
    $adminKey = $request->header('X-Admin-Key');
    if ($adminKey !== config('app.admin_api_key')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    
    $services = [];
    
    // Check Database
    try {
        DB::connection()->getPdo();
        DB::select('SELECT 1');
        $services['database'] = 'healthy';
    } catch (\Exception $e) {
        \Log::error('Database health check failed', ['error' => $e->getMessage()]);
        $services['database'] = 'unhealthy';
    }
    
    // Check Redis
    try {
        Redis::ping();
        $services['redis'] = 'healthy';
    } catch (\Exception $e) {
        \Log::error('Redis health check failed', ['error' => $e->getMessage()]);
        $services['redis'] = 'unhealthy';
    }
    
    // Check Evolution API
    try {
        $evolutionUrl = config('services.evolution.url', 'http://localhost:8080');
        $response = Http::timeout(5)->get("{$evolutionUrl}/health");
        $services['evolution_api'] = $response->successful() ? 'healthy' : 'unhealthy';
    } catch (\Exception $e) {
        \Log::error('Evolution API health check failed', ['error' => $e->getMessage()]);
        $services['evolution_api'] = 'unhealthy';
    }
    
    // Check Email (SMTP)
    try {
        // Tentar conectar ao SMTP sem enviar email
        $transport = app('mail.manager')->createTransport(
            config('mail')
        );
        $services['email'] = 'healthy';
    } catch (\Exception $e) {
        \Log::error('Email service health check failed', ['error' => $e->getMessage()]);
        $services['email'] = 'unhealthy';
    }
    
    // Verificar se todos estão saudáveis
    $allHealthy = collect($services)->every(fn($status) => $status === 'healthy');
    
    return response()->json([
        'services' => $services,
        'overall_status' => $allHealthy ? 'healthy' : 'degraded'
    ], $allHealthy ? 200 : 503);
});

// ===========================
// ALTERNATIVA: Controller
// ===========================

// Se preferir usar controller:
// Route::get('/health', [IntegrityController::class, 'health']);
// Route::get('/version', [IntegrityController::class, 'version']);
// Route::get('/auth/validate', [IntegrityController::class, 'validateAuth']);
// Route::get('/status/dependencies', [IntegrityController::class, 'dependencies']);
