# 🔐 Autenticação JWT - 28Fácil

## 📋 O que é JWT?

JWT (JSON Web Token) é um padrão aberto para transmitir informações de forma segura entre partes como um objeto JSON. É assinado digitalmente, garantindo que não pode ser alterado.

### Vantagens sobre API Keys simples:
- ✅ **Expira automaticamente** (não precisa revogar)
- ✅ **Contém informações** (user_id, permissions, etc)
- ✅ **Stateless** (não precisa consultar banco toda vez)
- ✅ **Mais seguro** (assinado criptograficamente)

---

## 🔄 Sistema Híbrido

O 28Fácil agora aceita **AMBOS**:

### 1. **API Keys** (para integrações permanentes)
```
X-API-Key: 28fc_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
```

### 2. **JWT Tokens** (para sessões de usuários)
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## 🎯 Quando usar cada um?

| Situação | Use |
|----------|-----|
| **Integração backend-to-backend** | API Key |
| **Aplicação web (frontend)** | JWT Token |
| **Scripts automatizados** | API Key |
| **Usuários logados** | JWT Token |
| **Serviços externos** | API Key |
| **App mobile** | JWT Token |

---

## 🔧 Como Funciona?

### Estrutura de um JWT

Um JWT tem 3 partes separadas por `.`:

```
HEADER.PAYLOAD.SIGNATURE
```

**Exemplo:**
```
eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxMjMsInBlcm1pc3Npb25zIjpbInJlYWQiLCJ3cml0ZSJdLCJleHAiOjE3MDU2NzgwMDB9.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c
```

### Decodificado:

**Header:**
```json
{
  "alg": "HS256",
  "typ": "JWT"
}
```

**Payload:**
```json
{
  "user_id": 123,
  "permissions": ["read", "write"],
  "exp": 1705678000
}
```

**Signature:**
```
HMACSHA256(
  base64UrlEncode(header) + "." + base64UrlEncode(payload),
  secret_key
)
```

---

## 💻 Implementação PHP

### Gerar JWT Token

```php
use Firebase\JWT\JWT;

function generateToken(int $userId, array $permissions = [], int $expiresIn = 3600): string
{
    $secretKey = getenv('JWT_SECRET') ?: 'sua_chave_secreta_aqui';
    
    $payload = [
        'iss' => 'api.28facil.com.br',        // Emissor
        'aud' => '28facil-clients',            // Audiência
        'iat' => time(),                       // Emitido em
        'exp' => time() + $expiresIn,          // Expira em
        'user_id' => $userId,
        'permissions' => $permissions,
    ];
    
    return JWT::encode($payload, $secretKey, 'HS256');
}

// Exemplo:
$token = generateToken(userId: 123, permissions: ['read', 'write'], expiresIn: 3600);
echo $token;
```

### Validar JWT Token

```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

function validateToken(string $token): ?array
{
    $secretKey = getenv('JWT_SECRET') ?: 'sua_chave_secreta_aqui';
    
    try {
        $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
        
        return [
            'valid' => true,
            'user_id' => $decoded->user_id,
            'permissions' => $decoded->permissions,
            'expires_at' => date('Y-m-d H:i:s', $decoded->exp),
        ];
    } catch (ExpiredException $e) {
        return ['valid' => false, 'error' => 'Token expirado'];
    } catch (SignatureInvalidException $e) {
        return ['valid' => false, 'error' => 'Assinatura inválida'];
    } catch (\Exception $e) {
        return ['valid' => false, 'error' => 'Token inválido'];
    }
}
```

---

## 🚀 Endpoints da API

### 1. **POST /auth/login** - Fazer login e obter JWT

```bash
curl -X POST https://api.28facil.com.br/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "usuario@exemplo.com",
    "password": "senha123"
  }'
```

**Resposta:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 3600,
  "user": {
    "id": 123,
    "name": "João Silva",
    "email": "usuario@exemplo.com"
  }
}
```

### 2. **POST /auth/refresh** - Renovar token expirado

```bash
curl -X POST https://api.28facil.com.br/auth/refresh \
  -H "Authorization: Bearer eyJ..." 
