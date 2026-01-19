#!/bin/bash

# =====================================================
# INSTALAR PORTAINER - Gerenciador Docker via Web
# =====================================================

set -e

echo "🐳 Instalando Portainer..."
echo ""

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    echo -e "${YELLOW}Docker não encontrado. Instalando...${NC}"
    
    # Atualizar sistema
    apt-get update
    apt-get install -y ca-certificates curl gnupg lsb-release
    
    # Adicionar chave GPG do Docker
    mkdir -p /etc/apt/keyrings
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
    
    # Adicionar repositório
    echo \
      "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
      $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
    
    # Instalar Docker
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
    
    echo -e "${GREEN}✓ Docker instalado com sucesso!${NC}"
else
    echo -e "${GREEN}✓ Docker já está instalado${NC}"
fi

# Criar volume para dados do Portainer
echo ""
echo "📦 Criando volume para dados do Portainer..."
docker volume create portainer_data

# Parar Portainer antigo se existir
echo "🛑 Removendo instalação antiga do Portainer (se existir)..."
docker stop portainer 2>/dev/null || true
docker rm portainer 2>/dev/null || true

# Iniciar Portainer
echo ""
echo "🚀 Iniciando Portainer..."
docker run -d \
  --name portainer \
  --restart always \
  -p 9443:9443 \
  -p 9000:9000 \
  -p 8000:8000 \
  -v /var/run/docker.sock:/var/run/docker.sock \
  -v portainer_data:/data \
  portainer/portainer-ce:latest

echo ""
echo "⏳ Aguardando Portainer inicializar..."
sleep 5

# Verificar status
if docker ps | grep -q portainer; then
    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}✅ Portainer instalado com sucesso!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "${BLUE}📍 Acesse o Portainer:${NC}"
    echo ""
    echo "   🌐 HTTPS: https://$(hostname -I | awk '{print $1}'):9443"
    echo "   🌐 HTTP:  http://$(hostname -I | awk '{print $1}'):9000"
    echo ""
    echo -e "${YELLOW}⚠️  PRIMEIRA VEZ:${NC}"
    echo "   1. Crie um usuário admin (username + senha)"
    echo "   2. Escolha 'Docker' como ambiente"
    echo "   3. Você verá o dashboard com seus containers"
    echo ""
    echo -e "${BLUE}📋 Próximos passos:${NC}"
    echo "   1. Acesse o Portainer no navegador"
    echo "   2. Vá em 'Stacks' > 'Add stack'"
    echo "   3. Cole o conteúdo do arquivo: docker/portainer-stack.yml"
    echo "   4. Clique em 'Deploy the stack'"
    echo ""
    echo -e "${YELLOW}💡 Dica:${NC}"
    echo "   O arquivo docker/portainer-stack.yml já está pronto!"
    echo "   Você pode editá-lo antes de fazer deploy pelo Portainer"
    echo ""
else
    echo -e "${RED}❌ Erro ao iniciar Portainer${NC}"
    echo "Logs:"
    docker logs portainer
    exit 1
fi
