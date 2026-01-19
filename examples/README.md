# 🧪 Exemplos de Uso - 28Fácil Integrity

## 📁 Arquivos

### 1. `jwt-usage.php`
Exemplo completo em PHP mostrando:
- Login e obtenção de JWT
- Uso do JWT com IntegrityManager
- Comparação com API Key
- Renovação de token
- Health check completo

**Uso:**
```bash
php jwt-usage.php
```

### 2. `generate-hash.php`
Gera hash bcrypt de senhas para inserir no banco de dados.

**Uso:**
```bash
php generate-hash.php minhasenha123
```

**Saída:**
```
===============================================================
Senha: minhasenha123
Hash:  $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
===============================================================

SQL para inserir usuário:

INSERT INTO users (name, email, password, email_verified_at, is_active)
VALUES (
    'Nome do Usuário',
    'email@exemplo.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    NOW(),
    TRUE
);
```

### 3. `test-authentication.sh`
Script bash interativo para testar autenticação:
- Health check
- Login com email/senha
- Validação de JWT
- Renovação de token
- Teste de API Key

**Uso:**
```bash
chmod +x test-authentication.sh
./test-authentication.sh
```

---

## 🚀 Quick Start

### 1. Instalar dependências
```bash
composer install
```

### 2. Criar usuário de teste
```bash
php examples/generate-hash.php senha123

# Copiar o SQL gerado e executar no banco
mysql -u root -p 28facil_api < insert_user.sql
```

### 3. Testar autenticação
```bash
# Via script bash
./examples/test-authentication.sh

# Ou via PHP
php examples/jwt-usage.php
```

---

## 💡 Dicas

### Testar com curl

**Login:**
```bash
curl -X POST https://api.28facil.com.br/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "teste@28facil.com.br",
    "password": "senha123"
  }'
```

**Validar JWT:**
```bash
curl https://api.28facil.com.br/auth/validate \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

**Validar API Key:**
```bash
curl https://api.28facil.com.br/auth/validate \
  -H "X-API-Key: 28fc_sua_key_aqui"
```

### Decodificar JWT

Visite [jwt.io](https://jwt.io) e cole seu token para ver o conteúdo.

### Gerar JWT Secret

```bash
openssl rand -base64 64
```

Adicionar ao `.env`:
```bash
JWT_SECRET=sua_chave_gerada_aqui
```

---

## 📚 Referências

- [Documentação JWT](../docs/JWT_AUTHENTICATION.md)
- [Sistema de API Keys](../docs/API_KEY_SYSTEM.md)
- [Deploy com Docker](../README-DOCKER.md)
