<?php
/**
 * API Server - 28Fácil
 * Sistema Híbrido: API Keys + JWT Tokens
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Responder OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuração
$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'mysql',
    'port' => getenv('DB_PORT') ?: 3306,
    'database' => getenv('DB_DATABASE') ?: '28facil_api',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: 'senha',
];

$jwtSecret = getenv('JWT_SECRET') ?: 'trocar_isso_por_algo_seguro';

// Conectar ao banco
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Router
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// =====================================================
// FUNÇÕES AUXILIARES
// =====================================================

/**
 * Gerar JWT Token
 */
function generateJWT(int $userId, array $permissions, int $expiresIn = 3600): string
{
    global $jwtSecret;
    
    $payload = [
        'iss' => 'api.28facil.com.br',
        'aud' => '28facil-clients',
        'iat' => time(),
        'exp' => time() + $expiresIn,
        'user_id' => $userId,
        'permissions' => $permissions,
    ];
    
    return JWT::encode($payload, $jwtSecret, 'HS256');
}

/**
 * Validar JWT Token
 */
function validateJWT(string $token): ?array
{
    global $jwtSecret;
    
    try {
        $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));
        
        return [
            'valid' => true,
            'user_id' => $decoded->user_id,
            'permissions' => $decoded->permissions,
            'expires_at' => date('c', $decoded->exp),
            'auth_type' => 'jwt',
        ];
    } catch (ExpiredException $e) {
        return ['valid' => false, 'error' => 'Token expirado'];
    } catch (SignatureInvalidException $e) {
        return ['valid' => false, 'error' => 'Assinatura inválida'];
    } catch (\Exception $e) {
        return ['valid' => false, 'error' => 'Token inválido'];
    }
}

/**
 * Validar API Key
 */
function validateApiKey(string $apiKey): ?array
{
    global $pdo;
    
    if (!str_starts_with($apiKey, '28fc_')) {
        return ['valid' => false, 'error' => 'Formato de API key inválido'];
    }
    
    $keyHash = hash('sha256', $apiKey);
    
    $stmt = $pdo->prepare("
        SELECT id, user_id, name, permissions, rate_limit, usage_count
        FROM api_keys 
        WHERE key_hash = :hash 
        AND is_active = 1
        AND (expires_at IS NULL OR expires_at > NOW())
        LIMIT 1
    ");
    $stmt->execute(['hash' => $keyHash]);
    $keyData = $stmt->fetch();
    
    if (!$keyData) {
        return ['valid' => false, 'error' => 'API key inválida ou expirada'];
    }
    
    // Atualizar estatísticas
    $updateStmt = $pdo->prepare("
        UPDATE api_keys 
        SET last_used_at = NOW(),
            usage_count = usage_count + 1,
            last_ip = :ip,
            last_user_agent = :user_agent
        WHERE id = :id
    ");
    $updateStmt->execute([
        'id' => $keyData->id,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ]);
    
    return [
        'valid' => true,
        'user_id' => $keyData->user_id,
        'permissions' => json_decode($keyData->permissions),
        'rate_limit' => (int) $keyData->rate_limit,
        'usage_count' => (int) $keyData->usage_count + 1,
        'auth_type' => 'api_key',
    ];
}

/**
 * Extrair credencial dos headers
 */
function extractCredential(): ?array
{
    // Tentar X-API-Key primeiro
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        return ['type' => 'api_key', 'value' => $_SERVER['HTTP_X_API_KEY']];
    }
    
    // Tentar Authorization Bearer
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(\S+)/', $auth, $matches)) {
            $token = $matches[1];
            // Verificar se é JWT (começa com eyJ) ou API key (28fc_)
            if (str_starts_with($token, 'eyJ')) {
                return ['type' => 'jwt', 'value' => $token];
            } elseif (str_starts_with($token, '28fc_')) {
                return ['type' => 'api_key', 'value' => $token];
            }
        }
    }
    
    return null;
}

// =====================================================
// ROTAS
// =====================================================

// GET / - Health check
if ($requestUri === '/' && $requestMethod === 'GET') {
    echo json_encode([
        'status' => 'ok',
        'service' => '28Fácil API Server',
        'version' => '2.0.0',
        'auth_methods' => ['api_key', 'jwt'],
        'timestamp' => date('c'),
    ]);
    exit;
}

// POST /auth/login - Fazer login e obter JWT
if ($requestUri === '/auth/login' && $requestMethod === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email e senha são obrigatórios']);
        exit;
    }
    
    // Buscar usuário
    $stmt = $pdo->prepare("
        SELECT id, name, email, password 
        FROM users 
        WHERE email = :email
    ");
    $stmt->execute(['email' => $input['email']]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($input['password'], $user->password)) {
        http_response_code(401);
        echo json_encode(['error' => 'Credenciais inválidas']);
        exit;
    }
    
    // Gerar tokens
    $accessToken = generateJWT($user->id, ['read', 'write'], 3600);      // 1h
    $refreshToken = generateJWT($user->id, ['refresh'], 2592000);        // 30d
    
    echo json_encode([
        'success' => true,
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'token_type' => 'Bearer',
        'expires_in' => 3600,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
    ]);
    exit;
}

// POST /auth/refresh - Renovar token
if ($requestUri === '/auth/refresh' && $requestMethod === 'POST') {
    $credential = extractCredential();
    
    if (!$credential || $credential['type'] !== 'jwt') {
        http_response_code(401);
        echo json_encode(['error' => 'Refresh token inválido']);
        exit;
    }
    
    $tokenData = validateJWT($credential['value']);
    
    if (!$tokenData['valid']) {
        http_response_code(401);
        echo json_encode(['error' => $tokenData['error'] ?? 'Token inválido']);
        exit;
    }
    
    // Gerar novo access token
    $newAccessToken = generateJWT($tokenData['user_id'], $tokenData['permissions'], 3600);
    
    echo json_encode([
        'success' => true,
        'access_token' => $newAccessToken,
        'token_type' => 'Bearer',
        'expires_in' => 3600,
    ]);
    exit;
}

// GET /auth/validate - Validar API Key ou JWT
if ($requestUri === '/auth/validate' && $requestMethod === 'GET') {
    $credential = extractCredential();
    
    if (!$credential) {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'error' => 'Nenhuma credencial fornecida'
        ]);
        exit;
    }
    
    // Validar conforme o tipo
    if ($credential['type'] === 'jwt') {
        $result = validateJWT($credential['value']);
    } else {
        $result = validateApiKey($credential['value']);
    }
    
    if (!$result['valid']) {
        http_response_code(401);
        echo json_encode($result);
        exit;
    }
    
    // Buscar dados do usuário
    $user = null;
    if ($result['user_id']) {
        $userStmt = $pdo->prepare("
            SELECT id, name, email 
            FROM users 
            WHERE id = :user_id
        ");
        $userStmt->execute(['user_id' => $result['user_id']]);
        $userData = $userStmt->fetch();
        
        if ($userData) {
            $user = [
                'id' => $userData->id,
                'name' => $userData->name,
                'email' => $userData->email,
            ];
        }
    }
    
    echo json_encode([
        'valid' => true,
        'auth_type' => $result['auth_type'],
        'user' => $user,
        'permissions' => $result['permissions'],
        'rate_limit' => $result['rate_limit'] ?? null,
        'usage_count' => $result['usage_count'] ?? null,
        'expires_at' => $result['expires_at'] ?? null,
    ]);
    exit;
}

// Rota não encontrada
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint not found',
    'path' => $requestUri,
]);
