import os
import librosa
import numpy as np
from transformers import pipeline
from sklearn.preprocessing import LabelEncoder
import joblib
import warnings
import time

# Suppress warnings (optional)
warnings.filterwarnings("ignore", category=UserWarning)

# Global configurations
MODEL_PATH = 'emotion_model.pkl'  # Pre-trained speech emotion model
TEXT_EMOTION_MODEL = 'j-hartmann/emotion-english-distilroberta-base'  # Pre-trained text emotion model

# Text-based Emotion Recognition using Hugging Face transformers
def load_text_emotion_model():
    """
    Loads the pre-trained text emotion recognition model.
    """
    print("Loading text emotion recognition model...")
    return pipeline('text-classification', model=TEXT_EMOTION_MODEL)

def recognize_text_emotion(text, model=None):
    """
    Recognizes the emotion from input text.
    Arguments:
    - text (str): The input text to classify emotion.
    - model (transformers.pipeline): The pre-loaded Hugging Face emotion model.

    Returns:
    - emotion (str): Recognized emotion.
    """
    if not model:
        model = load_text_emotion_model()

    # Predict emotion
    result = model(text)
    emotion = result[0]['label']
    return emotion

# Audio-based Emotion Recognition using Librosa
def extract_audio_features(audio_path):
    """
    Extracts MFCC, Chroma, and Spectral Contrast features from the audio file.
    Arguments:
    - audio_path (str): Path to the audio file.

    Returns:
    - features (numpy.array): Array of extracted features.
    """
    try:
        # Load audio file
        y, sr = librosa.load(audio_path, sr=None)

        # Extract MFCCs (Mel Frequency Cepstral Coefficients)
        mfccs = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=13)
        mfccs_mean = np.mean(mfccs, axis=1)

        # Extract Chroma feature
        chroma = librosa.feature.chroma_stft(y=y, sr=sr)
        chroma_mean = np.mean(chroma, axis=1)

        # Extract Spectral Contrast feature
        spectral_contrast = librosa.feature.spectral_contrast(y=y, sr=sr)
        spectral_contrast_mean = np.mean(spectral_contrast, axis=1)

        # Combine features
        features = np.hstack([mfccs_mean, chroma_mean, spectral_contrast_mean])
        return features

    except Exception as e:
        print(f"Error in feature extraction: {e}")
        return None

def load_emotion_model(model_path=MODEL_PATH):
    """
    Loads the pre-trained emotion recognition model (SVM or deep learning).
    Arguments:
    - model_path (str): Path to the pre-trained model.

    Returns:
    - model: Loaded emotion recognition model.
    """
    if os.path.exists(model_path):
        print(f"Loading pre-trained model from {model_path}...")
        return joblib.load(model_path)
    else:
        print("Model not found. Please train a model first.")
        return None

def recognize_speech_emotion(audio_path, model=None):
    """
    Recognizes the emotion from an audio file.
    Arguments:
    - audio_path (str): Path to the audio file.
    - model: The pre-trained emotion model (SVM or deep learning).

    Returns:
    - emotion (str): Recognized emotion.
    """
    if not model:
        model = load_emotion_model()

    # Extract features from the audio file
    features = extract_audio_features(audio_path)
    if features is None:
        return "Error extracting features"

    # Predict emotion from the features
    prediction = model.predict([features])
    return prediction[0]

# Real-time Audio Emotion Recognition (Optional)
def real_time_audio_emotion(model=None, duration=5):
    """
    Real-time emotion recognition using audio input from a microphone.
    Arguments:
    - model: The pre-trained emotion model.
    - duration: The duration to record audio (in seconds).
    """
    import sounddevice as sd
    import scipy.io.wavfile as wav

    print(f"Recording audio for {duration} seconds...")

    # Record audio from microphone
    fs = 16000  # Sampling frequency
    recording = sd.rec(int(duration * fs), samplerate=fs, channels=1, dtype='int16')
    sd.wait()  # Wait until recording is finished

    # Save the recording as a WAV file
    audio_path = "temp_audio.wav"
    wav.write(audio_path, fs, recording)

    # Recognize emotion from recorded audio
    emotion = recognize_speech_emotion(audio_path, model)
    print(f"Detected Emotion: {emotion}")
    os.remove(audio_path)  # Remove temporary file

# Adaptive Learning for Emotion Recognition
def retrain_model(new_data, model=None):
    """
    Retrains the emotion model with new data.
    Arguments:
    - new_data (list): New labeled data for training the model.
    - model: The existing pre-trained model.
    """
    print("Retraining model with new data...")

    # Assuming new_data is a list of (features, labels) pairs
    features = [data[0] for data in new_data]
    labels = [data[1] for data in new_data]

    # Retrain the model (using SVM in this case)
    from sklearn.svm import SVC
    model = SVC(kernel='linear')
    model.fit(features, labels)

    # Save the retrained model
    joblib.dump(model, MODEL_PATH)
    print(f"Model retrained and saved to {MODEL_PATH}")
    return model

if __name__ == "__main__":
    # Example Usage
    # Text Emotion Recognition
    text_input = "I am so excited to start learning!"
    text_model = load_text_emotion_model()  # Load text model
    emotion_text = recognize_text_emotion(text_input, text_model)
    print(f"Text Emotion: {emotion_text}")

    # Audio Emotion Recognition
    audio_file = 'path_to_audio.wav'  # Replace with an actual audio file path
    speech_model = load_emotion_model()  # Load speech model
    emotion_speech = recognize_speech_emotion(audio_file, speech_model)
    print(f"Speech Emotion: {emotion_speech}")

    # Real-Time Audio Emotion Recognition (Optional)
    # real_time_audio_emotion(speech_model, duration=5)

    # Retraining Example (Optional)
    # new_data = [(features, label), (features2, label2), ...]
    # retrained_model = retrain_model(new_data, speech_model)