```

### 3. **GET /auth/validate** - Validar token ou API key

```bash
# Com JWT
curl -H "Authorization: Bearer eyJ..." \
     https://api.28facil.com.br/auth/validate

# Com API Key
curl -H "X-API-Key: 28fc_..." \
     https://api.28facil.com.br/auth/validate
```

**Resposta (ambos):**
```json
{
  "valid": true,
  "auth_type": "jwt",
  "user": {
    "id": 123,
    "name": "João Silva",
    "email": "usuario@exemplo.com"
  },
  "permissions": ["read", "write"],
  "expires_at": "2026-01-19T18:30:00-03:00"
}
```

---

## 🔐 Usando com o Integrity Manager

### Com API Key (permanente)

```php
use AiVoPro\Integrity\IntegrityManager;

$manager = new IntegrityManager(
    'https://api.28facil.com.br',
    '28fc_sua_api_key'
);

$result = $manager->checkAuthentication();
```

### Com JWT Token (sessão)

```php
use AiVoPro\Integrity\IntegrityManager;

// 1. Fazer login primeiro
$token = loginAndGetToken('usuario@exemplo.com', 'senha123');

// 2. Usar o token
$manager = new IntegrityManager(
    'https://api.28facil.com.br',
    $token,
    authType: 'jwt'  // Especificar que é JWT
);

$result = $manager->checkAuthentication();
```

---

## 🛡️ Segurança

### JWT Secret Key

**IMPORTANTE:** Use uma chave forte e mantenha em segredo!

```bash
# Gerar uma chave segura
openssl rand -base64 64
```

No `.env`:
```bash
JWT_SECRET=SuaChaveMuitoSeguraGeradaPeloOpensslComPeloMenos64Caracteres
```

### Tempo de Expiração

Recomendações:
- **Web App:** 1 hora (3600s)
- **Mobile App:** 7 dias (604800s)
- **Refresh Token:** 30 dias (2592000s)

### Refresh Token Strategy

```php
// Gerar token de acesso (curto) + refresh token (longo)
function generateTokenPair(int $userId): array
{
    $accessToken = generateToken($userId, ['read', 'write'], 3600);      // 1h
    $refreshToken = generateToken($userId, ['refresh'], 2592000);        // 30d
    
    return [
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'token_type' => 'Bearer',
        'expires_in' => 3600,
    ];
}
```

---

## 🧪 Testar JWT

### Decodificar JWT online

Visite: https://jwt.io

Cole seu token e veja o conteúdo decodificado (sem validar assinatura).

### Testar com curl

```bash
# 1. Fazer login
TOKEN=$(curl -s -X POST https://api.28facil.com.br/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@exemplo.com","password":"senha"}' \
  | jq -r '.token')

# 2. Usar o token
curl -H "Authorization: Bearer $TOKEN" \
     https://api.28facil.com.br/auth/validate
```

---

## 📊 Comparação: API Key vs JWT

| Característica | API Key | JWT Token |
|----------------|---------|----------|
| **Validade** | Permanente | Temporária |
| **Revogação** | Manual no banco | Automática (expira) |
| **Informações** | Apenas ID | User, permissions, etc |
| **Performance** | Consulta banco | Stateless (mais rápido) |
| **Segurança** | Média | Alta |
| **Uso** | Backend | Frontend/Mobile |
| **Formato** | `28fc_...` | `eyJ...` |

---

## ✅ Checklist de Implementação

- [ ] Instalar `firebase/php-jwt`
- [ ] Configurar `JWT_SECRET` no `.env`
- [ ] Criar endpoint `/auth/login`
- [ ] Criar endpoint `/auth/refresh`
- [ ] Atualizar `/auth/validate` para aceitar JWT
- [ ] Criar função `generateToken()`
- [ ] Criar função `validateToken()`
- [ ] Testar com Postman/curl
- [ ] Integrar com Integrity Manager
- [ ] Documentar para desenvolvedores

---

## 🔗 Recursos

- [JWT.io](https://jwt.io) - Debugger JWT
- [Firebase PHP-JWT](https://github.com/firebase/php-jwt) - Biblioteca PHP
- [RFC 7519](https://datatracker.ietf.org/doc/html/rfc7519) - Especificação JWT

---

**Desenvolvido com ❤️ pela 28Fácil**
