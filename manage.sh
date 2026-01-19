#!/bin/bash

# =====================================================
# SCRIPT DE GERENCIAMENTO - API Server 28Fácil
# =====================================================

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

function show_help() {
    echo "Uso: ./manage.sh [comando]"
    echo ""
    echo "Comandos disponíveis:"
    echo "  status          - Mostrar status dos containers"
    echo "  logs            - Ver logs em tempo real"
    echo "  logs-api        - Ver apenas logs da API"
    echo "  logs-mysql      - Ver apenas logs do MySQL"
    echo "  restart         - Reiniciar todos os containers"
    echo "  restart-api     - Reiniciar apenas a API"
    echo "  stop            - Parar todos os containers"
    echo "  start           - Iniciar todos os containers"
    echo "  shell           - Entrar no container da API"
    echo "  mysql           - Entrar no MySQL"
    echo "  create-key      - Criar nova API Key via CLI"
    echo "  list-keys       - Listar API Keys"
    echo "  backup-db       - Fazer backup do banco"
    echo "  update          - Atualizar e reconstruir"
    echo ""
}

case "$1" in
    status)
        echo -e "${GREEN}Status dos Containers:${NC}"
        docker-compose ps
        echo ""
        echo -e "${GREEN}Recursos utilizados:${NC}"
        docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}" \
            28facil-api-server 28facil-mysql traefik 2>/dev/null || true
        ;;
    
    logs)
        echo -e "${GREEN}Logs em tempo real (Ctrl+C para sair):${NC}"
        docker-compose logs -f --tail=100
        ;;
    
    logs-api)
        echo -e "${GREEN}Logs da API:${NC}"
        docker-compose logs -f --tail=100 api-server
        ;;
    
    logs-mysql)
        echo -e "${GREEN}Logs do MySQL:${NC}"
        docker-compose logs -f --tail=100 mysql
        ;;
    
    restart)
        echo -e "${YELLOW}Reiniciando todos os containers...${NC}"
        docker-compose restart
        echo -e "${GREEN}✓ Containers reiniciados${NC}"
        ;;
    
    restart-api)
        echo -e "${YELLOW}Reiniciando API Server...${NC}"
        docker-compose restart api-server
        echo -e "${GREEN}✓ API Server reiniciada${NC}"
        ;;
    
    stop)
        echo -e "${YELLOW}Parando todos os containers...${NC}"
        docker-compose stop
        echo -e "${GREEN}✓ Containers parados${NC}"
        ;;
    
    start)
        echo -e "${GREEN}Iniciando containers...${NC}"
        docker-compose start
        echo -e "${GREEN}✓ Containers iniciados${NC}"
        docker-compose ps
        ;;
    
    shell)
        echo -e "${GREEN}Entrando no container da API...${NC}"
        docker-compose exec api-server bash
        ;;
    
    mysql)
        echo -e "${GREEN}Conectando ao MySQL...${NC}"
        docker-compose exec mysql mysql -u root -p
        ;;
    
    create-key)
        echo -e "${GREEN}=== Criar Nova API Key ===${NC}"
        read -p "Nome da Key: " key_name
        read -p "User ID (ou Enter para NULL): " user_id
        
        if [ -z "$user_id" ]; then
            user_id="NULL"
        fi
        
        # Gerar key
        random_key=$(openssl rand -hex 24)
        full_key="28fc_${random_key}"
        key_hash=$(echo -n "$full_key" | sha256sum | awk '{print $1}')
        key_prefix="28fc_${random_key:0:8}"
        
        # Inserir no banco
        docker-compose exec -T mysql mysql -u root -p${DB_PASSWORD:-senha_forte_aqui} 28facil_api <<EOF
INSERT INTO api_keys (
    key_hash, 
    key_prefix, 
    user_id, 
    name, 
    permissions, 
    rate_limit
) VALUES (
    '$key_hash',
    '$key_prefix',
    $user_id,
    '$key_name',
    JSON_ARRAY('read', 'write'),
    1000
);
EOF
        
        echo ""
        echo -e "${GREEN}✅ API Key criada com sucesso!${NC}"
        echo ""
        echo -e "${YELLOW}GUARDE ESTA KEY EM LOCAL SEGURO:${NC}"
        echo -e "${GREEN}$full_key${NC}"
        echo ""
        echo -e "Prefixo (identificação): ${key_prefix}***"
        ;;
    
    list-keys)
        echo -e "${GREEN}=== API Keys Cadastradas ===${NC}"
        docker-compose exec mysql mysql -u root -p${DB_PASSWORD:-senha_forte_aqui} \
            28facil_api -e "
            SELECT 
                id,
                key_prefix,
                name,
                is_active,
                usage_count,
                DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as criada_em,
                DATE_FORMAT(last_used_at, '%d/%m/%Y %H:%i') as ultimo_uso
            FROM api_keys 
            ORDER BY created_at DESC;
        "
        ;;
    
    backup-db)
        BACKUP_DIR="./backups"
        mkdir -p $BACKUP_DIR
        BACKUP_FILE="$BACKUP_DIR/28facil_api_$(date +%Y%m%d_%H%M%S).sql"
        
        echo -e "${GREEN}Fazendo backup do banco de dados...${NC}"
        docker-compose exec -T mysql mysqldump -u root -p${DB_PASSWORD:-senha_forte_aqui} \
            28facil_api > $BACKUP_FILE
        
        echo -e "${GREEN}✓ Backup salvo em: $BACKUP_FILE${NC}"
        ls -lh $BACKUP_FILE
        ;;
    
    update)
        echo -e "${YELLOW}Atualizando sistema...${NC}"
        git pull
        docker-compose down
        docker-compose build --no-cache
        docker-compose up -d
        echo -e "${GREEN}✓ Sistema atualizado!${NC}"
        ;;
    
    *)
        show_help
        exit 1
        ;;
esac
