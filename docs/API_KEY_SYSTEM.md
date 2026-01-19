# Sistema de Gerenciamento de API Keys - 28Fácil

## 🎯 Objetivo

Criar um sistema completo para:
1. **Gerar** API Keys para clientes
2. **Validar** API Keys nas requisições
3. **Gerenciar** (listar, revogar, renovar) API Keys
4. **Monitorar** uso das API Keys

---

## 📦 Arquitetura

```
[🌐 Site 28facil.com.br]
        ↓
   Gera API Key
        ↓
[💾 Banco de Dados]
        ↓
[🔑 API Key: 28fc_abc123...]
        ↓
   Cliente usa
        ↓
[🚫 api.28facil.com.br/auth/validate]
        ↓
   Valida no banco
        ↓
[✅ Autorizado / ❌ Negado]
```

---

## 📊 Banco de Dados

### Tabela: `api_keys`

```sql
CREATE TABLE api_keys (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Identificação
    key_hash VARCHAR(255) NOT NULL UNIQUE COMMENT 'Hash SHA256 da key',
    key_prefix VARCHAR(20) NOT NULL COMMENT 'Prefixo visível (ex: 28fc_abc)',
    
    -- Proprietário
    user_id BIGINT UNSIGNED NULL COMMENT 'ID do usuário dono',
    name VARCHAR(255) NOT NULL COMMENT 'Nome descritivo da key',
    
    -- Permissões
    permissions JSON NOT NULL DEFAULT '[]' COMMENT 'Array de permissões',
    rate_limit INT UNSIGNED DEFAULT 1000 COMMENT 'Requisições por hora',
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL COMMENT 'Data de expiração',
    
    -- Uso
    last_used_at TIMESTAMP NULL,
    usage_count BIGINT UNSIGNED DEFAULT 0,
    last_ip VARCHAR(45) NULL,
    
    -- Auditoria
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    revoked_at TIMESTAMP NULL,
    revoked_reason TEXT NULL,
    
    INDEX idx_key_hash (key_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Tabela: `api_key_logs` (Opcional - para auditoria)

```sql
CREATE TABLE api_key_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_key_id BIGINT UNSIGNED NOT NULL,
    
    -- Requisição
    endpoint VARCHAR(500) NOT NULL,
    method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    
    -- Resposta
    status_code INT UNSIGNED,
    response_time_ms INT UNSIGNED,
    
    -- Data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🔑 Formato da API Key

### Padrão: `28fc_` + `32 caracteres aleatórios`

Exemplo: `28fc_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6`

**Explicação:**
- `28fc` = Prefixo da empresa (28Fácil)
- `_` = Separador
- 32 caracteres = Chave secreta (aleatória)

**Segurança:**
- A key completa é mostrada **apenas UMA VEZ** na criação
- No banco salvamos apenas o **hash SHA256**
- Guardamos o **prefixo** (`28fc_abc`) para identificação visual

---

## 💻 Código - Geração de API Key

### PHP (Laravel ou puro)

```php
<?php

class ApiKeyManager
{
    /**
     * Gera uma nova API Key
     */
    public static function generate(
        string $name,
        int $userId = null,
        array $permissions = ['read'],
        ?DateTime $expiresAt = null
    ): array {
        // Gerar chave aleatória segura
        $randomBytes = random_bytes(24); // 24 bytes = 32 chars em hex
        $secret = bin2hex($randomBytes);
        $fullKey = '28fc_' . $secret;
        
        // Hash para salvar no banco
        $keyHash = hash('sha256', $fullKey);
        
        // Prefixo para identificação (primeiros 8 chars depois do _)
        $keyPrefix = '28fc_' . substr($secret, 0, 8);
        
        // Salvar no banco
        $apiKeyId = DB::table('api_keys')->insertGetId([
            'key_hash' => $keyHash,
            'key_prefix' => $keyPrefix,
            'user_id' => $userId,
            'name' => $name,
            'permissions' => json_encode($permissions),
            'is_active' => true,
            'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
            'created_at' => now(),
        ]);
        
        // Retornar (ATENÇÃO: mostrar fullKey apenas UMA VEZ!)
        return [
            'id' => $apiKeyId,
            'key' => $fullKey, // Mostrar ao usuário AGORA
            'prefix' => $keyPrefix, // Para exibir em listas
            'name' => $name,
            'created_at' => now()->toIso8601String(),
        ];
    }
    
    /**
     * Valida uma API Key
     */
    public static function validate(string $apiKey): ?array
    {
        // Verificar formato
        if (!str_starts_with($apiKey, '28fc_')) {
            return null;
        }
        
        // Hash da key fornecida
        $keyHash = hash('sha256', $apiKey);
        
        // Buscar no banco
        $record = DB::table('api_keys')
            ->where('key_hash', $keyHash)
            ->where('is_active', true)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();
        
        if (!$record) {
            return null;
        }
        
        // Atualizar estatísticas de uso
        DB::table('api_keys')
            ->where('id', $record->id)
            ->update([
                'last_used_at' => now(),
                'usage_count' => DB::raw('usage_count + 1'),
                'last_ip' => request()->ip() ?? null,
            ]);
        
        // Retornar dados da key
        return [
            'id' => $record->id,
            'user_id' => $record->user_id,
            'name' => $record->name,
            'permissions' => json_decode($record->permissions, true),
            'rate_limit' => $record->rate_limit,
        ];
    }
    
    /**
     * Revogar uma API Key
     */
    public static function revoke(int $keyId, string $reason = null): bool
    {
        return DB::table('api_keys')
            ->where('id', $keyId)
            ->update([
                'is_active' => false,
                'revoked_at' => now(),
                'revoked_reason' => $reason,
            ]) > 0;
    }
}
```

