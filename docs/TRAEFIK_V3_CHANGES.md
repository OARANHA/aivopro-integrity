# 🆕 Traefik 3.6 - Mudanças e Melhorias

## Por que atualizar para v3.6?

### 📅 Ciclo de Suporte

| Versão | Lançamento | Suporte Ativo | Suporte Segurança |
|--------|-------------|---------------|--------------------|
| **3.6** | Nov 2025 | ✅ Até Nov 2026 | ✅ Até Nov 2026 |
| 2.11 | Fev 2024 | ❌ Terminou Abr 2025 | ⚠️ **Termina 1 Fev 2026** |

⚠️ **Traefik 2.11 perde suporte de segurança em 13 dias!**

---

## ✨ Novidades do Traefik v3

### 1. Performance Melhorada
- 🚀 Até 30% mais rápido no roteamento
- 📊 Menor uso de memória
- ⚡ Hot reload mais eficiente

### 2. Segurança Aprimorada
- 🔒 Melhor suporte para TLS 1.3
- 🔐 Headers de segurança padrão mais rigorosos
- 🛡️ Rate limiting mais flexível

### 3. Monitoramento
- 📊 Métricas mais detalhadas
- 📝 Logs estruturados (JSON)
- 🔍 Tracing distribuído melhorado

### 4. Kubernetes Native
- ☸️ Melhor integração com K8s
- 📦 Gateway API support
- 🔄 Auto-scaling otimizado

---

## 🔄 Mudanças Breaking (v2 → v3)

### 1. Configuração de Entrypoints

**Antes (v2):**
```yaml
--entrypoints.web.http.redirections.entryPoint.to=websecure
```

**Agora (v3):**
```yaml
--entrypoints.web.http.redirections.entrypoint.to=websecure
```

💡 **Nota:** `entryPoint` → `entrypoint` (minúsculo)

### 2. Middleware de Autenticação

**Sem mudanças significativas** - Nossa config já é compatível!

### 3. Let's Encrypt

**Sem mudanças** - Funciona igual!

### 4. Docker Labels

**Sem mudanças** - Todos os labels continuam iguais!

---

## ✅ Nossa Stack já está compatível!

Já atualizamos tudo para v3.6:

- ✅ `traefik-stack.yml` - Versão 3.6
- ✅ `docker-compose.yml` - Labels compatíveis
- ✅ Redirects HTTP → HTTPS
- ✅ Let's Encrypt configurado
- ✅ Dashboard funcional

---

## 🚀 Como Atualizar

### No Portainer:

1. **Remover stack antiga:**
   - `Stacks` → `traefik` → `Remove`

2. **Criar nova stack:**
   - `+ Add stack`
   - Name: `traefik`
   - Build method: `Repository`
   - URL: `https://github.com/OARANHA/28facil-integrity`
   - Reference: `refs/heads/main`
   - Compose path: `traefik-stack.yml`

3. **Deploy!**

### Verificar:

```bash
# Ver versão
docker logs traefik | grep "Traefik version"
# Deve aparecer: Traefik version 3.6.x

# Testar dashboard
curl http://158.220.97.145:8088/dashboard/
```

---

## 📚 Referências

- [Traefik v3 Migration Guide](https://doc.traefik.io/traefik/migration/v2-to-v3/)
- [Traefik 3.6 Release Notes](https://github.com/traefik/traefik/releases/tag/v3.6.0)
- [Traefik Releases](https://doc.traefik.io/traefik/deprecation/releases/)

---

**Atualizado para Traefik 3.6 em 19 Jan 2026** 🎉
