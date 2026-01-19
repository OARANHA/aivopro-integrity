# 🐳 Instalação via Portainer

## Por que usar Portainer?

- ✅ **Interface gráfica** - Sem precisar decorar comandos Docker
- ✅ **Fácil de usar** - Arrastar e soltar, clicks
- ✅ **Gerenciar tudo** - Containers, volumes, networks, images
- ✅ **Logs em tempo real** - Ver logs de forma visual
- ✅ **Terminal integrado** - Abrir shell nos containers

---

## 📋 Passo a Passo

### 1. Instalar Portainer

```bash
cd ~/28facil-integrity
chmod +x setup-portainer.sh
./setup-portainer.sh
```

**Saída esperada:**
```
🐳 Instalando Portainer...
✓ Docker já está instalado
📦 Criando volume para dados do Portainer...
🚀 Iniciando Portainer...

========================================
✅ Portainer instalado com sucesso!
========================================

📍 Acesse o Portainer:
   🌐 HTTPS: https://SEU_IP:9443
   🌐 HTTP:  http://SEU_IP:9000
```

---

### 2. Primeiro Acesso ao Portainer

1. **Abra o navegador:**
   ```
   http://SEU_IP:9000
   ```

2. **Crie usuário admin:**
   - Username: `admin`
   - Password: (crie uma senha forte)
   - Confirme a senha
   - Clique em "Create user"

3. **Conectar ao Docker:**
   - Selecione: **"Docker - Manage the local Docker environment"**
   - Clique em "Connect"

4. **Dashboard:**
   - Você verá o dashboard com estatísticas
   - Clique em "local" para gerenciar

---

### 3. Deploy da Stack 28Fácil

#### Opção A: Stack Completa (com Traefik + SSL)

1. **No Portainer:**
   - Menu lateral: `Stacks`
   - Botão: `+ Add stack`

2. **Configurar:**
   - Name: `28facil-api`
   - Build method: `Web editor`

3. **Colar o conteúdo:**
   - Abra o arquivo: `docker/portainer-stack.yml`
   - Copie TODO o conteúdo
   - Cole no editor do Portainer

4. **Variáveis de ambiente:**
   Clique em "+ Add an environment variable" para cada:
   
   | Name | Value |
   |------|-------|
   | `DOMAIN` | `28facil.com.br` |
   | `ACME_EMAIL` | `seu@email.com` |
   | `DB_PASSWORD` | `senha_forte_123` |
   | `DB_DATABASE` | `28facil_api` |
   | `DB_USERNAME` | `28facil` |
   | `JWT_SECRET` | `sua_chave_jwt_aqui` |

5. **Deploy:**
   - Clique em `Deploy the stack`
   - Aguarde 30 segundos

#### Opção B: Stack Simples (sem SSL, porta 8080)

1. **Use o arquivo:** `docker/portainer-simple-stack.yml`
2. **Deploy** direto (não precisa de variáveis)
3. **Acesse:** `http://SEU_IP:8080`

---

### 4. Verificar se funcionou

1. **No Portainer:**
   - Menu: `Containers`
   - Você deve ver:
     - ✅ `28facil-mysql` (running)
     - ✅ `28facil-api` (running)
     - ✅ `28facil-traefik` (running) - se usou stack completa

2. **Testar API:**
   ```bash
   # Stack simples
   curl http://SEU_IP:8080/
   
   # Stack completa
   curl https://api.28facil.com.br/
   ```

---

### 5. Gerenciar via Portainer

#### Ver Logs
1. Menu: `Containers`
2. Clique no container (ex: `28facil-api`)
3. Aba: `Logs`
4. Ative: `Auto-refresh logs`

#### Abrir Terminal
1. Menu: `Containers`
2. Clique no container
3. Aba: `Console`
4. Clique: `Connect`
5. Execute comandos:
   ```bash
   php -v
   ls -la /var/www/html
   ```

#### Reiniciar Container
1. Menu: `Containers`
2. Selecione o container
3. Botão: `Restart`

#### Ver Estatísticas
1. Menu: `Containers`
2. Clique no container
3. Aba: `Stats`
4. Veja: CPU, Memória, Network, Disk

---

### 6. Criar Tabelas no MySQL

1. **Abrir terminal do MySQL no Portainer:**
   - Containers > `28facil-mysql` > Console > Connect

2. **Executar:**
   ```bash
   mysql -u root -p
   # Senha: a que você definiu em DB_PASSWORD
   ```

3. **Criar tabelas:**
   ```sql
   USE 28facil_api;
   
   -- Copiar e colar as migrations:
   -- server-examples/database-migration.sql
   -- server-examples/database-migration-users.sql
   ```

**OU** usando o terminal do host:

```bash
# Upload do arquivo SQL
cat server-examples/database-migration.sql | \
  docker exec -i 28facil-mysql mysql -u root -p28facil_api -psenha123
```

---

### 7. Criar Primeira API Key

1. **No terminal do MySQL (via Portainer):**
   ```sql
   USE 28facil_api;
   
   INSERT INTO api_keys (
       user_id,
       name,
       key_hash,
       permissions,
       is_active
   ) VALUES (
       NULL,
       'Chave de Teste',
       SHA2('28fc_test_key_123', 256),
       JSON_ARRAY('read', 'write'),
       1
   );
   ```

2. **Testar:**
   ```bash
   curl -H "X-API-Key: 28fc_test_key_123" \
        http://SEU_IP:8080/auth/validate
   ```

---

## 🎨 Interface do Portainer

### Dashboard Principal
- **Containers:** Total, Running, Stopped
- **Images:** Imagens Docker disponíveis
- **Volumes:** Armazenamento persistente
- **Networks:** Redes Docker

### Menu Lateral
- `Home` - Dashboard
- `Stacks` - Gerenciar stacks (compose)
- `Containers` - Lista de containers
- `Images` - Imagens Docker
- `Networks` - Redes
- `Volumes` - Volumes
- `Events` - Log de eventos
- `Settings` - Configurações

---

## 🔧 Troubleshooting

### Container não inicia
1. Ver logs: Containers > Nome > Logs
2. Verificar variáveis de ambiente
3. Verificar se portas estão em uso

### Não consigo acessar a API
1. Verificar se container está "running"
2. Ver logs do Traefik (se usando stack completa)
3. Testar: `curl http://localhost:8080` de dentro do container

### Esqueci senha do Portainer
```bash
# Resetar senha
docker stop portainer
docker rm portainer
./setup-portainer.sh
```

---

## 📚 Recursos

- [Portainer Docs](https://docs.portainer.io/)
- [Docker Compose Docs](https://docs.docker.com/compose/)
- [Traefik Docs](https://doc.traefik.io/traefik/)

---

**Desenvolvido com ❤️ pela 28Fácil**
