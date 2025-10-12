const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const compression = require('compression');
const morgan = require('morgan');

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(helmet());
app.use(compression());
app.use(morgan('combined'));
app.use(cors());
app.use(express.json());

// Routes
app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    service: 'node-api',
    timestamp: new Date().toISOString()
  });
});

app.get('/api/status', (req, res) => {
  res.json({
    service: 'CyberTirah Node.js API',
    version: '2.0.0',
    status: 'operational'
  });
});

app.post('/api/process', (req, res) => {
  const { data } = req.body;
  
  if (!data) {
    return res.status(400).json({ error: 'Data is required' });
  }
  
  // Simulate processing
  const result = {
    processed: true,
    input: data,
    output: `Processed: ${data}`,
    timestamp: new Date().toISOString()
  };
  
  res.json(result);
});

app.listen(PORT, () => {
  console.log(`Node.js API service running on port ${PORT}`);
});