---

## 🚀 Endpoints da API

### 1. **POST /api/keys** - Criar nova API Key

```javascript
// Request
POST /api/keys
Authorization: Bearer {token_do_usuario}
Content-Type: application/json

{
  "name": "Minha API Key de Produção",
  "permissions": ["read", "write"],
  "expires_at": "2027-12-31"
}

// Response (200 OK)
{
  "success": true,
  "data": {
    "id": 1,
    "key": "28fc_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6",
    "prefix": "28fc_a1b2c3d4",
    "name": "Minha API Key de Produção",
    "permissions": ["read", "write"],
    "created_at": "2026-01-19T17:30:00-03:00"
  },
  "message": "API Key criada! Guarde-a em local seguro, não será exibida novamente."
}
```

### 2. **GET /api/keys** - Listar suas API Keys

```javascript
// Request
GET /api/keys
Authorization: Bearer {token_do_usuario}

// Response (200 OK)
{
  "success": true,
  "data": [
    {
      "id": 1,
      "prefix": "28fc_a1b2c3d4",
      "name": "Minha API Key de Produção",
      "permissions": ["read", "write"],
      "is_active": true,
      "last_used_at": "2026-01-19T15:20:00-03:00",
      "usage_count": 1523,
      "created_at": "2026-01-01T10:00:00-03:00"
    }
  ]
}
```

### 3. **GET /auth/validate** - Validar API Key (usado pelo Integrity)

```javascript
// Request
GET /auth/validate
X-API-Key: 28fc_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6

// Response Válida (200 OK)
{
  "valid": true,
  "user": {
    "id": 123,
    "name": "João Silva",
    "email": "joao@example.com"
  },
  "permissions": ["read", "write"],
  "rate_limit": 1000
}

// Response Inválida (401 Unauthorized)
{
  "valid": false,
  "error": "Invalid or expired API key"
}
```

### 4. **DELETE /api/keys/{id}** - Revogar API Key

```javascript
// Request
DELETE /api/keys/1
Authorization: Bearer {token_do_usuario}
Content-Type: application/json

{
  "reason": "Não uso mais"
}

// Response (200 OK)
{
  "success": true,
  "message": "API Key revogada com sucesso"
}
```

---

## 🌐 Interface Web (Dashboard)

Crie uma página em `28facil.com.br/dashboard/api-keys`

Veja o arquivo: `server-examples/api-key-dashboard.html`

---

## 🔒 Middleware de Validação

### Laravel

```php
<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\ApiKeyManager;

class ValidateApiKey
{
    public function handle($request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key') 
               ?? str_replace('Bearer ', '', $request->header('Authorization', ''));
        
        if (!$apiKey) {
            return response()->json([
                'error' => 'API Key required'
            ], 401);
        }
        
        $keyData = ApiKeyManager::validate($apiKey);
        
        if (!$keyData) {
            return response()->json([
                'error' => 'Invalid API Key'
            ], 401);
        }
        
        // Adicionar dados ao request
        $request->merge(['api_key_data' => $keyData]);
        
        return $next($request);
    }
}
```

### Express.js

```javascript
const validateApiKey = async (req, res, next) => {
  const apiKey = req.headers['x-api-key'] || 
                 req.headers.authorization?.replace('Bearer ', '');
  
  if (!apiKey) {
    return res.status(401).json({ error: 'API Key required' });
  }
  
  const keyData = await ApiKeyManager.validate(apiKey);
  
  if (!keyData) {
    return res.status(401).json({ error: 'Invalid API Key' });
  }
  
  req.apiKeyData = keyData;
  next();
};
```

---

## ✅ Checklist de Implementação

- [ ] Criar tabelas no banco de dados
- [ ] Implementar classe `ApiKeyManager`
- [ ] Criar endpoint `POST /api/keys` (gerar)
- [ ] Criar endpoint `GET /api/keys` (listar)
- [ ] Criar endpoint `GET /auth/validate` (validar)
- [ ] Criar endpoint `DELETE /api/keys/{id}` (revogar)
- [ ] Criar middleware de validação
- [ ] Criar interface web de gerenciamento
- [ ] Testar com o pacote Integrity
- [ ] Documentar para os usuários

---

## 🧪 Testando

```bash
# 1. Gerar API Key (via dashboard ou API)
curl -X POST https://api.28facil.com.br/api/keys \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Teste","permissions":["read"]}'

# 2. Testar validação
curl https://api.28facil.com.br/auth/validate \
  -H "X-API-Key: 28fc_sua_key_aqui"

# 3. Testar com Integrity
php -r '
use AiVoPro\Integrity\IntegrityManager;
$m = new IntegrityManager("https://api.28facil.com.br", "28fc_sua_key_aqui");
$r = $m->checkAuthentication();
echo $r->isPassed() ? "Válida!" : "Inválida!";
'
```
