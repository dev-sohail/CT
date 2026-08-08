import torch
from transformers import pipeline, AutoTokenizer, AutoModelForSequenceClassification
import numpy as np
import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, accuracy_score

# Pre-trained model from Hugging Face
MODEL_NAME = 'j-hartmann/emotion-english-distilroberta-base'

# Initialize the Hugging Face pipeline for emotion classification
emotion_classifier = pipeline("text-classification", model=MODEL_NAME, tokenizer=MODEL_NAME)

# List of possible emotion classes
EMOTIONS = ['anger', 'fear', 'joy', 'love', 'sadness', 'surprise']

def preprocess_text(text):
    """
    Basic text preprocessing.
    Arguments:
    - text (str): Input text to process.
    
    Returns:
    - processed_text (str): Cleaned input text.
    """
    # Lowercasing the text
    processed_text = text.lower()
    # You can add more text cleaning methods here if necessary (e.g., removing special characters)
    return processed_text

def predict_emotion(text):
    """
    Predict the emotion of the input text using a pre-trained emotion model.
    Arguments:
    - text (str): Input text to classify.
    
    Returns:
    - emotion (str): Predicted emotion.
    """
    text = preprocess_text(text)
    result = emotion_classifier(text)
    predicted_class = result[0]['label']
    return predicted_class

def evaluate_model_on_dataset(dataset):
    """
    Evaluates the model on a custom dataset.
    Arguments:
    - dataset (pandas.DataFrame): Dataset with 'text' and 'emotion' columns.
    
    Returns:
    - None
    """
    # Split dataset into train and test sets
    X_train, X_test, y_train, y_test = train_test_split(dataset['text'], dataset['emotion'], test_size=0.2, random_state=42)

    # Model predictions on test data
    y_pred = [predict_emotion(text) for text in X_test]

    # Print classification report and accuracy
    print(f"Accuracy: {accuracy_score(y_test, y_pred)}")
    print(classification_report(y_test, y_pred, target_names=EMOTIONS))

if __name__ == "__main__":
    # Example usage:
    sample_text = "I feel so happy and energetic today!"
    emotion = predict_emotion(sample_text)
    print(f"Detected emotion: {emotion}")

    # Example of using a custom dataset for evaluation
    # Assuming you have a dataset with text and emotion columns
    data = {
        'text': ['I am so sad today', 'What a wonderful day!', 'I am terrified of spiders', 'I love this song!'],
        'emotion': ['sadness', 'joy', 'fear', 'love']
    }
    dataset = pd.DataFrame(data)
    evaluate_model_on_dataset(dataset)
