<?php
/**
 * Exemplos práticos de uso do JWT com 28Fácil
 */

require_once __DIR__ . '/../vendor/autoload.php';

use AiVoPro\Integrity\IntegrityManager;

// =====================================================
// EXEMPLO 1: Login e obter JWT Token
// =====================================================

function loginAndGetToken(string $email, string $password): ?array
{
    $apiUrl = 'https://api.28facil.com.br';
    
    $ch = curl_init("{$apiUrl}/auth/login");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $email,
            'password' => $password,
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    echo "Erro no login: {$response}\n";
    return null;
}

// Fazer login
$loginResult = loginAndGetToken('teste@28facil.com.br', 'senha123');

if ($loginResult && $loginResult['success']) {
    echo "✅ Login realizado com sucesso!\n";
    echo "Token: {$loginResult['access_token']}\n";
    echo "Expira em: {$loginResult['expires_in']} segundos\n";
    echo "Usuário: {$loginResult['user']['name']}\n\n";
    
    $jwtToken = $loginResult['access_token'];
} else {
    die("❌ Falha no login\n");
}

// =====================================================
// EXEMPLO 2: Usar JWT com IntegrityManager
// =====================================================

echo "--- Testando com JWT Token ---\n";

$manager = new IntegrityManager(
    'https://api.28facil.com.br',
    $jwtToken
);

// Verificar autenticação
$authCheck = $manager->checkAuthentication();

if ($authCheck->isPassed()) {
    echo "✅ Autenticado com JWT!\n";
    echo "Tipo: {$authCheck->getData()['auth_type']}\n";
    echo "Usuário: {$authCheck->getData()['user']['name']}\n";
    echo "Permissões: " . implode(', ', $authCheck->getData()['permissions']) . "\n";
} else {
    echo "❌ Falha na autenticação: {$authCheck->getMessage()}\n";
}

echo "\n";

// =====================================================
// EXEMPLO 3: Usar API Key (método tradicional)
// =====================================================

echo "--- Testando com API Key ---\n";

$managerApiKey = new IntegrityManager(
    'https://api.28facil.com.br',
    '28fc_sua_api_key_aqui'  // Trocar pela sua key real
);

$authCheckApiKey = $managerApiKey->checkAuthentication();

if ($authCheckApiKey->isPassed()) {
    echo "✅ Autenticado com API Key!\n";
    echo "Tipo: {$authCheckApiKey->getData()['auth_type']}\n";
    echo "Uso: {$authCheckApiKey->getData()['usage_count']} requisições\n";
} else {
    echo "❌ Falha na autenticação: {$authCheckApiKey->getMessage()}\n";
}

echo "\n";

// =====================================================
// EXEMPLO 4: Renovar token expirado
// =====================================================

function refreshToken(string $refreshToken): ?string
{
    $apiUrl = 'https://api.28facil.com.br';
    
    $ch = curl_init("{$apiUrl}/auth/refresh");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$refreshToken}"
        ],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    return null;
}

// Usar refresh token
if (isset($loginResult['refresh_token'])) {
    echo "--- Renovando token ---\n";
    $newAccessToken = refreshToken($loginResult['refresh_token']);
    
    if ($newAccessToken) {
        echo "✅ Token renovado com sucesso!\n";
        echo "Novo token: {$newAccessToken}\n";
    } else {
        echo "❌ Falha ao renovar token\n";
    }
}

echo "\n";

// =====================================================
// EXEMPLO 5: Health Check completo
// =====================================================

echo "--- Auditoria Completa ---\n";

$report = $manager->audit();

echo "Status: {$report->getStatus()}\n";
echo "Versão: {$report->getVersion()}\n";
echo "Tempo de resposta: {$report->getResponseTime()}ms\n\n";

echo "Checagens:\n";
foreach ($report->getChecks() as $check) {
    $icon = $check->isPassed() ? '✅' : '❌';
    echo sprintf(
        "%s %s: %s\n",
        $icon,
        $check->getName(),
        $check->getMessage()
    );
}
