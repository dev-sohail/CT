from transformers import pipeline

class SentimentAnalysis:
    def __init__(self):
        self.analyzer = pipeline("sentiment-analysis")

    def analyze(self, text):
        result = self.analyzer(text)
        return result[0]
