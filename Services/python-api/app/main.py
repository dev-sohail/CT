from flask import Flask, jsonify, request
from flask_cors import CORS
import os
from datetime import datetime

app = Flask(__name__)
CORS(app)

@app.route('/health', methods=['GET'])
def health():
    return jsonify({
        'status': 'healthy',
        'service': 'python-api',
        'timestamp': datetime.now().isoformat()
    })

@app.route('/api/ai/analyze', methods=['POST'])
def analyze():
    data = request.get_json()
    
    if not data or 'text' not in data:
        return jsonify({'error': 'Text is required'}), 400
    
    # Simulate AI analysis
    text = data['text']
    analysis = {
        'sentiment': 'positive' if 'good' in text.lower() else 'neutral',
        'keywords': text.split()[:5],
        'confidence': 0.85,
        'timestamp': datetime.now().isoformat()
    }
    
    return jsonify(analysis)

@app.route('/api/ml/predict', methods=['POST'])
def predict():
    data = request.get_json()
    
    if not data or 'features' not in data:
        return jsonify({'error': 'Features are required'}), 400
    
    # Simulate ML prediction
    prediction = {
        'prediction': 'class_a',
        'probability': 0.92,
        'features_used': len(data['features']),
        'timestamp': datetime.now().isoformat()
    }
    
    return jsonify(prediction)

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=True)
