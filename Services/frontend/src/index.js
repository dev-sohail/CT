const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const compression = require('compression');
const morgan = require('morgan');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(helmet());
app.use(compression());
app.use(morgan('combined'));
app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// Routes
app.get('/health', (req, res) => {
  res.json({
    status: 'healthy',
    service: 'frontend',
    timestamp: new Date().toISOString()
  });
});

app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// API Proxy
app.use('/api', (req, res) => {
  // Proxy to PHP backend
  const backendUrl = process.env.BACKEND_URL || 'http://localhost:8080';
  res.redirect(`${backendUrl}${req.path}`);
});

app.listen(PORT, () => {
  console.log(`Frontend service running on port ${PORT}`);
});
