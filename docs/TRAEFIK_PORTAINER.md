# 🚀 Deploy com Traefik + Portainer

## Ordem de Deploy

### 1️⃣ Primeiro: Traefik (Reverse Proxy + SSL)

**No Portainer:**

1. `Stacks` → `+ Add stack`
2. **Name:** `traefik`
3. **Build method:** `Repository`
4. **Repository:**
   ```
   URL: https://github.com/OARANHA/28facil-integrity
   Reference: refs/heads/main
   Compose path: traefik-stack.yml
   ```

5. **Environment variables:**
   
   | Name | Value |
   |------|-------|
   | `ACME_EMAIL` | `seu@email.com` |

6. **Deploy the stack**

**Aguarde 30 segundos** e verifique:
```bash
docker ps | grep traefik
curl http://localhost
```

---

### 2️⃣ Depois: API 28Fácil

**No Portainer:**

1. `Stacks` → `+ Add stack`
2. **Name:** `28facil-api`
3. **Build method:** `Repository`
4. **Repository:**
   ```
   URL: https://github.com/OARANHA/28facil-integrity
   Reference: refs/heads/main
   Compose path: docker-compose.yml
   ```

5. **Environment variables:**
   
   | Name | Value |
   |------|-------|
   | `DB_PASSWORD` | `sua_senha_mysql` |
   | `JWT_SECRET` | `sua_chave_jwt_segura` |

6. **Deploy the stack**

---

### 3️⃣ Configurar DNS

**Aponte os domínios para o IP do servidor:**

```
api.28facil.com.br     A    SEU_IP_VPS
traefik.28facil.com.br A    SEU_IP_VPS  (opcional - dashboard)
```

**Aguarde propagação DNS (5-30 minutos)**

Verifique:
```bash
dig api.28facil.com.br +short
# Deve retornar: SEU_IP_VPS
```

---

### 4️⃣ Testar

**Após DNS propagar:**

```bash
# Teste HTTP (vai redirecionar para HTTPS)
curl -I http://api.28facil.com.br

# Teste HTTPS (certificado Let's Encrypt automático)
curl https://api.28facil.com.br
```

**Resposta esperada:**
```json
{
  "status": "success",
  "message": "28Facil API Server is running!",
  "timestamp": "2026-01-19 20:00:00",
  "version": "1.0.0",
  "php_version": "8.2.x",
  "database": {
    "host": "mysql",
    "database": "28facil_api",
    "status": "configured"
  }
}
```

---

## 📊 Dashboard do Traefik (Opcional)

**Acesse:** `https://traefik.28facil.com.br`

**Login:**
- Username: `admin`
- Password: `admin` (MUDE ISSO!)

**Para mudar a senha:**

1. Gere nova senha:
   ```bash
   # No servidor
   docker run --rm httpd:alpine htpasswd -nb admin sua_nova_senha
   ```

2. Copie a saída

3. No Portainer:
   - `Stacks` → `traefik` → `Editor`
   - Encontre a linha: `traefik.http.middlewares.auth.basicauth.users=`
   - Substitua o hash
   - **IMPORTANTE:** Escape os `$` duplicando: `$` → `$$`
   - `Update the stack`

---

## 🔍 Verificar Containers

**Via Portainer:**
- `Containers` → Devem estar `running`:
  - ✅ `traefik`
  - ✅ `28facil-mysql`
  - ✅ `28facil-api`

**Via terminal:**
```bash
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

---

## 🔧 Troubleshooting

### Certificado SSL não gerou

**Causas comuns:**
1. DNS não propagou ainda (aguarde 30 min)
2. Portas 80/443 bloqueadas no firewall
3. Email inválido no ACME_EMAIL

**Verificar:**
```bash
# Ver logs do Traefik
docker logs traefik

# Verificar se porta 80/443 está aberta
sudo ufw status
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

### API não responde

**Verificar:**
```bash
# Logs da API
docker logs 28facil-api

# Testar dentro do container
docker exec -it 28facil-api curl http://localhost/

# Ver roteamento do Traefik
curl http://localhost:8080/api/http/routers
```

### Erro 502 Bad Gateway

**Causa:** API não está na rede `traefik-public`

**Solução:**
```bash
# Verificar redes
docker network ls
docker network inspect traefik-public

# A API deve aparecer nos "Containers"
```

---

## 📋 Resumo

✅ **Stack 1:** `traefik` → Portas 80/443, SSL automático  
✅ **Stack 2:** `28facil-api` → MySQL + API com roteamento  
✅ **DNS:** `api.28facil.com.br` → IP do VPS  
✅ **SSL:** Let's Encrypt automático via Traefik  
✅ **Acesso:** `https://api.28facil.com.br`  

---

**Desenvolvido com ❤️ pela 28Fácil**
