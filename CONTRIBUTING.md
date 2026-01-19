# Guia de Contribuição

Obrigado por considerar contribuir com o 28Fácil Integrity Manager! 🚀

## Como Contribuir

### Reportar Bugs

Antes de criar um issue:
1. Verifique se o bug já não foi reportado
2. Inclua informações sobre seu ambiente (PHP, SO, etc)
3. Forneça passos para reproduzir o problema
4. Inclua logs ou mensagens de erro relevantes

### Sugerir Melhorias

- Descreva claramente a funcionalidade sugerida
- Explique por que seria útil para o projeto
- Forneça exemplos de uso, se possível

### Pull Requests

1. **Fork** o repositório
2. **Clone** seu fork
   ```bash
   git clone https://github.com/seu-usuario/28facil-integrity.git
   ```
3. **Crie uma branch** para sua feature
   ```bash
   git checkout -b feature/minha-feature
   ```
4. **Faça suas mudanças** seguindo os padrões do projeto
5. **Rode os testes**
   ```bash
   composer analyse
   ```
6. **Commit** suas mudanças
   ```bash
   git commit -m "Add: Nova funcionalidade"
   ```
7. **Push** para seu fork
   ```bash
   git push origin feature/minha-feature
   ```
8. **Abra um Pull Request**

## Padrões de Código

### Estilo de Código
- Seguimos **PSR-12**
- Use **type hints** sempre que possível
- Documente classes e métodos públicos
- Mantenha métodos pequenos e focados

### Mensagens de Commit

Use prefixos descritivos:
- `Add:` Nova funcionalidade
- `Fix:` Correção de bug
- `Update:` Atualização de funcionalidade existente
- `Remove:` Remoção de código
- `Refactor:` Refatoração sem mudança de comportamento
- `Docs:` Apenas documentação
- `Test:` Adição ou correção de testes

Exemplo:
```
Add: Suporte para webhook de alertas

Implementa sistema de notificação via webhook quando
a API apresenta problemas.
```

### Testes

- Escreva testes para novas funcionalidades
- Garanta que todos os testes passem
- Mantenha cobertura de testes adequada

```bash
composer test
```

### Análise de Código

Antes de submeter PR, execute:

```bash
# Análise completa
composer analyse

# Ou individualmente
composer phpstan  # Análise estática
composer phpcs    # Verificação de estilo
composer test     # Testes unitários
```

## Estrutura do Projeto

```
28facil-integrity/
├── src/
│   ├── Checks/           # Classes de verificação
│   ├── Reports/          # Classes de relatório
│   └── IntegrityManager.php
├── tests/              # Testes unitários
├── examples/           # Exemplos de uso
└── docs/               # Documentação adicional
```

## Adicionando Nova Checagem

Para adicionar uma nova checagem:

1. Crie a classe em `src/Checks/`
2. Implemente o método `execute(): Check`
3. Adicione a checagem em `IntegrityManager::audit()`
4. Escreva testes
5. Atualize a documentação

Exemplo:

```php
<?php
namespace AiVoPro\Integrity\Checks;

use AiVoPro\Integrity\Reports\Check;
use GuzzleHttp\Client;

class MinhaNovaCheck
{
    public function __construct(
        private Client $client,
        private string $apiUrl
    ) {}

    public function execute(): Check
    {
        // Sua lógica aqui
        return new Check(
            name: 'minha_checagem',
            passed: true,
            message: 'Checagem passou',
            data: []
        );
    }
}
```

## Questões?

Se tiver dúvidas:
- Abra uma [issue](https://github.com/OARANHA/28facil-integrity/issues)
- Entre em contato: contato@28facil.com.br

---

**Obrigado por contribuir!** ❤️
