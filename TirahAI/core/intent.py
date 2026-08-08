"""
Intent Classification Engine for TirahAi
Determines user intent from natural language using pattern matching and learning
"""

import re
from typing import Optional, Dict, List, Tuple


class IntentEngine:
    def __init__(self, memory=None):
        self.memory = memory

    INTENT_PATTERNS = {
        "time_date": [
            r"\b(time|date|today|what day|what time)\b",
            r"\b(current|now)\s.*\b(time|date|day|hour|minute)\b",
            r"\bwhat\s+(time|day|date)\s+is\s+it\b",
        ],
        "greeting": [
            r"\b(hello|hi|hey|greetings|good\s*(morning|afternoon|evening|day))\b",
            r"\bhowdy\b",
        ],
        "help": [
            r"\b(help|what can you do|capabilities|commands|options)\b",
            r"\b(how|what)\s.*\b(use|works|do)\b",
            r"\bshow\s+(me\s+)?(commands|help|tools)\b",
        ],
        "calculation": [
            r"\b(calculate|compute|solve|math)\b",
            r"^[0-9+\-*/().\s]+\s*[+\-*/]\s*[0-9+\-*/().\s]+$",
            r"\b(what\s.*\b(plus|minus|times|divided|multiplied|added|subtracted))\b",
        ],
        "memory_recall": [
            r"\b(remember|recall|what did)\s.*\b(i|we|you)\b",
            r"\b(earlier|before|previously|last time)\b",
            r"\bdo you remember\b",
        ],
        "vision_ocr": [
            r"\b(read|scan|ocr|recognize|extract)\s.*\b(image|picture|photo|document|pdf|file)\b",
            r"\b(extract|get|read)\s.*\b(text|words)\s.*\b(from|in)\b.*\b(image|picture|photo)\b",
            r"\b(ocr|optical character recognition)\b",
            r"\b(convert|turn)\s.*\b(image|picture|photo|scan)\s.*\b(to|into)\s.*\b(text)\b",
        ],
        "vision_enhance": [
            r"\b(enhance|improve|sharpen|adjust)\s.*\b(image|picture|photo)\b",
            r"\b(image|photo)\s.*\b(enhance|edit|adjust|fix)\b",
            r"\bfix\s.*\b(image|blurry|dark)\b",
        ],
        "study_explain": [
            r"\bexplain\b",
            r"\b(teach|learn)\s.*\b(me|about)\b",
            r"\bwhat\s.*\b(is|are|does|mean)\b",
            r"\b(define|describe|elaborate)\b",
            r"\bhow\s.*\b(does|do|can|to)\s.*\bwork\b",
        ],
        "study_quiz": [
            r"\b(quiz|test|exam|practice)\s.*\b(me|on|about)\b",
            r"\b(create|make|generate)\s.*\b(quiz|test|questions)\b",
            r"\bask\s.*\b(me|questions)\b",
        ],
        "study_flashcard": [
            r"\b(flashcard|flash card|card)\b",
            r"\b(create|make)\s.*\b(flashcard|study card)\b",
        ],
        "study_summarize": [
            r"\b(summarize|summary|sum up|in short)\b",
            r"\b(give|write|make)\s.*\b(summary|overview)\b",
        ],
        "study_plan": [
            r"\bstudy\s.*\b(plan|schedule|guide|path)\b",
            r"\b(how|where)\s.*\b(start|begin)\s.*\b(learning|studying)\b",
            r"\blearning\s.*\b(path|roadmap|plan)\b",
        ],
        "voice_listen": [
            r"\b(listen|hear|record)\b",
            r"\b(start|begin)\s.*\b(listening|recording)\b",
            r"\bwhat\s.*\b(did|does)\s.*\b(say|said)\b",
            r"\b(speech\s.*\b(to|2)\s.*\btext|stt)\b",
        ],
        "voice_speak": [
            r"\b(speak|say|tell|read)\s.*\b(out|loud|aloud)\b",
            r"\b(text\s.*\b(to|2)\s.*\bspeech|tts)\b",
            r"\b(announce|pronounce)\b",
        ],
        "code_analyze": [
            r"\b(analyze|review|check)\s.*\b(code|program|script)\b",
            r"\b(how)\s.*\b(code)\s.*\b(look|work)\b",
        ],
        "code_generate": [
            r"\b(generate|write|create|make)\s.*\b(code|function|program|script)\b",
            r"\b(write|code)\s.*\b(a|an|the)\s.*\b(function|program|class)\b",
        ],
        "code_debug": [
            r"\b(debug|fix|error|bug|issue)\s.*\b(code|program|error)\b",
            r"\b(why|what)\s.*\b(error|wrong|broken)\b",
            r"\bnot\s.*\bworking\b",
        ],
        "code_format": [
            r"\b(format|beautify|prettify|clean)\s.*\b(code)\b",
            r"\b(autopep8|black|prettier)\b",
        ],
        "code_convert": [
            r"\b(convert|translate|port)\s.*\b(code)\s.*\b(from|to)\b",
            r"\b(change|transform)\s.*\b(language)\b",
        ],
        "code_project": [
            r"\b(scan|browse|explore|look\s*at)\s.*\b(directory|folder|project|codebase|source)\b",
            r"\b(project|codebase)\s.*\b(structure|tree|files|layout)\b",
            r"\b(list|show)\s.*\b(all)\s.*\b(files|directories)\b",
            r"\b(find|locate|search)\s.*\b(file|class|function|code)\s.*\b(in)\s.*\b(project|directory)\b",
            r"\b(context|understand)\s.*\b(project|codebase)\b",
            r"\bgoogle.*(code|project|syntax|example|docs)\b",
        ],
        "hardware_control": [
            r"\b(arduino|sensor|pin|gpio)\b",
            r"\b(connect|read|write)\s.*\b(pin|port|device)\b",
            r"\b(hardware|device)\s.*\b(status|control|connect)\b",
        ],
        "hardware_camera": [
            r"\b(camera|webcam)\b",
            r"\b(take|capture)\s.*\b(photo|picture|image)\b",
            r"\b(record|start)\s.*\b(video)\b",
        ],
        "os_app": [
            r"\b(open|launch|start|run)\s.*\b(app|application|program|software)\b",
            r"\b(close|kill|quit|stop)\s.*\b(app|application|program)\b",
        ],
        "os_system": [
            r"\b(system|computer)\s.*\b(info|status|health)\b",
            r"\b(how|what)\s.*\b(battery|memory|cpu|disk)\b",
            r"\b(shutdown|restart)\s.*\b(computer|system|pc)\b",
            r"\b(screenshot|screen\s*capture)\b",
        ],
        "os_file": [
            r"\b(find|search|locate)\s.*\b(file|folder|document)\b",
            r"\b(list|show)\s.*\b(files|folders)\b",
            r"\b(create|make|delete|remove)\s.*\b(file|folder)\b",
        ],
        "os_clipboard": [
            r"\b(clipboard|copy|paste)\b",
            r"\b(copy|paste)\s.*\b(to|from)\s.*\b(clipboard)\b",
        ],
        "weather": [
            r"\b(weather|temperature|forecast)\b",
            r"\b(how|what)\s.*\b(cold|hot|warm|rain|snow|sunny)\b",
            r"\bis\s.*\b(raining|snowing|sunny|cloudy)\b",
        ],
        "email": [
            r"\b(email|mail|message|send)\s.*\b(to|send|compose|write)\b",
            r"\b(check|read|inbox)\s.*\b(email|mail)\b",
        ],
        "calendar": [
            r"\b(calendar|schedule|appointment|event)\b",
            r"\b(what|when|do)\s.*\b(today|tomorrow|this week)\b",
        ],
        "translate": [
            r"\b(translate|translation)\s.*\b(to|from|into)\b",
            r"\b(what|how)\s.*\b(say|write)\s.*\bin\b",
            r"\bconvert\s.*\b(to)\s.*\b(language)\b",
        ],
        "web_search": [
            r"\b(search|google|look up|find)\s.*\b(for|online|web|internet)\b",
            r"\b(search|browse)\s.*\b(the web|internet|online)\b",
        ],
        "web_browse": [
            r"\b(open|go to|navigate)\s.*\b(website|url|site|page|link)\b",
            r"\b(browse|visit)\s.*\b(site|web)\b",
        ],
        "reminder": [
            r"\b(remind|reminder|remember)\s.*\b(me|to|about)\b",
            r"\b(set|create|schedule)\s.*\b(reminder|alarm|notification)\b",
        ],
        "music": [
            r"\b(play|stop|pause|resume)\s.*\b(music|song|audio|sound)\b",
            r"\b(play|hear|listen)\s.*\b(song|track)\b",
        ],
        "agent_general": [
            r"\b(think|analyze|consider|evaluate)\b",
            r"\b(what|why|how|when|where|who)\s.*\?.+",
            r"\btell\s.*\b(me|about)\b",
        ],
    }

    def classify(self, command: str) -> Tuple[str, float]:
        command_lower = command.lower().strip()
        if not command_lower:
            return "general", 0.0

        scored = []
        for intent, patterns in self.INTENT_PATTERNS.items():
            for pattern in patterns:
                match = re.search(pattern, command_lower, re.IGNORECASE)
                if match:
                    matched_len = len(match.group())
                    confidence = min(1.0, matched_len / max(len(command_lower), 1) * 2.0)
                    scored.append((intent, confidence))
                    break

        if self.memory:
            suggestion = self.memory.suggest_intent(command)
            if suggestion:
                scored.append((suggestion, 0.4))

        if scored:
            best = max(scored, key=lambda x: x[1])
            if best[1] < 0.25:
                return "general", best[1]
            return best

        if len(command_lower.split()) <= 3:
            return "general", 0.3

        return "general", 0.2

    def get_all_intents(self) -> List[str]:
        return list(self.INTENT_PATTERNS.keys())

    def get_intent_description(self, intent: str) -> str:
        descriptions = {
            "vision_ocr": "Extract text from images and documents using OCR",
            "vision_enhance": "Enhance and adjust images",
            "study_explain": "Explain topics and concepts",
            "study_quiz": "Generate and take quizzes",
            "study_flashcard": "Create and review flashcards",
            "study_summarize": "Summarize text content",
            "study_plan": "Create study plans and learning paths",
            "voice_listen": "Listen and transcribe speech",
            "voice_speak": "Convert text to speech",
            "code_analyze": "Analyze code quality and structure",
            "code_generate": "Generate code from descriptions",
            "code_debug": "Debug code and fix errors",
            "code_format": "Format and beautify code",
            "code_convert": "Convert code between programming languages",
            "code_project": "Scan project directories and get AI-powered code assistance with context",
            "hardware_control": "Control hardware devices and sensors",
            "hardware_camera": "Control camera and capture images",
            "os_app": "Open and close applications",
            "os_system": "Get system info and control system",
            "os_file": "Manage files and folders",
            "os_clipboard": "Copy and paste from clipboard",
            "weather": "Get weather information and forecasts",
            "email": "Send and check emails",
            "calendar": "Manage calendar events and schedule",
            "translate": "Translate text between languages",
            "web_search": "Search the web for information",
            "web_browse": "Browse websites and pages",
            "reminder": "Set reminders and alarms",
            "time_date": "Check current time and date",
            "music": "Play and control music",
            "calculation": "Perform mathematical calculations",
            "greeting": "Greet and respond to greetings",
            "help": "Show help and available commands",
            "agent_general": "General conversation and analysis",
            "memory_recall": "Recall previous conversations and information",
        }
        return descriptions.get(intent, "Process general request")
