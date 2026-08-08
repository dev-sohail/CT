import librosa
import numpy as np
import os
import scipy
import matplotlib.pyplot as plt
from scipy import signal

# Define a function to load the audio file
def load_audio(audio_path, sr=22050):
    """
    Loads an audio file and resamples it to the desired sample rate (sr).
    Arguments:
    - audio_path (str): Path to the audio file.
    - sr (int): Sample rate for resampling (default is 22050Hz).
    
    Returns:
    - y (numpy.ndarray): Audio signal (time-domain).
    - sr (int): Sample rate of the audio file.
    """
    y, sr = librosa.load(audio_path, sr=sr)
    return y, sr

# Extracting MFCCs (Mel Frequency Cepstral Coefficients)
def extract_mfcc(y, sr, n_mfcc=13):
    """
    Extracts MFCC features from an audio signal.
    Arguments:
    - y (numpy.ndarray): Audio signal.
    - sr (int): Sample rate.
    - n_mfcc (int): Number of MFCCs to extract (default is 13).
    
    Returns:
    - mfccs (numpy.ndarray): MFCCs extracted from the audio.
    """
    mfccs = librosa.feature.mfcc(y=y, sr=sr, n_mfcc=n_mfcc)
    mfccs_mean = np.mean(mfccs, axis=1)  # Averaging the MFCC coefficients over time
    return mfccs_mean

# Extracting Chroma (chromagram) feature
def extract_chroma(y, sr):
    """
    Extracts chroma features (pitch-related).
    Arguments:
    - y (numpy.ndarray): Audio signal.
    - sr (int): Sample rate.
    
    Returns:
    - chroma_mean (numpy.ndarray): Averaged chroma feature.
    """
    chroma = librosa.feature.chroma_stft(y=y, sr=sr)
    chroma_mean = np.mean(chroma, axis=1)
    return chroma_mean

# Extracting Spectral Contrast feature
def extract_spectral_contrast(y, sr):
    """
    Extracts spectral contrast feature.
    Arguments:
    - y (numpy.ndarray): Audio signal.
    - sr (int): Sample rate.
    
    Returns:
    - spectral_contrast_mean (numpy.ndarray): Averaged spectral contrast.
    """
    spectral_contrast = librosa.feature.spectral_contrast(y=y, sr=sr)
    spectral_contrast_mean = np.mean(spectral_contrast, axis=1)
    return spectral_contrast_mean

# Extracting Zero Crossing Rate (ZCR)
def extract_zero_crossing_rate(y):
    """
    Extracts the zero crossing rate feature.
    Arguments:
    - y (numpy.ndarray): Audio signal.
    
    Returns:
    - zcr_mean (numpy.ndarray): Averaged zero crossing rate.
    """
    zcr = librosa.feature.zero_crossing_rate(y)
    zcr_mean = np.mean(zcr)
    return zcr_mean

# Extracting Root Mean Square Energy (RMS)
def extract_rms(y):
    """
    Extracts the Root Mean Square Energy feature.
    Arguments:
    - y (numpy.ndarray): Audio signal.
    
    Returns:
    - rms_mean (float): Mean RMS value.
    """
    rms = librosa.feature.rms(y=y)
    rms_mean = np.mean(rms)
    return rms_mean

# Function to extract all relevant features from the audio
def extract_audio_features(audio_path):
    """
    Extracts multiple audio features from an audio file, including:
    - MFCCs, Chroma, Spectral Contrast, ZCR, and RMS.
    
    Arguments:
    - audio_path (str): Path to the audio file.
    
    Returns:
    - features (numpy.ndarray): Combined feature vector from all extracted features.
    """
    # Load the audio file
    y, sr = load_audio(audio_path)
    
    # Extract features
    mfccs = extract_mfcc(y, sr)
    chroma = extract_chroma(y, sr)
    spectral_contrast = extract_spectral_contrast(y, sr)
    zcr = extract_zero_crossing_rate(y)
    rms = extract_rms(y)
    
    # Combine all features into one vector
    features = np.hstack([mfccs, chroma, spectral_contrast, zcr, rms])
    return features

# Example of using the preprocessing to extract features from an audio file
def preprocess_and_extract_features(audio_path):
    """
    Preprocesses an audio file and extracts features for emotion recognition.
    
    Arguments:
    - audio_path (str): Path to the audio file.
    
    Returns:
    - features (numpy.ndarray): Extracted features ready for model input.
    """
    features = extract_audio_features(audio_path)
    return features

# Function to visualize the features (optional)
def visualize_features(audio_path):
    """
    Visualizes the extracted audio features.
    Arguments:
    - audio_path (str): Path to the audio file.
    """
    y, sr = librosa.load(audio_path, sr=None)

    # Plot MFCCs
    mfccs = librosa.feature.mfcc(y=y, sr=sr)
    plt.figure(figsize=(10, 6))
    plt.subplot(3, 1, 1)
    plt.title("MFCC")
    librosa.display.specshow(mfccs, x_axis='time', sr=sr)
    plt.colorbar()

    # Plot Chroma
    chroma = librosa.feature.chroma_stft(y=y, sr=sr)
    plt.subplot(3, 1, 2)
    plt.title("Chroma")
    librosa.display.specshow(chroma, x_axis='time', y_axis='chroma', sr=sr)
    plt.colorbar()

    # Plot Spectral Contrast
    spectral_contrast = librosa.feature.spectral_contrast(y=y, sr=sr)
    plt.subplot(3, 1, 3)
    plt.title("Spectral Contrast")
    librosa.display.specshow(spectral_contrast, x_axis='time', sr=sr)
    plt.colorbar()

    plt.tight_layout()
    plt.show()

if __name__ == "__main__":
    # Example of extracting features from an audio file
    audio_file_path = 'path_to_audio_file.wav'
    features = preprocess_and_extract_features(audio_file_path)
    print(f"Extracted features: {features}")
    
    # Visualizing features
    visualize_features(audio_file_path)
