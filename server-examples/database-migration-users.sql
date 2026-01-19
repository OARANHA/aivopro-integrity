-- =====================================================
-- TABELA DE USUÁRIOS (para autenticação JWT)
-- =====================================================

CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Dados Básicos
    `name` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt da senha',
    
    -- Verificação
    `email_verified_at` TIMESTAMP NULL,
    `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
    
    -- Tokens
    `remember_token` VARCHAR(100) NULL,
    
    -- Auditoria
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login_at` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(45) NULL,
    
    -- Índices
    INDEX `idx_email` (`email`),
    INDEX `idx_is_active` (`is_active`)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Usuários do sistema 28Fácil';

-- =====================================================
-- INSERIR USUÁRIO DE TESTE
-- Senha: senha123
-- =====================================================

INSERT INTO `users` (
    `name`,
    `email`,
    `password`,
    `email_verified_at`,
    `is_active`
) VALUES (
    'Usuário Teste',
    'teste@28facil.com.br',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- senha: senha123
    NOW(),
    TRUE
);

-- Verificar
SELECT id, name, email, is_active, created_at 
FROM users;
