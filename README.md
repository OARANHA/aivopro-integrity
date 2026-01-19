# 28Fácil Integrity Manager

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-blue.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**Sistema de Verificação de Integridade para o Ecossistema 28Fácil**

Pacote PHP para monitoramento e validação da saúde da API [api.28facil.com.br](https://api.28facil.com.br), incluindo health checks, validação de credenciais e verificação de dependências essenciais.

---

## 📦 Instalação

Instale via Composer:

```bash
composer require 28facil/integrity
```

---

## 🚀 Uso Básico

### Health Check Simples

```php
use AiVoPro\Integrity\IntegrityManager;

$manager = new IntegrityManager('https://api.28facil.com.br');

// Verificação rápida
if ($manager->isHealthy()) {
    echo "API está saudável!";
} else {
    echo "API com problemas: " . $manager->getLastError();
}
```

### Auditoria Completa

```php
use AiVoPro\Integrity\IntegrityManager;

$manager = new IntegrityManager(
    apiUrl: 'https://api.28facil.com.br',
    apiKey: 'sua-api-key-aqui'
);

// Executa todas as checagens
$report = $manager->audit();

echo "Status Geral: " . $report->getStatus() . "\n";
echo "Versão da API: " . $report->getVersion() . "\n";
echo "Tempo de Resposta: " . $report->getResponseTime() . "ms\n";

// Verificar checagens individuais
foreach ($report->getChecks() as $check) {
    echo sprintf(
        "[%s] %s: %s\n",
        $check->isPassed() ? '✓' : '✗',
        $check->getName(),
        $check->getMessage()
    );
}
```

### Checagens Específicas

```php
// Verificar apenas a versão
$version = $manager->checkVersion();
echo "Versão atual: {$version->version}\n";

// Validar credenciais
$auth = $manager->checkAuthentication('sua-api-key');
if ($auth->isValid()) {
    echo "Credenciais válidas!\n";
}

// Verificar dependências
$deps = $manager->checkDependencies();
foreach ($deps->getServices() as $service => $status) {
    echo "{$service}: " . ($status ? 'OK' : 'FALHOU') . "\n";
}
```

---

## 🔍 Checagens Disponíveis

| Checagem | Descrição |
|----------|----------|
| **Health Check** | Verifica se a API está respondendo |
| **Version Check** | Obtém e valida a versão da API |
| **Authentication** | Valida API keys e tokens |
| **Dependencies** | Verifica serviços essenciais (DB, Redis, etc) |
| **Performance** | Mede tempo de resposta e latência |
| **Endpoints** | Testa endpoints críticos |

---

## ⚙️ Configuração Avançada

### Com Cache

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$cache = new FilesystemAdapter('28facil_integrity', 300);
$manager = new IntegrityManager(
    apiUrl: 'https://api.28facil.com.br',
    cache: $cache
);
```

### Timeout Customizado

```php
$manager = new IntegrityManager(
    apiUrl: 'https://api.28facil.com.br',
    timeout: 10.0,  // segundos
    retries: 3
);
```

### Modo Silencioso (sem exceções)

```php
$manager = new IntegrityManager(
    apiUrl: 'https://api.28facil.com.br',
    throwExceptions: false
);

$report = $manager->audit();
if (!$report->isSuccess()) {
    // Lidar com erros sem exceções
    error_log($report->getErrorMessage());
}
```

---

## 📊 Relatório de Auditoria

O método `audit()` retorna um objeto `AuditReport` com:

```php
$report->getStatus();           // 'healthy', 'degraded', 'down'
$report->getVersion();          // Versão da API
$report->getResponseTime();     // Tempo em ms
$report->getTimestamp();        // DateTime da checagem
$report->getChecks();           // Array de Check objects
$report->isHealthy();          // bool
$report->toArray();            // Array para JSON/log
$report->toJson();             // JSON string
```

---

## 🧪 Testes

```bash
# Rodar testes
composer test

# Análise estática
composer phpstan

# Code style
composer phpcs

# Tudo junto
composer analyse
```

---

## 📝 Exemplo: Monitoramento Contínuo

```php
// Script para cron job (a cada 5 minutos)
use AiVoPro\Integrity\IntegrityManager;

$manager = new IntegrityManager('https://api.28facil.com.br');
$report = $manager->audit();

if (!$report->isHealthy()) {
    // Enviar alerta
    mail(
        'admin@28facil.com.br',
        '⚠️ API 28Fácil com problemas',
        $report->toJson()
    );
    
    // Log
    error_log('[28Fácil] API Health: ' . $report->getStatus());
}
```

---

## 🛠️ Requisitos

- PHP 8.1 ou superior
- Extensões: `json`, `curl`
- Composer

---

## 📄 Licença

MIT License - veja [LICENSE](LICENSE) para detalhes.

---

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor:

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Add: Nova funcionalidade'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

---

## 📧 Suporte

- **Website**: [28facil.com.br](https://28facil.com.br)
- **Email**: contato@28facil.com.br
- **Issues**: [GitHub Issues](https://github.com/OARANHA/28facil-integrity/issues)

---

**Desenvolvido com ❤️ pela equipe 28Fácil / AiVoPro**