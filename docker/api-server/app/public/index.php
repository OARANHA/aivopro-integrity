<?php
/**
 * API Server - 28Fácil
 * Endpoint principal para validação de API Keys
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

// Responder OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoload (se usar Composer)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
}

// Configuração do banco
$dbConfig = [
    'host' => getenv('DB_HOST') ?: 'mysql',
    'port' => getenv('DB_PORT') ?: 3306,
    'database' => getenv('DB_DATABASE') ?: '28facil_api',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: 'senha',
];

// Conectar ao banco
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database connection failed',
        'message' => $e->getMessage()
    ]);
    exit;
}

// Router simples
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// =====================================================
// ROTAS
// =====================================================

// GET / - Health check
if ($requestUri === '/' && $requestMethod === 'GET') {
    echo json_encode([
        'status' => 'ok',
        'service' => '28Fácil API Server',
        'version' => '1.0.0',
        'timestamp' => date('c'),
    ]);
    exit;
}

// GET /auth/validate - Validar API Key
if ($requestUri === '/auth/validate' && $requestMethod === 'GET') {
    // Extrair API key
    $apiKey = null;
    
    if (isset($_SERVER['HTTP_X_API_KEY'])) {
        $apiKey = $_SERVER['HTTP_X_API_KEY'];
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $apiKey = str_replace('Bearer ', '', $_SERVER['HTTP_AUTHORIZATION']);
    }
    
    if (!$apiKey) {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'error' => 'API key not provided'
        ]);
        exit;
    }
    
    // Validar formato
    if (!str_starts_with($apiKey, '28fc_')) {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'error' => 'Invalid API key format'
        ]);
        exit;
    }
    
    // Gerar hash
    $keyHash = hash('sha256', $apiKey);
    
    // Buscar no banco
    $stmt = $pdo->prepare("
        SELECT 
            id, user_id, name, permissions, rate_limit, usage_count
        FROM api_keys 
        WHERE key_hash = :hash 
        AND is_active = 1
        AND (expires_at IS NULL OR expires_at > NOW())
        LIMIT 1
    ");
    $stmt->execute(['hash' => $keyHash]);
    $keyData = $stmt->fetch();
    
    if (!$keyData) {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'error' => 'Invalid or expired API key'
        ]);
        exit;
    }
    
    // Atualizar estatísticas
    $updateStmt = $pdo->prepare("
        UPDATE api_keys 
        SET 
            last_used_at = NOW(),
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
    
    // Buscar dados do usuário (opcional)
    $user = null;
    if ($keyData->user_id) {
        $userStmt = $pdo->prepare("
            SELECT id, name, email 
            FROM users 
            WHERE id = :user_id
        ");
        $userStmt->execute(['user_id' => $keyData->user_id]);
        $userData = $userStmt->fetch();
        
        if ($userData) {
            $user = [
                'id' => $userData->id,
                'name' => $userData->name,
                'email' => $userData->email,
            ];
        }
    }
    
    // Resposta de sucesso
    echo json_encode([
        'valid' => true,
        'user' => $user,
        'permissions' => json_decode($keyData->permissions),
        'rate_limit' => (int) $keyData->rate_limit,
        'usage_count' => (int) $keyData->usage_count + 1,
    ]);
    exit;
}

// Rota não encontrada
http_response_code(404);
echo json_encode([
    'error' => 'Endpoint not found',
    'path' => $requestUri,
]);
