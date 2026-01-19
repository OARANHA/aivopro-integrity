/**
 * Exemplo completo de endpoints para Express.js
 * Cole este código no seu server.js ou crie um arquivo routes/integrity.js
 */

const express = require('express');
const router = express.Router();

// ===========================
// 1. HEALTH CHECK (OBRIGATÓRIO)
// ===========================
router.get('/health', (req, res) => {
  res.json({
    status: 'ok',
    timestamp: new Date().toISOString(),
    uptime: process.uptime()
  });
});

// ===========================
// 2. VERSION INFO
// ===========================
router.get('/version', (req, res) => {
  res.json({
    version: process.env.API_VERSION || '1.0.0',
    api_name: '28Fácil API',
    environment: process.env.NODE_ENV || 'production',
    node_version: process.version
  });
});

// ===========================
// 3. ROOT ENDPOINT (com versão)
// ===========================
router.get('/', (req, res) => {
  res.json({
    name: '28Fácil API',
    version: process.env.API_VERSION || '1.0.0',
    status: 'running',
    documentation: 'https://api.28facil.com.br/docs'
  });
});

// ===========================
// 4. AUTH VALIDATION
// ===========================
router.get('/auth/validate', (req, res) => {
  // Extrair API key do header
  const apiKey = req.headers['x-api-key'] || 
                 (req.headers.authorization || '').replace('Bearer ', '');
  
  if (!apiKey) {
    return res.status(401).json({
      valid: false,
      error: 'API key missing'
    });
  }
  
  // SUBSTITUA ISSO pela sua lógica de validação real
  // Exemplo: consultar banco de dados, Redis, etc
  const isValid = validateApiKey(apiKey); // Função sua
  
  if (isValid) {
    res.json({
      valid: true,
      user: {
        id: '123',
        name: 'Usuário',
        email: 'usuario@28facil.com.br'
      },
      permissions: ['read', 'write']
    });
  } else {
    res.status(401).json({
      valid: false,
      error: 'Invalid API key'
    });
  }
});

// ===========================
// 5. DEPENDENCIES STATUS
// ===========================
router.get('/status/dependencies', async (req, res) => {
  // OPCIONAL: Proteger este endpoint
  const adminKey = req.headers['x-admin-key'];
  if (adminKey !== process.env.ADMIN_API_KEY) {
    return res.status(401).json({ error: 'Unauthorized' });
  }
  
  const services = {
    database: await checkDatabase(),
    redis: await checkRedis(),
    evolution_api: await checkEvolutionAPI(),
    email: await checkEmailService()
  };
  
  // Verificar se todos estão saudáveis
  const allHealthy = Object.values(services).every(s => s === 'healthy');
  
  res.status(allHealthy ? 200 : 503).json({
    services,
    overall_status: allHealthy ? 'healthy' : 'degraded'
  });
});

// ===========================
// FUNÇÕES AUXILIARES
// ===========================

function validateApiKey(apiKey) {
  // IMPLEMENTE SUA LÓGICA AQUI
  // Exemplo simples:
  return apiKey === process.env.VALID_API_KEY;
  
  // Ou consultar banco:
  // const user = await db.query('SELECT * FROM api_keys WHERE key = ?', [apiKey]);
  // return user.length > 0;
}

async function checkDatabase() {
  try {
    // SUBSTITUA pela sua conexão real
    // const result = await db.query('SELECT 1');
    
    // Exemplo com Prisma:
    // await prisma.$queryRaw`SELECT 1`;
    
    // Exemplo com Sequelize:
    // await sequelize.authenticate();
    
    return 'healthy';
  } catch (error) {
    console.error('Database check failed:', error);
    return 'unhealthy';
  }
}

async function checkRedis() {
  try {
    // SUBSTITUA pela sua conexão real
    // const redis = require('./config/redis');
    // await redis.ping();
    
    return 'healthy';
  } catch (error) {
    console.error('Redis check failed:', error);
    return 'unhealthy';
  }
}

async function checkEvolutionAPI() {
  try {
    const evolutionUrl = process.env.EVOLUTION_API_URL || 'http://localhost:8080';
    const response = await fetch(`${evolutionUrl}/health`, {
      method: 'GET',
      timeout: 5000
    });
    
    return response.ok ? 'healthy' : 'unhealthy';
  } catch (error) {
    console.error('Evolution API check failed:', error);
    return 'unhealthy';
  }
}

async function checkEmailService() {
  try {
    // Verificar se consegue conectar ao SMTP
    // const nodemailer = require('nodemailer');
    // await transporter.verify();
    
    return 'healthy';
  } catch (error) {
    console.error('Email service check failed:', error);
    return 'unhealthy';
  }
}

// ===========================
// EXPORTAR
// ===========================
module.exports = router;

// NO SEU server.js:
// const integrityRoutes = require('./routes/integrity');
// app.use('/', integrityRoutes);
