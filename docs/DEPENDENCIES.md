# 📦 Dependências do 28Fácil Integrity

## Overview

Este pacote foi inspirado no `heyaikeedo/integrity` mas com melhorias e adaptações para o ecossistema 28Fácil.

---

## Comparação de Dependências

### heyaikeedo/integrity (original)

```json
{
  "require": {
    "php": "^8.2",
    "firebase/php-jwt": "^6.10",
    "iziphp/router": "^1.2",
    "psr/http-factory": "^1.0",
    "symfony/cache": "^7.0",
    "symfony/http-client": "^7.0"
  }
}
```

### 28facil/integrity (nosso)

```json
{
  "require": {
    "php": "^8.1|^8.2",
    "firebase/php-jwt": "^6.10",
    "guzzlehttp/guzzle": "^7.8",
    "iziphp/router": "^1.2",
    "psr/http-factory": "^1.0",
    "symfony/cache": "^6.0|^7.0",
    "symfony/http-client": "^6.0|^7.0"
  }
}
```

---

## 🔍 O que cada dependência faz?

### 1. **firebase/php-jwt** (^6.10)
- **Propósito:** Criação e validação de tokens JWT
- **Usado em:** Autenticação de usuários com tokens temporários
- **Alternativa ao:** API Keys permanentes

**Exemplo:**
```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$token = JWT::encode($payload, $secretKey, 'HS256');
$decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
```

---

### 2. **guzzlehttp/guzzle** (^7.8)
- **Propósito:** Cliente HTTP para fazer requisições
- **Usado em:** Health checks, chamadas à API
- **Por que mantivemos:** Mais popular e maduro que Symfony HttpClient

**Exemplo:**
```php
use GuzzleHttp\Client;

$client = new Client();
$response = $client->get('https://api.28facil.com.br/health');
```

---

### 3. **iziphp/router** (^1.2)
- **Propósito:** Roteamento de requisições HTTP
- **Usado em:** Sistema de rotas da API
- **Compatível com:** PSR-7, PSR-15 (middlewares)

**O que é?**
- Roteador leve e rápido
- Suporte a middlewares
- Cache de rotas compiladas

**Quando usar:**
```php
use Izi\Router\Router;

$router = new Router();
$router->get('/health', [HealthController::class, 'check']);
$router->post('/auth/login', [AuthController::class, 'login']);
```

---

### 4. **psr/http-factory** (^1.0)
- **Propósito:** Interfaces para criar objetos PSR-7 (Request, Response)
- **Usado em:** Criação de requests/responses padronizados
- **Padrão:** PSR-17 (HTTP Factories)

**O que é PSR-17?**
Define interfaces para criar objetos HTTP:
- `RequestFactoryInterface`
- `ResponseFactoryInterface`
- `StreamFactoryInterface`
- `UriFactoryInterface`

**Exemplo:**
```php
use Psr\Http\Message\RequestFactoryInterface;

$request = $requestFactory->createRequest('GET', 'https://api.exemplo.com');
```

---

### 5. **symfony/cache** (^6.0|^7.0)
- **Propósito:** Sistema de cache (PSR-6 e PSR-16)
- **Usado em:** Cache de health checks, tokens, configurações
- **Adapters:** Filesystem, Redis, Memcached, APCu

**Exemplo:**
```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$cache = new FilesystemAdapter('28facil_integrity', 300);
$cachedData = $cache->get('health_status', function() {
    return performHealthCheck();
});
```

---

### 6. **symfony/http-client** (^6.0|^7.0)
- **Propósito:** Cliente HTTP alternativo ao Guzzle
- **Usado em:** Requisições HTTP assíncronas
- **Vantagens:** Mais leve, suporte nativo a HTTP/2

**Por que temos Guzzle E Symfony HttpClient?**
- **Guzzle:** Mais conhecido, usado em projetos legados
- **Symfony HttpClient:** Mais moderno, melhor para async
- **Flexibilidade:** Desenvolvedor escolhe qual usar

**Exemplo:**
```php
use Symfony\Component\HttpClient\HttpClient;

$client = HttpClient::create();
$response = $client->request('GET', 'https://api.28facil.com.br');
```

