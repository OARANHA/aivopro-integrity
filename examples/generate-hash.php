<?php
/**
 * Script para gerar hash de senha para inserir no banco
 */

if ($argc < 2) {
    echo "Uso: php generate-hash.php <senha>\n";
    echo "Exemplo: php generate-hash.php minhasenha123\n";
    exit(1);
}

$senha = $argv[1];
$hash = password_hash($senha, PASSWORD_BCRYPT);

echo "\n";
echo "=" . str_repeat("=", 60) . "\n";
echo "Senha: {$senha}\n";
echo "Hash:  {$hash}\n";
echo "=" . str_repeat("=", 60) . "\n";
echo "\n";
echo "SQL para inserir usuário:\n";
echo "\n";
echo "INSERT INTO users (name, email, password, email_verified_at, is_active)\n";
echo "VALUES (\n";
echo "    'Nome do Usuário',\n";
echo "    'email@exemplo.com',\n";
echo "    '{$hash}',\n";
echo "    NOW(),\n";
echo "    TRUE\n";
echo ");\n";
echo "\n";
