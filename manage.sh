#!/bin/bash

# =====================================================
# SCRIPT DE GERENCIAMENTO - API Server 28Fácil
# =====================================================

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Detectar qual comando do Docker Compose usar
if docker compose version &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
else
    DOCKER_COMPOSE="docker-compose"
fi

DOCKER_COMPOSE_FILE="docker/docker-compose.yml"

# Função: mostrar ajuda
show_help() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  Gerenciador - API Server 28Fácil${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""
    echo "Uso: ./manage.sh [comando]"
    echo ""
    echo "Comandos disponíveis:"
    echo ""
    echo "  status       - Ver status dos containers"
    echo "  logs         - Ver logs em tempo real"
    echo "  logs-api     - Ver apenas logs da API"
    echo "  logs-mysql   - Ver apenas logs do MySQL"
    echo "  restart      - Reiniciar todos os containers"
    echo "  restart-api  - Reiniciar apenas a API"
    echo "  stop         - Parar todos os containers"
    echo "  start        - Iniciar containers parados"
    echo "  rebuild      - Rebuildar e reiniciar"
    echo "  shell        - Abrir shell no container da API"
    echo "  mysql        - Abrir MySQL CLI"
    echo "  health       - Testar health check da API"
    echo "  stats        - Ver estatísticas de uso"
    echo "  clean        - Limpar containers e volumes (CUIDADO!)"
    echo ""
}

# Verificar se comando foi fornecido
if [ -z "$1" ]; then
    show_help
    exit 1
fi

COMMAND=$1

case $COMMAND in
    status)
        echo -e "${BLUE}📊 Status dos containers:${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE ps
        ;;
    
    logs)
        echo -e "${BLUE}📋 Logs em tempo real (Ctrl+C para sair):${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE logs -f
        ;;
    
    logs-api)
        echo -e "${BLUE}📋 Logs da API:${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE logs -f api-server
        ;;
    
    logs-mysql)
        echo -e "${BLUE}📋 Logs do MySQL:${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE logs -f mysql
        ;;
    
    restart)
        echo -e "${YELLOW}🔄 Reiniciando todos os containers...${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE restart
        echo -e "${GREEN}✅ Containers reiniciados${NC}"
        ;;
    
    restart-api)
        echo -e "${YELLOW}🔄 Reiniciando API Server...${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE restart api-server
        echo -e "${GREEN}✅ API reiniciada${NC}"
        ;;
    
    stop)
        echo -e "${YELLOW}🛑 Parando containers...${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE stop
        echo -e "${GREEN}✅ Containers parados${NC}"
        ;;
    
    start)
        echo -e "${GREEN}🚀 Iniciando containers...${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE start
        echo -e "${GREEN}✅ Containers iniciados${NC}"
        ;;
    
    rebuild)
        echo -e "${YELLOW}🔨 Rebuilding e reiniciando...${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE down
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE build --no-cache
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE up -d
        echo -e "${GREEN}✅ Rebuild concluído${NC}"
        ;;
    
    shell)
        echo -e "${BLUE}💻 Abrindo shell no container da API...${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE exec api-server /bin/bash
        ;;
    
    mysql)
        echo -e "${BLUE}🗄️  Abrindo MySQL CLI...${NC}"
        echo -e "${YELLOW}Senha padrão: senha (definida no .env)${NC}"
        $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE exec mysql mysql -u root -p
        ;;
    
    health)
        echo -e "${BLUE}🏥 Testando health check...${NC}"
        echo ""
        
        # Testar localhost
        echo "Testando http://localhost:8080/"
        curl -s http://localhost:8080/ | jq . || echo "Erro ao conectar"
        
        echo ""
        echo "Testando http://localhost:8080/auth/validate (deve dar 401)"
        curl -s http://localhost:8080/auth/validate | jq .
        ;;
    
    stats)
        echo -e "${BLUE}📈 Estatísticas de uso:${NC}"
        docker stats --no-stream $(docker ps --filter "name=28facil" --format "{{.Names}}")
        ;;
    
    clean)
        echo -e "${RED}⚠️  ATENÇÃO: Isso vai remover TODOS os containers e volumes!${NC}"
        echo -e "${RED}Todos os dados serão PERDIDOS!${NC}"
        read -p "Tem certeza? (digite 'sim' para confirmar): " confirm
        
        if [ "$confirm" = "sim" ]; then
            echo -e "${YELLOW}🗑️  Removendo containers e volumes...${NC}"
            $DOCKER_COMPOSE -f $DOCKER_COMPOSE_FILE down -v
            echo -e "${GREEN}✅ Limpeza concluída${NC}"
        else
            echo -e "${GREEN}✅ Operação cancelada${NC}"
        fi
        ;;
    
    *)
        echo -e "${RED}❌ Comando desconhecido: $COMMAND${NC}"
        show_help
        exit 1
        ;;
esac