---

## 🆚 Guzzle vs Symfony HttpClient

| Característica | Guzzle | Symfony HttpClient |
|----------------|--------|-------------------|
| **Popularidade** | ⭐⭐⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Performance** | Boa | Excelente |
| **HTTP/2** | ❌ | ✅ |
| **Async** | Promises | Async nativo |
| **Tamanho** | Maior | Menor |
| **Maturidade** | Muito maduro | Moderno |
| **PSR-18** | ✅ | ✅ |

---

## 📚 Standards (PSRs) Utilizados

### PSR-6: Caching Interface
- **Pacote:** `symfony/cache`
- **Interface:** `Psr\Cache\CacheItemPoolInterface`

### PSR-16: Simple Cache
- **Pacote:** `symfony/cache`
- **Interface:** `Psr\SimpleCache\CacheInterface`

### PSR-17: HTTP Factories
- **Pacote:** `psr/http-factory`
- **Usado para:** Criar objetos HTTP padronizados

### PSR-18: HTTP Client
- **Pacote:** `guzzlehttp/guzzle` ou `symfony/http-client`
- **Interface:** `Psr\Http\Client\ClientInterface`

---

## 🔧 Por que PHP 8.1+ ao invés de 8.2+?

**Decisão:** Suportamos `^8.1|^8.2` ao invés de apenas `^8.2`

**Motivo:**
- PHP 8.2 foi lançado em dezembro de 2022
- Muitos projetos ainda usam 8.1 (LTS até novembro de 2024)
- **Compatibilidade maior** com projetos existentes

**Se seu servidor tem PHP 8.1:**
```bash
php -v
# PHP 8.1.x
composer require 28facil/integrity  # ✅ Funciona!
```

**Se seu servidor tem PHP 8.2+:**
```bash
php -v
# PHP 8.2.x ou 8.3.x
composer require 28facil/integrity  # ✅ Funciona também!
```

---

## 📦 Instalação Completa

```bash
composer require 28facil/integrity
```

Isso vai instalar automaticamente:
- ✅ firebase/php-jwt
- ✅ guzzlehttp/guzzle
- ✅ iziphp/router
- ✅ psr/http-factory
- ✅ symfony/cache
- ✅ symfony/http-client
- ✅ Todas as dependências transitivas

---

## 🔍 Verificar Dependências Instaladas

```bash
composer show --tree 28facil/integrity
```

**Saída esperada:**
```
28facil/integrity 1.0.0
├── php ^8.1|^8.2
├── firebase/php-jwt ^6.10
│   └── php ^7.4 || ^8.0
├── guzzlehttp/guzzle ^7.8
│   ├── guzzlehttp/promises ^2.0
│   ├── guzzlehttp/psr7 ^2.6.2
│   └── psr/http-client ^1.0
├── iziphp/router ^1.2
│   ├── nikic/php-parser ^5.0
│   └── psr/container ^2.0
├── psr/http-factory ^1.0
├── symfony/cache ^6.0|^7.0
│   ├── psr/cache ^3.0
│   └── symfony/cache-contracts ^2.5|^3
└── symfony/http-client ^6.0|^7.0
    ├── psr/log ^1|^2|^3
    └── symfony/http-client-contracts ^3.4
```

---

## 🚀 Próximos Passos

1. **Atualizar dependências:**
```bash
composer update 28facil/integrity
```

2. **Verificar compatibilidade:**
```bash
composer check-platform-reqs
```

3. **Limpar cache:**
```bash
composer clear-cache
```

---

## 🆘 Troubleshooting

### Erro: "PHP version mismatch"
```bash
# Verificar versão do PHP
php -v

# Atualizar composer.json para aceitar sua versão
# Ou atualizar PHP para 8.1+
```

### Erro: "Package not found"
```bash
# Limpar cache do composer
composer clear-cache

# Atualizar composer
composer self-update

# Tentar novamente
composer require 28facil/integrity
```

### Conflito de versões
```bash
# Ver árvore de dependências
composer show --tree

# Resolver conflitos
composer update --with-all-dependencies
```

---

**Desenvolvido com ❤️ pela 28Fácil**
