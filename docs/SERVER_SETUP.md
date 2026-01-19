# Configuração do Servidor - API 28Fácil

## Guia para Implementar Endpoints de Integrity na sua API

Este documento explica como configurar **api.28facil.com.br** para responder corretamente às checagens do pacote Integrity.

---

## 📡 Endpoints Necessários

### 1. Health Check - `/health` (OBRIGATÓRIO)

**Método:** `GET`

**Resposta esperada:**
```json
{
  "status": "ok",
  "timestamp": "2026-01-19T17:20:00-03:00"
}
```

**Código de status:** `200 OK`

---

### 2. Version Check - `/version` (RECOMENDADO)

**Método:** `GET`

**Resposta esperada:**
```json
{
  "version": "1.0.0",
  "api_name": "28Fácil API",
  "environment": "production"
}
```

**Alternativa:** Incluir no endpoint raiz `/`

---

### 3. Authentication Validation - `/auth/validate` (OPCIONAL)

**Método:** `GET`

**Headers esperados:**
- `X-API-Key: sua-api-key`
- `Authorization: Bearer sua-api-key`

**Resposta quando válida (200 OK):**
```json
{
  "valid": true,
  "user": {
    "id": "123",
    "name": "Usuário",
    "email": "usuario@example.com"
  },
  "permissions": ["read", "write"]
}
```

**Resposta quando inválida (401 Unauthorized):**
```json
{
  "valid": false,
  "error": "Invalid API key"
}
```

---

### 4. Dependencies Check - `/status/dependencies` (RECOMENDADO)

**Método:** `GET`

**Resposta esperada:**
```json
{
  "services": {
    "database": "healthy",
    "redis": "healthy",
    "evolution_api": "healthy",
    "smtp": "healthy"
  }
}
```

**Status possíveis:**
- `"healthy"` ou `"ok"` ou `true` = Serviço funcionando
- `"unhealthy"` ou `"down"` ou `false` = Serviço com problema

**Formato alternativo:**
```json
{
  "dependencies": {
    "database": {
      "status": "healthy",
      "response_time_ms": 5
    },
    "redis": {
      "status": "healthy",
      "response_time_ms": 2
    }
  }
}
```

---

## 🛠️ Implementação Rápida

### Exemplo Node.js/Express

```javascript
const express = require('express');
const app = express();

// 1. Health Check
app.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString()
  });
});

// 2. Version
app.get('/version', (req, res) => {
  res.json({
    version: '1.0.0',
    api_name: '28Fácil API',
    environment: process.env.NODE_ENV || 'production'
  });
});

// 3. Auth Validation
app.get('/auth/validate', (req, res) => {
  const apiKey = req.headers['x-api-key'] || 
                 req.headers.authorization?.replace('Bearer ', '');
  
  if (!apiKey) {
    return res.status(401).json({ valid: false, error: 'API key missing' });
  }
  
  // Validar sua API key (exemplo simples)
  if (apiKey === process.env.VALID_API_KEY) {
    res.json({
      valid: true,
      user: {
        id: '1',
        name: 'Admin',
        email: 'admin@28facil.com.br'
      },
      permissions: ['read', 'write']
    });
  } else {
    res.status(401).json({ valid: false, error: 'Invalid API key' });
  }
});

// 4. Dependencies Status
app.get('/status/dependencies', async (req, res) => {
  const services = {
    database: await checkDatabase(),
    redis: await checkRedis(),
    evolution_api: await checkEvolutionAPI()
  };
  
  res.json({ services });
});

// Funções auxiliares
async function checkDatabase() {
  try {
    // Testar conexão com banco
    await db.query('SELECT 1');
    return 'healthy';
  } catch (error) {
    return 'unhealthy';
  }
}

async function checkRedis() {
  try {
    await redis.ping();
    return 'healthy';
  } catch (error) {
    return 'unhealthy';
  }
}

async function checkEvolutionAPI() {
  try {
    const response = await fetch('http://evolution-api:8080/health');
    return response.ok ? 'healthy' : 'unhealthy';
  } catch (error) {
    return 'unhealthy';
  }
}

app.listen(3000);
```

