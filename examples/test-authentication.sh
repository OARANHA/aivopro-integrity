#!/bin/bash

# =====================================================
# Script de teste de autenticação - 28Fácil
# =====================================================

API_URL="https://api.28facil.com.br"

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo "====================================="
echo "  Testes de Autenticação - 28Fácil"
echo "====================================="
echo ""

# =====================================================
# 1. Health Check
# =====================================================

echo -e "${YELLOW}1. Health Check${NC}"
RESPONSE=$(curl -s "$API_URL/")
echo "$RESPONSE" | jq .

if echo "$RESPONSE" | jq -e '.status == "ok"' > /dev/null; then
    echo -e "${GREEN}✓ API está online${NC}"
else
    echo -e "${RED}✗ API não está respondendo${NC}"
    exit 1
fi

echo ""

# =====================================================
# 2. Login com JWT
# =====================================================

echo -e "${YELLOW}2. Login (obter JWT)${NC}"

read -p "Email: " EMAIL
read -sp "Senha: " PASSWORD
echo ""

LOGIN_RESPONSE=$(curl -s -X POST "$API_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d "{
    \"email\": \"$EMAIL\",
    \"password\": \"$PASSWORD\"
  }")

echo "$LOGIN_RESPONSE" | jq .

if echo "$LOGIN_RESPONSE" | jq -e '.success == true' > /dev/null; then
    echo -e "${GREEN}✓ Login realizado com sucesso${NC}"
    JWT_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.access_token')
    REFRESH_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.refresh_token')
    echo "Token obtido: ${JWT_TOKEN:0:20}..."
else
    echo -e "${RED}✗ Falha no login${NC}"
    exit 1
fi

echo ""

# =====================================================
# 3. Validar JWT Token
# =====================================================

echo -e "${YELLOW}3. Validar JWT Token${NC}"

VALIDATE_RESPONSE=$(curl -s "$API_URL/auth/validate" \
  -H "Authorization: Bearer $JWT_TOKEN")

echo "$VALIDATE_RESPONSE" | jq .

if echo "$VALIDATE_RESPONSE" | jq -e '.valid == true' > /dev/null; then
    echo -e "${GREEN}✓ Token válido${NC}"
else
    echo -e "${RED}✗ Token inválido${NC}"
fi

echo ""

# =====================================================
# 4. Renovar Token
# =====================================================

echo -e "${YELLOW}4. Renovar Token${NC}"

REFRESH_RESPONSE=$(curl -s -X POST "$API_URL/auth/refresh" \
  -H "Authorization: Bearer $REFRESH_TOKEN")

echo "$REFRESH_RESPONSE" | jq .

if echo "$REFRESH_RESPONSE" | jq -e '.success == true' > /dev/null; then
    echo -e "${GREEN}✓ Token renovado${NC}"
    NEW_JWT_TOKEN=$(echo "$REFRESH_RESPONSE" | jq -r '.access_token')
    echo "Novo token: ${NEW_JWT_TOKEN:0:20}..."
else
    echo -e "${RED}✗ Falha ao renovar${NC}"
fi

echo ""

# =====================================================
# 5. Testar API Key (se fornecida)
# =====================================================

echo -e "${YELLOW}5. Testar API Key (opcional)${NC}"
read -p "Tem uma API Key para testar? (s/n): " TEST_API_KEY

if [ "$TEST_API_KEY" = "s" ] || [ "$TEST_API_KEY" = "S" ]; then
    read -p "API Key: " API_KEY
    
    API_KEY_RESPONSE=$(curl -s "$API_URL/auth/validate" \
      -H "X-API-Key: $API_KEY")
    
    echo "$API_KEY_RESPONSE" | jq .
    
    if echo "$API_KEY_RESPONSE" | jq -e '.valid == true' > /dev/null; then
        echo -e "${GREEN}✓ API Key válida${NC}"
    else
        echo -e "${RED}✗ API Key inválida${NC}"
    fi
else
    echo "Pulando teste de API Key"
fi

echo ""
echo "====================================="
echo -e "${GREEN}Testes concluídos!${NC}"
echo "====================================="
