#!/bin/bash

# =====================================================
# SCRIPT DE DEPLOY - API Server 28Fácil
# =====================================================

set -e  # Parar em caso de erro

echo "🚀 Iniciando deploy do API Server 28Fácil..."

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker não encontrado. Instale primeiro: https://docs.docker.com/engine/install/${NC}"
    exit 1
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo -e "${RED}❌ Docker Compose não encontrado${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Docker e Docker Compose encontrados${NC}"

# Verificar se .env existe
if [ ! -f .env ]; then
    echo -e "${YELLOW}⚠️  Arquivo .env não encontrado. Criando a partir do .env.example...${NC}"
    cp .env.example .env
    echo -e "${YELLOW}✏️  ATENÇÃO: Edite o arquivo .env com suas configurações antes de continuar!${NC}"
    echo -e "${YELLOW}Execute: nano .env${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Arquivo .env encontrado${NC}"

# Criar arquivo acme.json para Let's Encrypt
if [ ! -f traefik/acme.json ]; then
    echo -e "${YELLOW}⚠️  Criando arquivo acme.json para certificados SSL...${NC}"
    mkdir -p traefik
    touch traefik/acme.json
    chmod 600 traefik/acme.json
fi

# Criar diretórios necessários
echo "📁 Criando diretórios..."
mkdir -p traefik/logs
mkdir -p storage/api-logs
mkdir -p api-server/public

# Parar containers antigos se existirem
echo "🛑 Parando containers antigos..."
docker-compose down 2>/dev/null || true

# Construir imagens
echo "🔨 Construindo imagens Docker..."
docker-compose build --no-cache

# Iniciar containers
echo "🚀 Iniciando containers..."
docker-compose up -d

# Aguardar MySQL inicializar
echo "⏳ Aguardando MySQL inicializar..."
sleep 10

# Verificar status
echo "🔍 Verificando status dos containers..."
docker-compose ps

# Testar conexão
echo ""
echo "🧪 Testando API..."
sleep 5

if curl -s -o /dev/null -w "%{http_code}" http://localhost/health | grep -q "200\|404"; then
    echo -e "${GREEN}✅ API Server está respondendo!${NC}"
else
    echo -e "${RED}❌ API Server não está respondendo. Verifique os logs:${NC}"
    echo "docker-compose logs api-server"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}✅ Deploy concluído!${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo "Próximos passos:"
echo ""
echo "1. Configure o DNS:"
echo "   api.28facil.com.br -> IP do seu VPS"
echo ""
echo "2. Teste o endpoint:"
echo "   curl https://api.28facil.com.br/"
echo ""
echo "3. Criar primeira API Key:"
echo "   docker-compose exec mysql mysql -u root -p"
echo "   USE 28facil_api;"
echo "   -- Ver exemplos em docs/API_KEY_SYSTEM.md"
echo ""
echo "4. Ver logs:"
echo "   docker-compose logs -f api-server"
echo ""
echo "5. Gerenciar:"
echo "   ./manage.sh status|logs|restart|stop"
echo ""
