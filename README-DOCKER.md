# 🐳 Deploy com Docker + Traefik - API Server 28Fácil

## 🎯 O que este setup faz?

Cria um servidor API completo para:
- **Gerar** e **validar** API Keys
- **SSL automático** com Let's Encrypt
- **Reverse proxy** com Traefik
- **Banco de dados** MySQL isolado
- **Logs** centralizados

---

## 📦 Arquitetura

```
[🌐 Internet]
       ↓
api.28facil.com.br (DNS)
       ↓
[Traefik :80/:443] ← SSL Let's Encrypt
       ↓
[API Server :80] ← PHP/Apache
       ↓
[MySQL :3306] ← Banco de dados
```

---

## ⚡ Instalação Rápida

### 1️⃣ Clonar o repositório

```bash
cd /root  # ou onde preferir
git clone https://github.com/OARANHA/28facil-integrity.git
cd 28facil-integrity
```

### 2️⃣ Configurar variáveis de ambiente

```bash
cp .env.example .env
nano .env
```

**Edite:**
```bash
DB_DATABASE=28facil_api
DB_USERNAME=28facil
DB_PASSWORD=SUA_SENHA_FORTE_AQUI  # 🔒 TROCAR!

APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:$(openssl rand -base64 32)  # Gerar

JWT_SECRET=$(openssl rand -base64 32)  # Gerar

LETSENCRYPT_EMAIL=seu-email@exemplo.com  # 📧 TROCAR!
```

### 3️⃣ Configurar DNS

Aponte o domínio para o IP do seu VPS:

```
Tipo: A
Nome: api.28facil.com.br
Valor: SEU_IP_VPS
TTL: 300
```

**Aguarde a propagação** (1-5 minutos):
```bash
dig api.28facil.com.br +short
# Deve retornar o IP do seu VPS
```

### 4️⃣ Editar email do Traefik

```bash
nano traefik/traefik.yml
```

Alterar a linha:
```yaml
email: seu-email@exemplo.com  # TROCAR!
```

### 5️⃣ Deploy!

```bash
chmod +x deploy.sh manage.sh
./deploy.sh
```

O script vai:
- ✅ Verificar dependências
- ✅ Criar diretórios
- ✅ Construir imagens Docker
- ✅ Iniciar containers
- ✅ Configurar SSL
- ✅ Criar banco de dados

---

## 🧪 Testar

### Health Check
```bash
curl https://api.28facil.com.br/
```

**Resposta esperada:**
```json
{
  "status": "ok",
  "service": "28Fácil API Server",
  "version": "1.0.0",
  "timestamp": "2026-01-19T17:40:00-03:00"
}
```

### Criar API Key manualmente

```bash
./manage.sh create-key
```

Ou via SQL:
```bash
./manage.sh mysql
```

```sql
USE 28facil_api;

-- Gerar hash (substitua a key)
SET @full_key = '28fc_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6';
SET @key_hash = SHA2(@full_key, 256);
SET @key_prefix = 'SUBSTRING(@full_key, 1, 13)';

INSERT INTO api_keys (
    key_hash,
    key_prefix,
    name,
    permissions,
    rate_limit
) VALUES (
    @key_hash,
    @key_prefix,
    'Minha Primeira Key',
    JSON_ARRAY('read', 'write'),
    1000
);
```

### Validar API Key

```bash
curl -H "X-API-Key: 28fc_sua_key_aqui" \
     https://api.28facil.com.br/auth/validate
```

**Resposta válida:**
```json
{
  "valid": true,
  "user": null,
  "permissions": ["read", "write"],
  "rate_limit": 1000,
  "usage_count": 1
}
```

---

## 🛠️ Gerenciamento

### Ver status
```bash
./manage.sh status
```

### Ver logs
```bash
./manage.sh logs          # Todos
./manage.sh logs-api      # Apenas API
./manage.sh logs-mysql    # Apenas MySQL
```

### Reiniciar
```bash
./manage.sh restart       # Todos
./manage.sh restart-api   # Apenas API
```

### Parar/Iniciar
```bash
./manage.sh stop
./manage.sh start
```

### Entrar nos containers
```bash
./manage.sh shell         # API Server
./manage.sh mysql         # MySQL
```

### Listar API Keys
```bash
./manage.sh list-keys
```

### Backup do banco
```bash
./manage.sh backup-db
```

---

## 🔌 Integração com Integrity

### 1. Criar API Key
```bash
./manage.sh create-key
```

### 2. Usar no código

```php
use AiVoPro\Integrity\IntegrityManager;

$manager = new IntegrityManager(
    'https://api.28facil.com.br',
    '28fc_sua_key_criada'
);

$result = $manager->checkAuthentication();

if ($result->isPassed()) {
    echo "✅ Autenticado!";
} else {
    echo "❌ API Key inválida!";
}
```

---

## 🔒 Segurança

### Firewall (recomendado)

```bash
# Permitir apenas portas necessárias
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP (redirect)
ufw allow 443/tcp   # HTTPS
ufw enable
```

### Mudar senha do MySQL

```bash
nano .env  # Editar DB_PASSWORD
docker-compose down
docker-compose up -d
```

### Rate Limiting

Já configurado nas API Keys (padrão: 1000 req/hora)

---

## 📊 Monitoramento

### Recursos do sistema
```bash
docker stats
```

### Espaço em disco
```bash
df -h
docker system df
```

### Limpar containers/imagens antigas
```bash
docker system prune -a
```

---

## 🐞 Troubleshooting

### API não responde

```bash
# Ver logs
./manage.sh logs-api

# Verificar se container está rodando
docker ps | grep api-server

# Reiniciar
./manage.sh restart-api
```

### SSL não funciona

```bash
# Verificar logs do Traefik
docker logs traefik

# Verificar DNS
dig api.28facil.com.br +short

# Verificar arquivo acme.json
ls -la traefik/acme.json
chmod 600 traefik/acme.json
```

### MySQL não conecta

```bash
# Ver logs
./manage.sh logs-mysql

# Testar conexão
docker-compose exec mysql mysql -u root -p

# Verificar .env
cat .env | grep DB_
```

---

## 🔄 Atualizar

```bash
./manage.sh update
```

Ou manualmente:
```bash
git pull
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

---

## 🗂️ Estrutura de Arquivos

```
28facil-integrity/
├── docker-compose.yml          # Orquestração
├── .env                        # Configurações (CRIAR!)
├── deploy.sh                   # Script de deploy
├── manage.sh                   # Script de gerenciamento
├── docker/
│   └── api-server/
│       ├── Dockerfile
│       ├── apache-config.conf
│       └── app/
│           └── public/
│               └── index.php      # API
├── traefik/
│   ├── traefik.yml             # Config Traefik
│   ├── acme.json               # Certificados SSL
│   └── logs/                   # Logs do Traefik
├── storage/
│   └── api-logs/               # Logs da API
└── server-examples/
    └── database-migration.sql  # Schema do banco
```

---

## ❓ Suporte

Problemas? Verifique:
1. Logs: `./manage.sh logs`
2. Status: `./manage.sh status`
3. DNS configurado corretamente
4. Portas 80/443 abertas no firewall
5. .env configurado

---

**Feito com ❤️ pela 28Fácil**
