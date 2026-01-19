<?php

/**
 * Rotas da API para Gerenciamento de API Keys
 * Arquivo: routes/api.php
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\ApiKeyManager;

// =====================================================
// ROTAS PÚBLICAS (sem autenticação)
// =====================================================

/**
 * Validar API Key
 * Usado pelo pacote Integrity e por clientes da API
 */
Route::get('/auth/validate', function (Request $request) {
    // Extrair API key dos headers
    $apiKey = $request->header('X-API-Key') 
           ?? str_replace('Bearer ', '', $request->header('Authorization', ''));
    
    if (!$apiKey) {
        return response()->json([
            'valid' => false,
            'error' => 'API key not provided'
        ], 401);
    }
    
    // Validar usando ApiKeyManager
    $keyData = ApiKeyManager::validate($apiKey);
    
    if (!$keyData) {
        return response()->json([
            'valid' => false,
            'error' => 'Invalid or expired API key'
        ], 401);
    }
    
    // Buscar dados do usuário (opcional)
    $user = null;
    if ($keyData['user_id']) {
        $userData = DB::table('users')
            ->where('id', $keyData['user_id'])
            ->first(['id', 'name', 'email']);
        
        if ($userData) {
            $user = [
                'id' => $userData->id,
                'name' => $userData->name,
                'email' => $userData->email,
            ];
        }
    }
    
    return response()->json([
        'valid' => true,
        'user' => $user,
        'permissions' => $keyData['permissions'],
        'rate_limit' => $keyData['rate_limit'],
        'usage_count' => $keyData['usage_count'],
    ]);
});

// =====================================================
// ROTAS PROTEGIDAS (requer autenticação de usuário)
// =====================================================

Route::middleware(['auth:sanctum'])->group(function () {
    
    /**
     * Listar todas as API Keys do usuário autenticado
     */
    Route::get('/api/keys', function (Request $request) {
        $userId = $request->user()->id;
        $activeOnly = $request->query('active_only', true);
        
        $keys = ApiKeyManager::list($userId, (bool) $activeOnly);
        
        return response()->json([
            'success' => true,
            'data' => $keys,
            'total' => count($keys),
        ]);
    });
    
    /**
     * Criar uma nova API Key
     */
    Route::post('/api/keys', function (Request $request) {
        // Validação
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:read,write,delete,admin',
            'expires_at' => 'nullable|date|after:now',
            'rate_limit' => 'nullable|integer|min:1|max:10000',
        ]);
        
        $userId = $request->user()->id;
        $name = $validated['name'];
        $permissions = $validated['permissions'] ?? ['read'];
        $expiresAt = isset($validated['expires_at']) 
            ? new DateTime($validated['expires_at']) 
            : null;
        $rateLimit = $validated['rate_limit'] ?? 1000;
        
        // Gerar a key
        $result = ApiKeyManager::generate(
            name: $name,
            userId: $userId,
            permissions: $permissions,
            expiresAt: $expiresAt,
            rateLimit: $rateLimit
        );
        
        // Salvar descrição se fornecida
        if (isset($validated['description'])) {
            DB::table('api_keys')
                ->where('id', $result['id'])
                ->update(['description' => $validated['description']]);
        }
        
        return response()->json([
            'success' => true,
            'data' => $result,
            'message' => 'API Key criada com sucesso! Guarde-a em local seguro, ela não será exibida novamente.',
        ], 201);
    });
    
    /**
     * Obter detalhes de uma API Key específica
     */
    Route::get('/api/keys/{id}', function (Request $request, int $id) {
        $userId = $request->user()->id;
        
        $key = DB::table('api_keys')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
        
        if (!$key) {
            return response()->json([
                'success' => false,
                'error' => 'API Key não encontrada',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $key->id,
                'prefix' => $key->key_prefix,
                'name' => $key->name,
                'description' => $key->description,
                'permissions' => json_decode($key->permissions),
                'is_active' => (bool) $key->is_active,
                'rate_limit' => $key->rate_limit,
                'usage_count' => $key->usage_count,
                'last_used_at' => $key->last_used_at,
                'last_ip' => $key->last_ip,
                'expires_at' => $key->expires_at,
                'created_at' => $key->created_at,
            ],
        ]);
    });
    
    /**
     * Revogar (desativar) uma API Key
     */
    Route::delete('/api/keys/{id}', function (Request $request, int $id) {
        $userId = $request->user()->id;
        
        // Verificar se a key pertence ao usuário
        $key = DB::table('api_keys')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
        
        if (!$key) {
            return response()->json([
                'success' => false,
                'error' => 'API Key não encontrada',
            ], 404);
        }
        
        $reason = $request->input('reason', 'Revogada pelo usuário');
        
        // Revogar
        $success = ApiKeyManager::revoke($id, $reason);
        
        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'API Key revogada com sucesso',
            ]);
        }
        
        return response()->json([
            'success' => false,
            'error' => 'Erro ao revogar API Key',
        ], 500);
    });
    
    /**
     * Atualizar nome/descrição de uma API Key
     */
    Route::patch('/api/keys/{id}', function (Request $request, int $id) {
        $userId = $request->user()->id;
        
        // Verificar propriedade
        $key = DB::table('api_keys')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
        
        if (!$key) {
            return response()->json([
                'success' => false,
                'error' => 'API Key não encontrada',
            ], 404);
        }
        
        // Validação
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        
        // Atualizar
        $updates = [];
        if (isset($validated['name'])) {
            $updates['name'] = $validated['name'];
        }
        if (isset($validated['description'])) {
            $updates['description'] = $validated['description'];
        }
        
        if (!empty($updates)) {
            DB::table('api_keys')
                ->where('id', $id)
                ->update($updates);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'API Key atualizada com sucesso',
        ]);
    });
});
