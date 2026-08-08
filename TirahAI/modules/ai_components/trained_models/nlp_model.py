from transformers import pipeline, AutoTokenizer, AutoModelForSequenceClassification, AutoModelForCausalLM

class NLPModel:
    def __init__(self):
        # Load pre-trained sentiment analysis model
        self.sentiment_model = pipeline("sentiment-analysis")
        
        # Load pre-trained text generation model (e.g., GPT-2)
        self.generator = pipeline("text-generation", model="gpt2", tokenizer="gpt2")

    def analyze_sentiment(self, text: str):
        """
        Analyze sentiment of the input text.
        """
        result = self.sentiment_model(text)
        return result

    def generate_text(self, prompt: str, max_length=100):
        """
        Generate text based on input prompt.
        """
        result = self.generator(prompt, max_length=max_length, num_return_sequences=1)
        return result[0]['generated_text']

# Example usage
nlp_model = NLPModel()
sentiment = nlp_model.analyze_sentiment("I love programming!")
generated_text = nlp_model.generate_text("Once upon a time")
print(sentiment, generated_text)
