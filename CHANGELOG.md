# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2026-01-19

### Adicionado
- Classe `IntegrityManager` para gerenciamento de verificações
- Sistema de checagens modulares:
  - `HealthCheck` - Verificação de saúde da API
  - `VersionCheck` - Detecção de versão com cache
  - `AuthenticationCheck` - Validação de credenciais
  - `PerformanceCheck` - Métricas de performance
  - `DependenciesCheck` - Verificação de serviços
- Classes de relatório:
  - `AuditReport` - Relatório completo de auditoria
  - `Check` - Representação de checagem individual
- Sistema de cache opcional usando Symfony Cache
- Suporte a múltiplas tentativas e timeout configurável
- Exemplos de uso:
  - `basic_usage.php` - Uso básico do pacote
  - `full_audit.php` - Auditoria completa
  - `monitoring_script.php` - Script para monitoramento contínuo
- Documentação completa no README.md
- Configurações de qualidade de código:
  - PHPUnit para testes
  - PHPStan (nível 8) para análise estática
  - PHP_CodeSniffer com PSR-12
- Licença MIT

### Segurança
- Implementação segura sem backdoors ou execução remota de código
- Validação adequada de respostas HTTP
- Tratamento de exceções com modo silencioso opcional
