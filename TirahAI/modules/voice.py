"""
Voice Module for TirahAi
Handles speech recognition and text-to-speech functionality
"""

import os
import tempfile
import threading
import queue
import json
from typing import Optional, Callable
from config.settings import VOICE_CONFIG
from utils.logger import get_logger

try:
    import speech_recognition as sr
except ImportError:
    sr = None

try:
    import pyttsx3
except ImportError:
    pyttsx3 = None

try:
    from gtts import gTTS
except ImportError:
    gTTS = None

try:
    import pvporcupine
    _porcupine_available = True
except ImportError:
    _porcupine_available = False

try:
    import vosk
    vosk.SetLogLevel(-1)
    _vosk_available = True
except ImportError:
    _vosk_available = False

try:
    import sounddevice as sd
    _sounddevice_available = True
except ImportError:
    _sounddevice_available = False

logger = get_logger(__name__)


class VoiceAssistant:
    """Manages voice input and output for TirahAi."""
    
    def __init__(self):
        """Initialize voice assistant with TTS engine and recognizer."""
        self.recognizer = sr.Recognizer() if sr else None
        self.engine = None
        self.is_listening = False
        self._mic_available = None
        self.wake_word = "tirah"
        
        self._wake_thread = None
        self._wake_stop_event = threading.Event()
        self._wake_lock = threading.Lock()
        self._wake_callback = None
        self._porcupine = None
        self._vosk_model = None
        self._vosk_recognizer = None
        self._wake_word_active = False
        
        if VOICE_CONFIG['engine'] == 'pyttsx3' and pyttsx3:
            try:
                self.engine = pyttsx3.init()
                self._configure_engine()
                logger.info("Voice engine initialized successfully")
            except Exception as e:
                logger.error(f"Failed to initialize voice engine: {e}")
    
    def check_microphone(self) -> bool:
        """Check if a microphone device is available."""
        if not sr:
            logger.warning("Speech recognition not available")
            return False
        if self._mic_available is not None:
            return self._mic_available
        try:
            with sr.Microphone() as source:
                pass
            self._mic_available = True
        except Exception as e:
            logger.error(f"Microphone not available: {e}")
            self._mic_available = False
        return self._mic_available
    
    def _configure_engine(self):
        """Configure the pyttsx3 engine with settings."""
        if self.engine:
            self.engine.setProperty('rate', VOICE_CONFIG['rate'])
            self.engine.setProperty('volume', VOICE_CONFIG['volume'])
            voices = self.engine.getProperty('voices')
            if voices and len(voices) > VOICE_CONFIG['voice_id']:
                self.engine.setProperty('voice', voices[VOICE_CONFIG['voice_id']].id)
    
    def speak(self, text: str, use_gtts: bool = False):
        """Convert text to speech."""
        try:
            logger.info(f"Speaking: {text}")
            if use_gtts or VOICE_CONFIG['engine'] == 'gtts':
                self._speak_gtts(text)
            else:
                self._speak_pyttsx3(text)
        except Exception as e:
            logger.error(f"Speech error: {e}")
    
    def _speak_pyttsx3(self, text: str):
        """Speak using pyttsx3 engine."""
        if not pyttsx3:
            logger.warning("pyttsx3 not available")
            return
        if self.engine:
            try:
                thread_engine = pyttsx3.init()
                thread_engine.setProperty('rate', VOICE_CONFIG['rate'])
                thread_engine.setProperty('volume', VOICE_CONFIG['volume'])
                voices = thread_engine.getProperty('voices')
                if voices and len(voices) > VOICE_CONFIG['voice_id']:
                    thread_engine.setProperty('voice', voices[VOICE_CONFIG['voice_id']].id)
                thread_engine.say(text)
                thread_engine.runAndWait()
                thread_engine.stop()
            except Exception as e:
                logger.error(f"pyttsx3 thread error: {e}")
                try:
                    self.engine.say(text)
                    self.engine.runAndWait()
                except Exception as fallback_error:
                    logger.error(f"pyttsx3 fallback also failed: {fallback_error}")
    
    def _speak_gtts(self, text: str):
        """Speak using Google TTS."""
        if not gTTS:
            logger.warning("gTTS not available")
            return
        try:
            import pygame
            if not pygame.mixer.get_init():
                pygame.mixer.init()
            with tempfile.NamedTemporaryFile(delete=False, suffix='.mp3') as fp:
                temp_file = fp.name
            tts = gTTS(text=text, lang=VOICE_CONFIG['language'], slow=False)
            tts.save(temp_file)
            pygame.mixer.music.load(temp_file)
            pygame.mixer.music.play()
            while pygame.mixer.music.get_busy():
                pygame.time.Clock().tick(10)
            pygame.mixer.music.stop()
            pygame.mixer.quit()
            import time
            time.sleep(0.1)
            try:
                os.remove(temp_file)
            except:
                pass
        except Exception as e:
            logger.error(f"gTTS error: {e}")
    
    def listen(self, timeout: int = 5, phrase_time_limit: int = 10) -> Optional[str]:
        """Listen for voice input and convert to text."""
        if not sr:
            logger.warning("Speech recognition not available")
            return None
        try:
            if not self.check_microphone():
                return None
            with sr.Microphone() as source:
                logger.info("Listening...")
                self.is_listening = True
                try:
                    self.recognizer.adjust_for_ambient_noise(source, duration=0.2)
                except Exception:
                    pass
                audio = self.recognizer.listen(
                    source,
                    timeout=timeout,
                    phrase_time_limit=phrase_time_limit
                )
                self.is_listening = False
                logger.info("Processing speech...")
                try:
                    text = self.recognizer.recognize_google(audio, language=VOICE_CONFIG.get('language', 'en'))
                except sr.RequestError:
                    try:
                        text = self.recognizer.recognize_sphinx(audio)
                    except Exception as ex:
                        logger.error(f"Offline recognition error: {ex}")
                        return None
                logger.info(f"Recognized: {text}")
                return text.lower()
        except sr.WaitTimeoutError:
            self.is_listening = False
            logger.warning("Listening timeout - no speech detected")
            return None
        except sr.UnknownValueError:
            self.is_listening = False
            logger.warning("Could not understand audio")
            return None
        except sr.RequestError as e:
            self.is_listening = False
            logger.error(f"Speech recognition API error: {e}")
            return None
        except Exception as e:
            self.is_listening = False
            logger.error(f"Unexpected error in listen: {e}")
            return None
    
    def listen_in_background(self, callback):
        """Start listening in background mode."""
        if not sr:
            logger.warning("Speech recognition not available")
            return None
        def audio_callback(recognizer, audio):
            try:
                text = recognizer.recognize_google(audio)
                callback(text.lower())
            except sr.UnknownValueError:
                pass
            except sr.RequestError as e:
                logger.error(f"API error in background: {e}")
        try:
            if not self.check_microphone():
                return None
            with sr.Microphone() as source:
                try:
                    self.recognizer.adjust_for_ambient_noise(source)
                except Exception:
                    pass
            stop_listening = self.recognizer.listen_in_background(
                sr.Microphone(),
                audio_callback
            )
            logger.info("Background listening started")
            return stop_listening
        except Exception as e:
            logger.error(f"Failed to start background listening: {e}")
            return None
    
    def set_wake_word(self, wake_word: str = "tirah"):
        """
        Set the wake word for activation using Porcupine (primary) or Vosk (fallback).
        
        Args:
            wake_word: Word to activate assistant
        """
        self.wake_word = wake_word.lower()
        self._release_wake_engine()
        
        if _porcupine_available:
            try:
                access_key = VOICE_CONFIG.get('porcupine_access_key', None)
                keyword_paths = VOICE_CONFIG.get('wake_word_keyword_paths', None)
                
                if access_key and keyword_paths and os.path.exists(keyword_paths):
                    self._porcupine = pvporcupine.create(
                        access_key=access_key,
                        keyword_paths=[keyword_paths]
                    )
                    logger.info(f"Wake word engine set to Porcupine with keyword: {wake_word}")
                    return True
                else:
                    logger.warning(
                        "Porcupine keyword path not found or access_key missing. "
                        "Falling back to Vosk."
                    )
            except Exception as e:
                logger.warning(f"Porcupine initialization failed: {e}, falling back to Vosk")
                self._porcupine = None
        
        if _vosk_available:
            try:
                model_path = VOICE_CONFIG.get('vosk_model_path', None)
                if model_path and os.path.exists(model_path):
                    self._vosk_model = vosk.Model(model_path)
                    logger.info(f"Wake word engine set to Vosk with keyword: {wake_word}")
                    return True
                else:
                    logger.warning(f"Vosk model path not found: {model_path}")
            except Exception as e:
                logger.error(f"Vosk initialization failed: {e}")
                self._vosk_model = None
        else:
            logger.warning("Neither Porcupine nor Vosk is available for wake word detection")
        
        logger.info(f"Wake word '{wake_word}' set but no wake word engine available")
        return False
    
    def _release_wake_engine(self):
        """Release Porcupine/Vosk resources."""
        if self._porcupine:
            try:
                self._porcupine.delete()
            except Exception:
                pass
            self._porcupine = None
        self._vosk_recognizer = None
        self._vosk_model = None
    
    def start_wake_word_listening(self, callback: Callable[[str], None]):
        """
        Start listening for the wake word in a background thread.
        
        Args:
            callback: Function to call with the detected wake word
        """
        with self._wake_lock:
            if self._wake_thread and self._wake_thread.is_alive():
                logger.warning("Wake word listening already running")
                return False
            
            if not _sounddevice_available:
                logger.error("sounddevice is required for wake word listening")
                return False
            
            if not self._porcupine and not self._vosk_model:
                logger.error("No wake word engine initialized. Call set_wake_word() first.")
                return False
            
            self._wake_callback = callback
            self._wake_word_active = True
            self._wake_stop_event.clear()
            self._wake_thread = threading.Thread(
                target=self._wake_word_loop,
                daemon=True
            )
            self._wake_thread.start()
            logger.info("Wake word listening started")
            return True
    
    def _wake_word_loop(self):
        """Background loop for wake word detection."""
        callback = self._wake_callback
        stop_event = self._wake_stop_event
        wake_word = self.wake_word
        
        try:
            if self._porcupine:
                self._porcupine_detect(callback, stop_event, wake_word)
            elif self._vosk_model:
                self._vosk_detect(callback, stop_event, wake_word)
        except Exception as e:
            logger.error(f"Wake word loop error: {e}")
        finally:
            with self._wake_lock:
                self._wake_word_active = False
                self._wake_thread = None
                self._wake_callback = None
    
    def _porcupine_detect(self, callback, stop_event, wake_word):
        """Porcupine wake word detection loop."""
        porcupine = self._porcupine
        frame_length = porcupine.frame_length
        sample_rate = porcupine.sample_rate
        
        q = queue.Queue(maxsize=1)
        
        def audio_callback(indata, frames, time, status):
            if status:
                logger.warning(f"Audio stream status: {status}")
            try:
                q.put_nowait(bytes(indata))
            except queue.Full:
                pass
        
        stream = None
        try:
            stream = sd.InputStream(
                samplerate=sample_rate,
                channels=1,
                dtype='int16',
                blocksize=frame_length,
                callback=audio_callback
            )
            stream.start()
            logger.info("Porcupine listening active")
            while not stop_event.is_set():
                try:
                    data = q.get(timeout=1.0)
                except queue.Empty:
                    continue
                
                result = porcupine.process(data)
                if result >= 0:
                    logger.info(f"Wake word detected: {wake_word}")
                    callback(wake_word)
                    stop_event.wait(0.5)
        except Exception as e:
            logger.error(f"Porcupine stream error: {e}")
        finally:
            if stream:
                try:
                    stream.stop()
                except Exception:
                    pass
                try:
                    stream.close()
                except Exception:
                    pass
    
    def _vosk_detect(self, callback, stop_event, wake_word):
        """Vosk wake word detection loop."""
        model = self._vosk_model
        sample_rate = 16000
        rec = vosk.KaldiRecognizer(model, sample_rate)
        
        q = queue.Queue(maxsize=1)
        
        def audio_callback(indata, frames, time, status):
            if status:
                logger.warning(f"Audio stream status: {status}")
            try:
                q.put_nowait(bytes(indata))
            except queue.Full:
                pass
        
        stream = None
        try:
            stream = sd.InputStream(
                samplerate=sample_rate,
                channels=1,
                dtype='int16',
                blocksize=4000,
                callback=audio_callback
            )
            stream.start()
            logger.info("Vosk listening active")
            last_partial = ""
            while not stop_event.is_set():
                try:
                    data = q.get(timeout=1.0)
                except queue.Empty:
                    continue
                
                if rec.AcceptWaveform(data):
                    result = json.loads(rec.Result())
                    text = result.get('text', '').lower()
                    if wake_word in text:
                        logger.info(f"Wake word detected: {wake_word}")
                        callback(wake_word)
                        last_partial = ""
                        stop_event.wait(0.5)
                else:
                    partial = json.loads(rec.PartialResult())
                    text = partial.get('partial', '').lower()
                    if text and text != last_partial and wake_word in text:
                        logger.info(f"Wake word detected: {wake_word}")
                        callback(wake_word)
                        last_partial = ""
                        stop_event.wait(0.5)
                    last_partial = text
        except Exception as e:
            logger.error(f"Vosk stream error: {e}")
        finally:
            if stream:
                try:
                    stream.stop()
                except Exception:
                    pass
                try:
                    stream.close()
                except Exception:
                    pass
    
    def stop_wake_word_listening(self):
        """Stop wake word listening and clean up resources."""
        with self._wake_lock:
            if not self._wake_word_active:
                logger.info("Wake word listening not running")
                return
            
            self._wake_stop_event.set()
            self._wake_thread.join(timeout=2.0)
            self._wake_word_active = False
            self._wake_thread = None
            self._wake_callback = None
            logger.info("Wake word listening stopped")
    
    def wait_for_wake_word(self) -> bool:
        """Wait for wake word to be spoken."""
        command = self.listen()
        if command and self.wake_word in command:
            logger.info("Wake word detected!")
            return True
        return False
    
    def cleanup(self):
        """Clean up resources."""
        self.stop_wake_word_listening()
        self._release_wake_engine()
        if self.engine:
            try:
                self.engine.stop()
            except:
                pass
        logger.info("Voice assistant cleaned up")


# Convenience functions for backward compatibility
_voice_instance = None

def get_voice_assistant() -> VoiceAssistant:
    """Get singleton voice assistant instance."""
    global _voice_instance
    if _voice_instance is None:
        _voice_instance = VoiceAssistant()
    return _voice_instance


def speak(text: str):
    """Quick speak function."""
    get_voice_assistant().speak(text)


def listen() -> Optional[str]:
    """Quick listen function."""
    return get_voice_assistant().listen()