---

### Exemplo PHP/Laravel

```php
<?php

// routes/api.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

// 1. Health Check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String()
    ]);
});

// 2. Version
Route::get('/version', function () {
    return response()->json([
        'version' => config('app.version', '1.0.0'),
        'api_name' => '28Fácil API',
        'environment' => config('app.env')
    ]);
});

// 3. Auth Validation
Route::get('/auth/validate', function (Request $request) {
    $apiKey = $request->header('X-API-Key') 
           ?? str_replace('Bearer ', '', $request->header('Authorization') ?? '');
    
    if (!$apiKey) {
        return response()->json([
            'valid' => false,
            'error' => 'API key missing'
        ], 401);
    }
    
    // Validar API key
    $user = DB::table('users')->where('api_key', $apiKey)->first();
    
    if ($user) {
        return response()->json([
            'valid' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'permissions' => ['read', 'write']
        ]);
    }
    
    return response()->json([
        'valid' => false,
        'error' => 'Invalid API key'
    ], 401);
});

// 4. Dependencies Status
Route::get('/status/dependencies', function () {
    $services = [];
    
    // Check Database
    try {
        DB::connection()->getPdo();
        $services['database'] = 'healthy';
    } catch (\Exception $e) {
        $services['database'] = 'unhealthy';
    }
    
    // Check Redis
    try {
        Redis::ping();
        $services['redis'] = 'healthy';
    } catch (\Exception $e) {
        $services['redis'] = 'unhealthy';
    }
    
    // Check Evolution API
    try {
        $response = Http::timeout(5)->get('http://evolution-api:8080/health');
        $services['evolution_api'] = $response->successful() ? 'healthy' : 'unhealthy';
    } catch (\Exception $e) {
        $services['evolution_api'] = 'unhealthy';
    }
    
    return response()->json(['services' => $services]);
});
```

---

## ⚡ Implementação Mínima (Só o essencial)

Se você quiser começar com o **mínimo**, implemente apenas:

### Endpoint `/health`

```javascript
// Node.js
app.get('/health', (req, res) => {
  res.json({ status: 'ok' });
});
```

```php
// PHP
Route::get('/health', fn() => response()->json(['status' => 'ok']));
```

Com isso, já funciona o `isHealthy()` do pacote!

---

## 🔒 Segurança

### Endpoints Públicos
- `/health` - Pode ser público
- `/version` - Pode ser público

### Endpoints Protegidos
- `/auth/validate` - Deve validar API key
- `/status/dependencies` - **Recomendado proteger** (informações sensíveis)

**Exemplo de proteção:**

```javascript
app.get('/status/dependencies', requireAuth, async (req, res) => {
  // seu código
});

function requireAuth(req, res, next) {
  const apiKey = req.headers['x-api-key'];
  if (apiKey === process.env.ADMIN_API_KEY) {
    next();
  } else {
    res.status(401).json({ error: 'Unauthorized' });
  }
}
```

---

## 🧠 Testes

Depois de implementar, teste com curl:

```bash
# Health Check
curl https://api.28facil.com.br/health

# Version
curl https://api.28facil.com.br/version

# Auth (com sua API key)
curl -H "X-API-Key: sua-chave" https://api.28facil.com.br/auth/validate

# Dependencies
curl https://api.28facil.com.br/status/dependencies
```

Ou use o próprio pacote:

```bash
cd 28facil-integrity
php examples/basic_usage.php
```

---

## 📝 Resumo

| Endpoint | Método | Prioridade | Status HTTP |
|----------|---------|------------|-------------|
| `/health` | GET | **OBRIGATÓRIO** | 200 |
| `/version` | GET | Recomendado | 200 |
| `/auth/validate` | GET | Opcional | 200/401 |
| `/status/dependencies` | GET | Recomendado | 200 |

**Começa com `/health` e adiciona os outros conforme necessário!**
