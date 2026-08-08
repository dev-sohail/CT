"""
NLP Brain Module for TirahAi
Natural Language Processing and AI response generation
"""

from typing import Optional, List, Dict
import os
import re as re_module
from datetime import datetime, timedelta

from config.settings import AI_CONFIG, OPENAI_API_KEY, GEMINI_API_KEY
from utils.logger import get_logger
from modules.os_assistant import OSAssistant

logger = get_logger(__name__)


class NLPBrain:
    """
    Natural Language Processing brain for TirahAi.
    Handles conversation, context, and AI responses.
    """
    
    def __init__(self):
        """Initialize NLP brain with AI models."""
        self.conversation_history: List[Dict[str, str]] = []
        self.context_memory = AI_CONFIG['context_memory']
        self.primary_model = AI_CONFIG['primary_model']
        self.fallback_model = AI_CONFIG['fallback_model']
        
        self.openai_client = None
        self.gemini_model = None
        
        # Initialize OS Assistant
        self.os_assistant = OSAssistant()
        
        # Initialize OpenAI if available
        if OPENAI_API_KEY:
            try:
                from openai import OpenAI
                self.openai_client = OpenAI(api_key=OPENAI_API_KEY)
                logger.info("OpenAI API initialized")
            except Exception as e:
                logger.warning(f"Failed to initialize OpenAI: {e}")
        
        # Initialize Gemini if available
        self.gemini_client = None
        # self.gemini_model_name = "gemini-2.0-flash"
        self.gemini_model_name = "gemini-3.5-flash"
        if GEMINI_API_KEY:
            try:
                from google import genai
                self.gemini_client = genai.Client(api_key=GEMINI_API_KEY)
                logger.info("Gemini API initialized")
            except Exception as e:
                logger.warning(f"Failed to initialize Gemini: {e}")
        
        logger.info("NLP Brain initialized")
    
    def process_command(self, command: str, context: Optional[str] = None) -> str:
        """
        Process user command and generate response.
        
        Args:
            command: User's command/query
            context: Optional context information
            
        Returns:
            AI-generated response
        """
        try:
            # Check for direct automation commands first
            intent = self.extract_intent(command)
            if intent == 'system_control':
                result = self._handle_automation(command)
                if result:
                    return result

            # Add to conversation history
            self._add_to_history("user", command)
            
            # Generate response based on primary model
            if self.primary_model == 'gemini' and self.gemini_client:
                response = self._gemini_response(command, context)
            elif self.primary_model == 'openai' and self.openai_client:
                response = self._openai_response(command, context)
            elif self.primary_model == 'ollama':
                response = self._ollama_response(command, context)
            else:
                # Try fallback sequence: prefer local model first
                response = self._ollama_response(command, context)
                if response == "I'm in offline mode and couldn't process that request. Try asking for 'help' to see what I can do.":
                    if self.gemini_client:
                        response = self._gemini_response(command, context)
                    elif self.openai_client:
                        response = self._openai_response(command, context)
                    else:
                        response = self._fallback_response(command)
            
            # Add response to history
            self._add_to_history("assistant", response)
            
            return response
            
        except Exception as e:
            logger.error(f"Error processing command: {e}")
            return "I encountered an error processing your request. Please try again."

    def _handle_automation(self, command: str) -> Optional[str]:
        """Handle system automation commands."""
        command_lower = command.lower()
        
        # App launching
        if "open" in command_lower or "launch" in command_lower:
            # Extract app name (simple heuristic)
            words = command_lower.split()
            try:
                idx = words.index("open") if "open" in words else words.index("launch")
                if idx + 1 < len(words):
                    app_name = words[idx + 1]
                    return self.os_assistant.open_application(app_name)
            except:
                pass

        # App closing
        if "close" in command_lower:
             words = command_lower.split()
             try:
                 idx = words.index("close")
                 if idx + 1 < len(words):
                     app_name = words[idx + 1]
                     return self.os_assistant.close_application(app_name)
             except:
                 pass
        
        # System info
        if "memory" in command_lower or "ram" in command_lower:
            info = self.os_assistant.get_system_info()
            return f"Memory Usage: {info.get('memory_used')} / {info.get('memory_total')} ({info.get('memory_percent')})"
            
        if "battery" in command_lower:
            status = self.os_assistant.get_battery_status()
            return f"Battery: {status.get('percent')}% {'(Plugged In)' if status.get('plugged_in') else '(On Battery)'}"

        # Screenshot
        if "screenshot" in command_lower:
            path = self.os_assistant.take_screenshot()
            return f"Screenshot saved to {path}"

        # Task Scheduling (Simple Natural Language)
        # "Remind me to [action] in [X] seconds/minutes"
        if "remind me to" in command_lower:
            try:
                    # Regex to parse "remind me to <task> in <number> <unit>"
                match = re_module.search(r"remind me to (.+) in (\d+) (seconds?|minutes?|hours?)", command_lower)
                if match:
                    task_desc = match.group(1)
                    amount = int(match.group(2))
                    unit = match.group(3)
                    
                    seconds = amount
                    if "minute" in unit:
                        seconds *= 60
                    elif "hour" in unit:
                        seconds *= 3600
                        
                    # Define a simple wrapper function for the task
                    def reminder_task():
                        logger.info(f"REMINDER: {task_desc}")
                        # In a real app, this might trigger a notification UI or TTS
                        
                    job_name = f"reminder_{int(datetime.now().timestamp())}"
                    from modules.automation.task_scheduler import add_task_to_scheduler
                    add_task_to_scheduler(reminder_task, "interval", {"seconds": seconds}, job_name)
                    
                    return f"I've scheduled a reminder to '{task_desc}' in {amount} {unit}."
            except Exception as e:
                logger.error(f"Scheduling error: {e}")

        return None

    def _gemini_response(self, command: str, context: Optional[str] = None) -> str:
        """Generate response using Google Gemini (new genai SDK)."""
        try:
            if not self.gemini_client:
                return self._ollama_response(command, context)

            prompt = command
            if context:
                prompt = f"Context: {context}\n\nQuery: {command}"
                
            response = self.gemini_client.models.generate_content(
                model=self.gemini_model_name,
                contents=prompt,
            )
            return response.text
            
        except Exception as e:
            logger.error(f"Gemini API error: {e}")
            return self._ollama_response(command, context)
    
    def _openai_response(self, command: str, context: Optional[str] = None) -> str:
        """Generate response using OpenAI API."""
        try:
            if not self.openai_client:
                return self._ollama_response(command, context)
            
            messages = self._build_messages(context)
            
            response = self.openai_client.chat.completions.create(
                model=AI_CONFIG.get('code_model', 'gpt-4-turbo'),
                messages=messages,
                temperature=AI_CONFIG['temperature'],
                max_tokens=AI_CONFIG['max_tokens']
            )
            
            return response.choices[0].message.content
            
        except Exception as e:
            logger.error(f"OpenAI API error: {e}")
            # Fallback to alternative model
            return self._ollama_response(command, context)
    
    def _ollama_response(self, command: str, context: Optional[str] = None) -> str:
        """Generate response using Ollama (local AI)."""
        try:
            import ollama
            
            messages = self._build_messages(context)
            
            # Try to list models to find a suitable one if 'llama2' isn't hardcoded
            # For now, we default to mistral or llama2 commonly used
            model = 'mistral' 
            
            response = ollama.chat(
                model=model,
                messages=messages
            )
            
            return response['message']['content']
            
        except Exception as e:
            logger.error(f"Ollama error: {e}")
            return self._fallback_response(command)
    
    def _fallback_response(self, command: str) -> str:
        """Simple rule-based fallback response."""
        command_lower = command.lower()
        
        # 1. Greetings
        greeting_words = re_module.compile(r'\b(hello|hi|hey|greetings)\b', re_module.IGNORECASE)
        if greeting_words.search(command_lower):
            return "Hello! I am operating in offline mode. How can I help you locally?"
            
        # 2. Help
        elif any(word in command_lower for word in ['help', 'what can you do', 'capabilities']):
            return ("I am currently offline, but I can still help you with:\n"
                   "- System commands (open apps, check status)\n"
                   "- Basic calculations\n"
                   "- Time and Date\n"
                   "- File management")

        # 3. Time/Date
        elif 'time' in command_lower:
            return f"The current time is {datetime.now().strftime('%I:%M %p')}"
        
        elif 'date' in command_lower or 'today' in command_lower:
            return f"Today is {datetime.now().strftime('%A, %B %d, %Y')}"

        # 4. Jokes
        elif 'joke' in command_lower or 'funny' in command_lower:
            import random
            jokes = [
                "Why do programmers prefer dark mode? Because light attracts bugs.",
                "How many programmers does it take to change a light bulb? None, that's a hardware problem.",
                "I would tell you a UDP joke, but you might not get it.",
                "There are 10 types of people in the world: those who understand binary, and those who don't."
            ]
            return random.choice(jokes)

        elif 'organize files' in command_lower or 'organize folder' in command_lower:
            return "Say 'organize files' from the UI or voice to pick a folder to organize."

        elif re_module.search(r'\bstudy mode\b', command_lower) or re_module.search(r'\bopen study\b', command_lower):
            return "Say 'open study' to switch to learning mode."

        elif re_module.search(r'\bcode mode\b', command_lower) or re_module.search(r'\bopen code\b', command_lower):
            return "Say 'open code' to open the code tools."

        # 5. Who are you
        elif 'who are you' in command_lower:
             return "I am TirahAi, your personal AI assistant. I am currently running in local mode."

        # 6. Math (Simple)
        elif any(op in command_lower for op in ['+', '-', '*', '/', 'calculate', 'compute']):
             try:
                 expr = re_module.sub(r'[a-zA-Z:?]', '', command)
                 expr = re_module.sub(r'[^0-9+\-*/(). ]', '', expr)
                 if len(expr.strip()) > 2 and len(expr) < 50:
                     result = eval(expr)
                     return f"The result is {result}"
             except:
                 pass

        return "I'm in offline mode and couldn't process that request. Try asking for 'help' to see what I can do."
    
    def _build_messages(self, context: Optional[str] = None) -> List[Dict[str, str]]:
        """Build message history for OpenAI/Ollama."""
        messages = [
            {
                "role": "system",
                "content": self._get_system_prompt(context)
            }
        ]
        
        # Add conversation history (limited by context_memory)
        messages.extend(self.conversation_history[-self.context_memory * 2:])
        
        return messages

    def _get_system_prompt(self, context: Optional[str] = None) -> str:
        """Get system prompt for AI."""
        # Get dynamic system info
        sys_info = self.os_assistant.get_system_info()
        time_str = datetime.now().strftime("%I:%M %p, %A %B %d")
        
        base_prompt = f"""You are TirahAi, an intelligent AI assistant.
        Current Time: {time_str}
        System: {sys_info.get('os', 'Unknown')}
        Battery: {self.os_assistant.get_battery_status().get('percent', 'Unknown')}%
        
        Capabilities:
        - Programming and code development
        - Study guidance and education
        - System automation (open apps, check memory, screenshots)
        - Hardware interfacing
        
        You can control the computer. If asked to open an app, say you will do it.
        Provide helpful, accurate, and concise responses.
        """
        
        if context:
            base_prompt += f"\n\nAdditional context: {context}"
        
        return base_prompt
    
    def _add_to_history(self, role: str, content: str):
        """Add message to conversation history."""
        self.conversation_history.append({
            "role": role,
            "content": content
        })
        
        # Keep history size manageable
        max_history = self.context_memory * 2  # user + assistant pairs
        if len(self.conversation_history) > max_history:
            self.conversation_history = self.conversation_history[-max_history:]
    
    def clear_history(self):
        """Clear conversation history."""
        self.conversation_history = []
        logger.info("Conversation history cleared")
    
    def get_sentiment(self, text: str) -> Dict[str, float]:
        """Analyze sentiment of text."""
        try:
            from textblob import TextBlob
            blob = TextBlob(text)
            return {
                'polarity': blob.sentiment.polarity,
                'subjectivity': blob.sentiment.subjectivity
            }
        except Exception as e:
            logger.error(f"Sentiment analysis error: {e}")
            return {'polarity': 0.0, 'subjectivity': 0.0}
    
    def extract_intent(self, command: str) -> str:
        """Extract intent from command."""
        command_lower = command.lower()
        
        # Automation / System Control
        sys_pattern = re_module.compile(r'\b(open|close|run|execute|launch|screenshot|battery|memory|shutdown|restart)\b')
        if sys_pattern.search(command_lower):
            return 'system_control'
            
        # Code-related
        code_pattern = re_module.compile(r'\b(code|program|debug|error)\b')
        if code_pattern.search(command_lower):
            return 'code_help'
        
        # Study-related
        study_pattern = re_module.compile(r'\b(study|learn|explain|teach)\b')
        if study_pattern.search(command_lower):
            return 'study_help'
        
        # Hardware-related
        hw_pattern = re_module.compile(r'\b(arduino|sensor|pin|hardware)\b')
        if hw_pattern.search(command_lower):
            return 'hardware_control'
        
        # General conversation
        else:
            return 'general'
    
    def summarize_text(self, text: str, max_length: int = 150) -> str:
        """Summarize long text."""
        if len(text) <= max_length:
            return text
        
        try:
            # Use AI for summarization
            prompt = f"Summarize the following text in {max_length} characters or less:\n\n{text}"
            return self.process_command(prompt)
        except:
            return text[:max_length] + "..."
