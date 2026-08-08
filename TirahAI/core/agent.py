"""
Agentic Model for TirahAi
Agent that processes commands, classifies intent, executes tools, and learns
Merges code, NLP, and system operations into a single intelligent agent
"""

import json
import re
import sys
from pathlib import Path
from typing import Optional, Dict, List, Any
from datetime import datetime
from utils.helpers import get_date, get_time

sys.path.insert(0, str(Path(__file__).parent.parent))

from core.memory import MemoryStore
from core.intent import IntentEngine
from core.tools import ToolRegistry
from config.settings import AI_CONFIG
from utils.logger import get_logger

logger = get_logger(__name__)

try:
    from textblob import TextBlob
except ImportError:
    TextBlob = None

try:
    import ollama as ollama_client
except ImportError:
    ollama_client = None


class AgenticModel:
    def __init__(self):
        self.memory = MemoryStore()
        self.intent_engine = IntentEngine(self.memory)
        self.tools = ToolRegistry()
        self._session_context: Dict[str, Any] = {}
        logger.info("AgenticModel initialized")

    def process(self, command: str, context: Optional[str] = None, session_id: Optional[str] = None) -> Dict:
        try:
            self.memory.add_to_short_term("user", command, {"type": "command"})
            intent, confidence = self.intent_engine.classify(command)
            self.memory.learn_pattern(command, intent)

            result = self._route_to_intent(command, intent, confidence, context)
            response_text = result.get("response", "")
            tool_results = result.get("tool_results", [])

            self.memory.add_to_short_term("assistant", response_text, {"type": "response", "intent": intent})
            self.memory.learn_route(command, intent, result.get("success", False))

            return {
                "response": response_text,
                "intent": intent,
                "confidence": round(confidence, 2),
                "tool_results": tool_results,
                "session_id": self.memory.session_id,
                "success": result.get("success", True),
                "timestamp": datetime.now().isoformat(),
            }
        except Exception as e:
            logger.error(f"Agent process error: {e}")
            return {
                "response": f"I encountered an error: {str(e)}",
                "intent": "error",
                "confidence": 0.0,
                "tool_results": [],
                "session_id": self.memory.session_id,
                "success": False,
                "timestamp": datetime.now().isoformat(),
            }

    def _route_to_intent(self, command: str, intent: str, confidence: float, context: Optional[str]) -> Dict:
        routes = {
            "vision_ocr": self._handle_vision_ocr,
            "vision_enhance": self._handle_vision_enhance,
            "study_explain": self._handle_study_explain,
            "study_quiz": self._handle_study_quiz,
            "study_flashcard": self._handle_study_flashcard,
            "study_summarize": self._handle_study_summarize,
            "study_plan": self._handle_study_plan,
            "voice_listen": self._handle_voice_listen,
            "voice_speak": self._handle_voice_speak,
            "code_analyze": self._handle_code_analyze,
            "code_generate": self._handle_code_generate,
            "code_debug": self._handle_code_debug,
            "code_format": self._handle_code_format,
            "code_convert": self._handle_code_convert,
            "code_project": self._handle_code_project,
            "hardware_control": self._handle_hardware_control,
            "hardware_camera": self._handle_hardware_camera,
            "os_app": self._handle_os_app,
            "os_system": self._handle_os_system,
            "os_file": self._handle_os_file,
            "os_clipboard": self._handle_os_clipboard,
            "weather": self._handle_weather,
            "email": self._handle_email,
            "calendar": self._handle_calendar,
            "translate": self._handle_translate,
            "web_search": self._handle_web_search,
            "web_browse": self._handle_web_browse,
            "reminder": self._handle_reminder,
            "time_date": self._handle_time_date,
            "music": self._handle_music,
            "calculation": self._handle_calculation,
            "greeting": self._handle_greeting,
            "help": self._handle_help,
            "memory_recall": self._handle_memory_recall,
            "general": self._handle_general,
        }

        handler = routes.get(intent, self._handle_general)
        try:
            return handler(command, context)
        except Exception as e:
            logger.error(f"Handler error for intent '{intent}': {e}")
            return self._fallback_response(command)

    def _execute_tools(self, tool_names: List[str], tool_args: Optional[List[Dict]] = None) -> List[Dict]:
        results = []
        for i, name in enumerate(tool_names):
            kwargs = tool_args[i] if tool_args and i < len(tool_args) else {}
            result = self.tools.execute(name, **kwargs)
            results.append(result)
        return results

    def _build_response(self, text: str, tools: Optional[List[str]] = None, tool_args: Optional[List[Dict]] = None) -> Dict:
        tool_results = self._execute_tools(tools or [], tool_args or []) if tools else []
        return {"response": text, "tool_results": tool_results, "success": True}

    def _handle_vision_ocr(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can extract text from images and documents. Upload an image or PDF via the Vision panel in the dashboard, and I'll OCR it for you.", ["get_help"])

    def _handle_vision_enhance(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("To enhance an image, upload it via the Vision panel in the dashboard. I can adjust brightness, contrast, sharpen, and more.")

    def _handle_study_explain(self, command: str, context: Optional[str]) -> Dict:
        topic = command.replace("explain", "").replace("what is", "").replace("what are", "").replace("define", "").replace("me", "").replace("about", "").strip()
        topic = topic.strip("?'\".!")
        if not topic or len(topic) < 3:
            return self._build_response("What topic would you like me to explain? Ask me about any subject, concept, or idea.")
        try:
            from modules.study_guide import StudyGuide
            sg = StudyGuide()
            explanation = sg.explain_topic(topic)
            if "offline" in str(explanation).lower() or "couldn't process" in str(explanation).lower():
                raise ValueError("offline fallback")
            return {"response": explanation, "tool_results": [], "success": True}
        except Exception:
            known = {
                "gravity": "Gravity is a fundamental force of nature that attracts objects with mass toward one another. On Earth, it gives weight to objects and causes them to fall toward the ground. It's described by Einstein's General Theory of Relativity as the curvature of spacetime caused by mass and energy. The strength of gravity depends on the mass of objects and the distance between them — larger masses and shorter distances produce stronger gravitational pull.",
                "photosynthesis": "Photosynthesis is the process by which green plants, algae, and some bacteria convert light energy from the sun into chemical energy in the form of glucose. This occurs in chloroplasts using chlorophyll (the green pigment). The basic equation is: 6CO₂ + 6H₂O + light → C₆H₁₂O₆ + 6O₂. In simple terms, plants take in carbon dioxide and water, use sunlight to convert them into sugar (food) and release oxygen as a byproduct.",
                "quantum computing": "Quantum computing uses quantum mechanical phenomena like superposition and entanglement to process information. Unlike classical bits (0 or 1), quantum bits (qubits) can exist in multiple states simultaneously. This enables quantum computers to solve certain problems exponentially faster than classical computers for tasks like factoring large numbers, simulating molecules, and optimization problems.",
                "machine learning": "Machine learning is a subset of artificial intelligence where systems learn and improve from experience without being explicitly programmed. The three main types are: supervised learning (trained on labeled data), unsupervised learning (finds patterns in unlabeled data), and reinforcement learning (learns through trial and error with rewards). Common algorithms include linear regression, decision trees, neural networks, and support vector machines.",
                "python": "Python is a high-level, interpreted programming language created by Guido van Rossum in 1991. It emphasizes code readability with significant whitespace. Python supports multiple programming paradigms including procedural, object-oriented, and functional programming. It's widely used for web development (Django, Flask), data science (pandas, NumPy), AI/ML (TensorFlow, PyTorch), and automation. Its extensive standard library earned it the motto 'batteries included'.",
                "dna": "DNA (Deoxyribonucleic Acid) is a molecule that carries genetic instructions for the development, functioning, growth, and reproduction of all known living organisms. It has a double-helix structure, discovered by Watson and Crick in 1953. DNA is made of four nucleotide bases: adenine (A), thymine (T), cytosine (C), and guanine (G), which pair A-T and C-G. Genes are segments of DNA that code for specific proteins.",
                "blockchain": "Blockchain is a distributed ledger technology where data is stored in blocks that are cryptographically linked in a chain. Each block contains a timestamp, transaction data, and a reference (hash) to the previous block. This creates an immutable record resistant to modification. It's best known as the foundation for cryptocurrencies like Bitcoin, but also has applications in supply chain, voting systems, and digital identity management.",
            }
            key = topic.lower().strip()
            if key in known:
                return {"response": known[key], "tool_results": [], "success": True}
            fuzzy = [k for k in known if k in key or key in k]
            if fuzzy:
                return {"response": known[fuzzy[0]], "tool_results": [], "success": True}
            return self._build_response(f"I can explain **{topic}**! Try asking in the Study panel of the dashboard for a full explanation with examples, references, and further reading. Or ask me about gravity, photosynthesis, quantum computing, machine learning, Python, DNA, or blockchain.")

    def _handle_study_quiz(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can generate quizzes on any topic. Go to the Study panel in the dashboard to create a custom quiz with flashcards and multiple-choice questions.")

    def _handle_study_flashcard(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("Use the Study panel in the dashboard to create and review flashcards. I can generate flashcards on any topic for effective learning.")

    def _handle_study_summarize(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can summarize text for you. Paste the text you want summarized, or use the Study panel in the dashboard for comprehensive summarization with key points extraction.")

    def _handle_study_plan(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I'll help create a personalized study plan. Tell me what subject you want to learn and for how long, or use the Study panel in the dashboard.")

    def _handle_voice_listen(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("Voice listening is active on the dashboard main page. Click the microphone button or say 'Hey Tirah' to interact with me using your voice.")

    def _handle_voice_speak(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("Text-to-speech is available. Type what you want me to say in the dashboard and I'll speak it out loud.")

    def _handle_code_analyze(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can analyze your code for errors, complexity, and best practices. Paste your code in the Code panel of the dashboard for analysis.")

    def _handle_code_generate(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("Tell me what code you need and in which language, and I'll generate it for you. Use the Code panel in the dashboard for code generation with syntax highlighting.")

    def _handle_code_debug(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can help debug your code. Share the code and the error message you're seeing, and I'll help find the issue.")

    def _handle_code_format(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can format and beautify your code. Paste it in the Code panel of the dashboard for auto-formatting.")

    def _handle_code_convert(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can convert code between programming languages. Tell me the source and target languages and paste your code in the Code panel.")

    def _handle_code_project(self, command: str, context: Optional[str]) -> Dict:
        import os as _os
        import re as _re
        use_research = "google" in command.lower() or "research" in command.lower() or "search" in command.lower()
        path_fmt = r'(?:directory|folder|project|path)\s+["\']?([^\s,;]+)["\']?'
        path_match = _re.search(path_fmt, command, _re.IGNORECASE)
        scan_path = path_match.group(1) if path_match else _os.getcwd()
        try:
            from modules.project_context import ProjectContext
            pc = ProjectContext()
            pc.scan_directory(scan_path)
            query = _re.sub(r'(scan|browse|explore|look at|code_project|google|research|search)\s*(?:directory|folder|project|in|from|path\s+\S+)?\s*', '', command, flags=_re.IGNORECASE).strip()
            if not query or len(query) < 3:
                tree_str = pc.get_tree_string()
                return {"response": f"📁 **Project Structure:**\n```\n{tree_str[:2000]}\n```\nFound {len(pc._file_index or {})} files. Ask me a specific question about this project to get AI-powered help.", "tool_results": [], "success": True}
            ctx = pc.build_context_prompt(query)
            resp = f"**Relevant files for '{query}':**\n"
            if ctx["relevant_files"]:
                for f in ctx["relevant_files"]:
                    resp += f"- `{f}`\n"
            else:
                resp += "No directly matching files found.\n"
            resp += f"\n**Project Tree:**\n```\n{ctx['project_tree'][:800]}\n```"
            if use_research:
                research = pc.search_google_with_context(query, ctx["context"])
                resp += f"\n\n**Research:**\n{research}"
            gemini_result = pc.query_gemini(f"Based on this project, answer the query:\n\n{ctx['context']}\n\nQuery: {query}")
            if gemini_result:
                resp += f"\n\n**AI Analysis:**\n{gemini_result}"
            return {"response": resp, "tool_results": [], "success": True}
        except Exception as e:
            logger.error(f"Project context error: {e}")
            return self._build_response(f"I encountered an error scanning the project: {str(e)}. Make sure the path exists and is readable.")

    def _handle_hardware_control(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can interface with hardware devices including Arduino, sensors, and other I/O devices. Use the Hardware panel in the dashboard to configure and control devices.")

    def _handle_hardware_camera(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("Camera control is available. Use the Hardware panel in the dashboard to capture images, record video, or do object detection.")

    def _handle_os_app(self, command: str, context: Optional[str]) -> Dict:
        app_name = self._extract_app_name(command)
        if app_name:
            result = self.tools.execute("open_application", app_name=app_name)
            if result.get("success"):
                return {"response": f"Opening {app_name}...", "tool_results": [result], "success": True}
        return self._build_response("What application would you like me to open? I can open apps like notepad, calculator, chrome, and more.", ["get_system_info"])

    def _handle_os_system(self, command: str, context: Optional[str]) -> Dict:
        if "battery" in command.lower():
            result = self.tools.execute("get_battery")
        elif "memory" in command.lower() or "ram" in command.lower() or "cpu" in command.lower():
            result = self.tools.execute("get_system_info")
        else:
            result = self.tools.execute("get_system_info")
        if result.get("success"):
            info = result.get("result", {})
            if "percent" in info:
                plugged = " (plugged in)" if info.get("plugged_in") else " (on battery)"
                text = f"Battery is at {info['percent']}%{plugged}"
            elif "memory" in info:
                mem = info["memory"]
                text = f"System: {info.get('os', 'Unknown')} | CPU: {info.get('cpu_percent', '?')}% | RAM: {mem.get('used_gb', '?')}GB/{mem.get('total_gb', '?')}GB ({mem.get('percent', '?')}%)"
            else:
                text = f"System info: {json.dumps(info, indent=2)}" if isinstance(info, dict) else str(info)
            return {"response": text, "tool_results": [result], "success": True}
        return self._build_response("System information is available in the System panel of the dashboard.", ["get_system_info"])

    def _handle_os_file(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("File management is available. Use the System panel in the dashboard to browse, search, and manage files.")

    def _handle_os_clipboard(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("Clipboard operations are available for managing copied text and content.")

    def _handle_weather(self, command: str, context: Optional[str]) -> Dict:
        location = self._extract_location(command) or "your area"
        result = self.tools.execute("weather", location=location)
        if result.get("success") and result.get("result", {}).get("weather"):
            weather = result["result"]["weather"]
            return {"response": f"Weather for {location}: {weather}", "tool_results": [result], "success": True}
        return self._build_response(f"I can check the weather. To get accurate forecasts, go to the Dashboard and configure a Weather API key in settings.", ["weather"])

    def _handle_email(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("Email integration is available. Configure your email settings in the Dashboard to send and receive emails through me.")

    def _handle_calendar(self, command: str, context: Optional[str]) -> Dict:
        today = get_date()
        return self._build_response(f"Calendar management is available. Today is {today}. Configure your calendar in the Dashboard to manage events and appointments.")

    def _handle_translate(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("Translation service is available. Use the Tools panel in the dashboard to translate text between languages.")

    def _handle_web_search(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can help search the web. Tell me what to search for, or use the browser to look up information online.")

    def _handle_web_browse(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can open websites for you. Tell me which site you'd like to visit.")

    def _handle_reminder(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("I can set reminders for you. Tell me what to remind you about and when, or use the Tools panel in the dashboard.")

    def _handle_time_date(self, command: str, context: Optional[str]) -> Dict:
        time_result = self.tools.execute("get_time")
        date_result = self.tools.execute("get_date")
        time_str = time_result.get("result", {}).get("formatted", "")
        date_str = date_result.get("result", {}).get("formatted", "")
        return {"response": f"{date_str}. {time_str}.", "tool_results": [time_result, date_result], "success": True}

    def _handle_music(self, command: str, context: Optional[str]) -> Dict:
        return self._build_response("Music playback is available. Tell me what song or artist you'd like to hear.")

    def _handle_calculation(self, command: str, context: Optional[str]) -> Dict:
        expr = re.sub(r'[^0-9+\-*/().%\s]', '', command.replace('x', '*').replace('×', '*').replace('÷', '/'))
        expr = expr.strip()
        if expr:
            result = self.tools.execute("calculate", expression=expr)
            if result.get("success"):
                calc = result["result"]
                return {"response": f"The result is {calc.get('result')}", "tool_results": [result], "success": True}
        return self._build_response("I can perform calculations. Just tell me what to calculate, like '2 + 2' or 'calculate 15 * 3'.")

    def _handle_greeting(self, command: str, context: Optional[str]) -> Dict:
        hour = datetime.now().hour
        greeting = "Good morning" if hour < 12 else "Good afternoon" if hour < 17 else "Good evening"
        from config.settings import GUI_CONFIG
        name = GUI_CONFIG.get("user_name", "there")
        return {"response": f"{greeting}, {name}! I'm TirahAi, your AI assistant. How can I help you today?", "tool_results": [self.tools.execute("get_time")], "success": True}

    def _handle_help(self, command: str, context: Optional[str]) -> Dict:
        tool_result = self.tools.execute("get_help")
        help_text = (
            "I'm TirahAi, your intelligent assistant. Here's what I can do:\n\n"
            "🎯 **Vision** - Extract text from images/documents, enhance photos\n"
            "📚 **Study** - Explain topics, create quizzes/flashcards, summarize\n"
            "💻 **Code** - Analyze, generate, debug, format, and convert code\n"
            "🎙️ **Voice** - Listen to speech, convert text to speech\n"
            "🔧 **Hardware** - Control Arduino, sensors, cameras\n"
            "🖥️ **System** - Open apps, get system info, manage files\n"
            "🌤️ **Utilities** - Weather, calendar, email, translate, calculate\n"
            "🧠 **Memory** - Remember preferences, recall past conversations\n\n"
            "Use the Dashboard panels for detailed control, or just talk to me naturally!"
        )
        return {"response": help_text, "tool_results": [tool_result], "success": True}

    def _handle_memory_recall(self, command: str, context: Optional[str]) -> Dict:
        context_text = self.memory.get_context_text(5)
        if context_text.strip():
            return {"response": f"Here's what we've been discussing:\n\n{context_text}", "tool_results": [], "success": True}
        return {"response": "I don't have any previous conversation in this session. Let me check long-term memory...", "tool_results": [self.tools.execute("search_memory", query=command)], "success": True}

    def _handle_general(self, command: str, context: Optional[str]) -> Dict:
        context_text = self.memory.get_context_text(3)
        sentiment = self._analyze_sentiment(command)
        response = self._generate_fallback_response(command, context_text, sentiment)
        return {"response": response, "tool_results": [], "success": True}

    def _fallback_response(self, command: str) -> Dict:
        return self._build_response(self._generate_fallback_response(command, "", {}))

    def _generate_fallback_response(self, command: str, context: str, sentiment: Optional[Dict] = None) -> str:
        command_lower = command.lower()

        if "who are you" in command_lower or "what are you" in command_lower:
            return "I am TirahAi, an intelligent agentic AI assistant. I can help with vision processing, study help, code analysis, voice commands, hardware control, system automation, and more. I learn from our conversations to serve you better."

        if "thank" in command_lower:
            return "You're welcome! I'm here whenever you need me. Is there anything else I can help with?"

        if any(w in command_lower for w in ["good", "great", "nice", "awesome", "cool"]):
            return "I'm glad you think so! Let me know what you'd like to do next."

        if "how are you" in command_lower:
            return "I'm functioning optimally and ready to assist! How can I help you today?"

        try:
            if TextBlob:
                blob = TextBlob(command)
                if blob.sentiment.polarity < -0.3:
                    return "I sense you might be frustrated. Let me know what's wrong and I'll do my best to help resolve it."
        except:
            pass

        import re, urllib.request, urllib.parse, json
        search = re.sub(r'\b(tell me about|what is|who is|what are|explain|describe|define|meaning of|about)\b', '', command, flags=re.IGNORECASE).strip()
        if not search:
            search = command
        search = search[:200]

        def _wiki_search(term: str):
            url = f"https://en.wikipedia.org/w/api.php?action=opensearch&search={urllib.parse.quote(term)}&limit=1&format=json"
            req = urllib.request.Request(url, headers={"User-Agent": "TirahAi/1.0"})
            with urllib.request.urlopen(req, timeout=5) as resp:
                return json.loads(resp.read().decode())

        try:
            data = _wiki_search(search)
            if not (len(data) > 1 and data[1]):
                data = _wiki_search(term.split()[-1] if (term := search.split()) else search)
            if len(data) > 1 and data[1] and len(data) > 3 and data[3]:
                title = data[1][0]
                url = data[3][0] if len(data[3]) > 0 else ""
                summary_url = f"https://en.wikipedia.org/api/rest_v1/page/summary/{urllib.parse.quote(title)}"
                req2 = urllib.request.Request(summary_url, headers={"User-Agent": "TirahAi/1.0"})
                with urllib.request.urlopen(req2, timeout=5) as resp2:
                    page_data = json.loads(resp2.read().decode())
                    extract = page_data.get("extract", "")
                    if extract:
                        short = ". ".join(extract.split(". ")[:5]) + "."
                        result = f"Based on my research, here's what I found about that:\n\n{short}"
                        if url:
                            result += f"\n\n🔗 [Learn more on Wikipedia]({url})"
                        return result
        except Exception:
            pass

        return f"I understand you want help with: '{command}'. I can assist with vision OCR, study help, code analysis, voice commands, system control, and more. Please use the Dashboard panels or ask me more specifically what you need."

    def _analyze_sentiment(self, text: str) -> Optional[Dict]:
        if TextBlob:
            try:
                blob = TextBlob(text)
                return {"polarity": blob.sentiment.polarity, "subjectivity": blob.sentiment.subjectivity}
            except:
                pass
        return None

    def _extract_app_name(self, command: str) -> Optional[str]:
        command_lower = command.lower()
        apps = ["notepad", "calculator", "paint", "chrome", "edge", "firefox", "explorer", "cmd", "powershell", "word", "excel", "terminal"]
        for app in apps:
            if app in command_lower:
                return app
        for prefix in ["open ", "launch ", "start ", "run "]:
            if prefix in command_lower:
                name = command_lower.split(prefix, 1)[-1].strip().split()[0] if command_lower.split(prefix, 1)[-1].strip() else ""
                if name:
                    return name
        return None

    def _extract_location(self, command: str) -> Optional[str]:
        for prefix in ["in ", "for ", "at ", "weather in ", "weather for "]:
            idx = command.lower().find(prefix)
            if idx >= 0:
                location = command[idx + len(prefix):].strip().rstrip(".?!,").strip()
                if location and len(location) < 50:
                    return location
        return None

    def get_status(self) -> Dict:
        mem_stats = self.memory.get_stats()
        tools_count = len(self.tools.tools)
        intents_count = len(self.intent_engine.get_all_intents())
        return {
            "status": "active",
            "session_id": self.memory.session_id,
            "memory": mem_stats,
            "tools_available": tools_count,
            "intents_recognized": intents_count,
            "accuracy": self.memory.get_accuracy_stats(),
            "route_stats": self.memory.get_route_stats(),
        }

    def get_available_intents(self) -> List[Dict]:
        return [
            {"intent": intent, "description": self.intent_engine.get_intent_description(intent)}
            for intent in self.intent_engine.get_all_intents()
        ]

    def get_available_tools(self) -> List[Dict]:
        return self.tools.get_tools()

    def feedback(self, command: str, response: str, rating: int, intent: str):
        self.memory.learn_from_feedback(command, response, rating, intent)

    def reset_session(self):
        self.memory.clear_session()
        self._session_context = {}


