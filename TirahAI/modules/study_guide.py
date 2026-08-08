"""
Study Guide Module for TirahAi
Enhanced with Wikipedia integration, formatted responses, quiz intelligence, and AI-powered flashcards
"""

import random
import re
import json
import urllib.request
import urllib.parse
import urllib.error
from typing import List, Dict, Optional, Tuple
from datetime import datetime, timedelta
from utils.logger import get_logger
from config.settings import STUDY_CONFIG

logger = get_logger(__name__)

WIKI_API = "https://en.wikipedia.org/api/rest_v1/page/summary/{topic}"
WIKI_SEARCH = "https://en.wikipedia.org/w/api.php?action=opensearch&search={query}&limit=3&format=json"
OPEN_LIBRARY = "https://openlibrary.org/search.json?q={query}&limit=3"


def _fetch_json(url: str) -> Optional[dict]:
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "TirahAi/1.0"})
        with urllib.request.urlopen(req, timeout=8) as resp:
            return json.loads(resp.read().decode())
    except Exception:
        return None


def _fetch_text(url: str) -> Optional[str]:
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "TirahAi/1.0"})
        with urllib.request.urlopen(req, timeout=8) as resp:
            return resp.read().decode()
    except Exception:
        return None


class StudyGuide:
    def __init__(self):
        self.subjects = STUDY_CONFIG.get('subjects', ['math', 'science', 'history', 'literature', 'programming'])
        self.current_subject = None
        self.quiz_history = []
        logger.info("Study Guide initialized")

    # ------------------------------------------------------------------
    # WIKIPEDIA / KNOWLEDGE SOURCES
    # ------------------------------------------------------------------
    def _wiki_summary(self, topic: str, sentences: int = 6) -> Optional[Dict]:
        url = WIKI_API.format(topic=urllib.parse.quote(topic))
        data = _fetch_json(url)
        if data and "extract" in data:
            extract = data["extract"]
            if sentences > 0:
                parts = extract.split(". ")
                extract = ". ".join(parts[:sentences]) + ("." if len(parts) > sentences else "")
            return {
                "title": data.get("title", topic),
                "extract": extract,
                "url": data.get("content_urls", {}).get("desktop", {}).get("page", f"https://en.wikipedia.org/wiki/{urllib.parse.quote(topic)}"),
                "thumbnail": data.get("thumbnail", {}).get("source") if data.get("thumbnail") else None,
            }
        search_url = WIKI_SEARCH.format(query=urllib.parse.quote(topic))
        search_data = _fetch_json(search_url)
        if search_data and len(search_data) > 1 and search_data[1]:
            first_result = search_data[1][0]
            return self._wiki_summary(first_result, sentences)
        return None

    def _openlibrary_resources(self, topic: str) -> List[Dict]:
        url = OPEN_LIBRARY.format(query=urllib.parse.quote(topic))
        data = _fetch_json(url)
        books = []
        if data and "docs" in data:
            for doc in data["docs"][:3]:
                if doc.get("title"):
                    books.append({
                        "title": doc["title"],
                        "author": ", ".join(doc.get("author_name", [])),
                        "year": doc.get("first_publish_year"),
                        "key": doc.get("key"),
                    })
        return books

    # ------------------------------------------------------------------
    # EXPLAIN TOPIC
    # ------------------------------------------------------------------
    def explain_topic(self, topic: str, detail_level: str = 'detailed') -> str:
        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()
            wiki = self._wiki_summary(topic, sentences=8 if detail_level == "comprehensive" else 5)
            books = self._openlibrary_resources(topic)

            depth = {
                'brief': 'a brief overview',
                'detailed': 'a thorough explanation',
                'comprehensive': 'an in-depth comprehensive explanation with examples, context, and analysis'
            }

            prompt = f"""Provide {depth.get(detail_level, 'a thorough explanation')} of: {topic}

Structure your response with these sections when appropriate:
## Overview
## Key Concepts
## {detail_level.capitalize()} Explanation
## Examples / Applications
## Key Takeaways

Use clear formatting with bullet points and paragraphs.
Include relevant dates, people, and context."""

            explanation = brain.process_command(prompt)

            if wiki:
                explanation += f"""

---
📚 **Wikipedia Reference**
{wiki['extract']}

🔗 [{wiki['title']}]({wiki['url']})"""

            if books:
                explanation += f"""

📖 **Suggested Reading**
"""
                for b in books:
                    line = f"- **{b['title']}**"
                    if b.get('author'):
                        line += f" by {b['author']}"
                    if b.get('year'):
                        line += f" ({b['year']})"
                    explanation += line + "\n"

            return explanation

        except Exception as e:
            logger.error(f"Explanation error: {e}")
            wiki = self._wiki_summary(topic, sentences=6)
            if wiki:
                return f"""## {wiki['title']}

{wiki['extract']}

🔗 [Read more on Wikipedia]({wiki['url']})"""
            return f"Unable to explain '{topic}'. Try rephrasing or check the Study panel in the dashboard."

    # ------------------------------------------------------------------
    # QUIZ
    # ------------------------------------------------------------------
    def create_quiz(self, topic: str, num_questions: int = None) -> List[Dict]:
        if num_questions is None:
            num_questions = STUDY_CONFIG.get('quiz_question_count', 5)

        wiki = self._wiki_summary(topic, sentences=10)
        context = wiki["extract"] if wiki else topic

        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()

            prompt = f"""Based on this information about {topic}:

{context}

Create {num_questions} thoughtful multiple-choice quiz questions that test DEEP understanding, not just recall.

For EACH question:
1. First identify an important concept or relationship
2. Then craft a question that requires reasoning
3. Create 4 plausible options where wrong answers reflect common misconceptions

Format each question EXACTLY as:
Q: [question]
A) [option]
B) [option]
C) [option]
D) [option]
Correct: [letter]
Explanation: [why this answer is right and what the wrong answers confuse]"""

            response = brain.process_command(prompt)
            quiz = self._parse_quiz_response(response, topic)

            if not quiz and wiki:
                quiz = self._fallback_quiz(topic, wiki["extract"], num_questions)

            if quiz:
                self.quiz_history.append({'topic': topic, 'questions': quiz, 'score': None})

            return quiz

        except Exception as e:
            logger.error(f"Quiz creation error: {e}")
            if wiki:
                return self._fallback_quiz(topic, wiki["extract"], num_questions)
            return []

    def _fallback_quiz(self, topic: str, context: str, count: int) -> List[Dict]:
        sentences = [s.strip() for s in context.split(".") if len(s.strip()) > 30]
        random.shuffle(sentences)
        quiz = []
        for i, sentence in enumerate(sentences[:count]):
            words = sentence.split()
            if len(words) < 6:
                continue
            key_word = random.choice([w for w in words if len(w) > 4 and w[0].isalpha()])
            question = sentence.replace(key_word, "_____", 1)
            options = [key_word]
            distractors = [w for w in words if w.lower() != key_word.lower() and len(w) > 3]
            random.shuffle(distractors)
            for d in distractors[:3]:
                options.append(d)
            while len(options) < 4:
                options.append("None of the above")
            random.shuffle(options)
            correct_letter = chr(65 + options.index(key_word))
            quiz.append({
                'question': f"Complete: {question}",
                'options': [f"{chr(65+i)}) {o}" for i, o in enumerate(options)],
                'correct': correct_letter,
                'explanation': f"The correct answer is '{key_word}' in the context of {topic}.",
            })
        return quiz

    def _parse_quiz_response(self, response: str, topic: str = "") -> List[Dict]:
        quiz = []
        current = {}
        for line in response.split("\n"):
            line = line.strip()
            if not line:
                continue
            if line.startswith("Q:"):
                if current and "question" in current and "options" in current:
                    quiz.append(current)
                current = {"question": line[2:].strip(), "options": [], "correct": None, "explanation": ""}
            elif line.startswith(("A)", "B)", "C)", "D)")):
                if "options" in current:
                    current["options"].append(line)
            elif line.startswith("Correct:"):
                current["correct"] = line.split(":")[1].strip()
            elif line.startswith("Explanation:"):
                current["explanation"] = line.split(":", 1)[1].strip()
        if current and "question" in current and "options" in current:
            quiz.append(current)
        return quiz

    def check_answer(self, question_index: int, user_answer: str) -> Dict:
        if not self.quiz_history:
            return {"error": "No active quiz"}
        current_quiz = self.quiz_history[-1]["questions"]
        if question_index >= len(current_quiz):
            return {"error": "Invalid question index"}
        question = current_quiz[question_index]
        correct = question.get("correct", "")
        return {
            "correct": user_answer.strip().upper() == correct.strip().upper(),
            "user_answer": user_answer,
            "correct_answer": correct,
            "explanation": question.get("explanation", ""),
        }

    # ------------------------------------------------------------------
    # FLASHCARDS
    # ------------------------------------------------------------------
    def create_flashcards(self, topic: str, num_cards: int = 10) -> List[Dict]:
        wiki = self._wiki_summary(topic, sentences=12)
        context = wiki["extract"] if wiki else topic

        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()
            has_ai = (
                getattr(brain, 'ollama_model', None)
                or (brain.primary_model == "gemini" and getattr(brain, 'gemini_model', None))
                or (brain.primary_model == "openai" and getattr(brain, 'openai_client', None))
            )
            if has_ai:
                cards = self._generate_flashcards_ai(topic, context, num_cards, brain)
                if cards:
                    return cards
        except Exception as e:
            logger.error(f"Flashcard AI unavailable: {e}")

        return self._generate_flashcards_offline(topic, context, num_cards)

    def _generate_flashcards_ai(self, topic: str, context: str, count: int, brain) -> List[Dict]:
        prompt = f"""Based on this information about {topic}:

{context}

Create {count} high-quality flashcards. Each should have a clear question/term on the front and a precise answer/definition on the back. Cover key concepts, terminology, relationships, and important facts.

Format EXACTLY as:
Front: [question or term]
Back: [answer or definition]"""
        try:
            response = brain.process_command(prompt)
            cards = []
            current = {}
            for line in response.split("\n"):
                line = line.strip()
                if line.startswith("Front:"):
                    if current and "front" in current and "back" in current:
                        cards.append(current)
                    current = {"front": line[6:].strip(), "back": ""}
                elif line.startswith("Back:") and current:
                    current["back"] = line[5:].strip()
            if current and "front" in current and "back" in current:
                cards.append(current)
            return cards[:count]
        except Exception:
            return self._generate_flashcards_offline(topic, context, count)

    def _generate_flashcards_offline(self, topic: str, context: str, count: int) -> List[Dict]:
        sentences = [s.strip() for s in re.split(r'[.!?\n]', context) if len(s.strip()) > 20]
        random.shuffle(sentences)
        cards = []
        for sentence in sentences[:count]:
            words = sentence.split()
            if len(words) < 5:
                continue
            key_terms = [w for w in words if len(w) > 4 and w[0].isalpha() and w.lower() not in ("about", "other", "there", "which", "their", "these")]
            if not key_terms:
                term = words[min(3, len(words) - 1)]
            else:
                term = random.choice(key_terms)
            definition = sentence.strip()
            if len(definition) > 120:
                definition = definition[:117] + "..."
            cards.append({"front": f"What is {term}?", "back": definition})
        if not cards:
            cards.append({
                "front": f"What should I remember about {topic}?",
                "back": f"Review the definition, main ideas, examples, and common questions about {topic}.",
            })
        return cards[:count]

    # ------------------------------------------------------------------
    # STUDY PLAN
    # ------------------------------------------------------------------
    def generate_study_plan(self, subject: str, duration_days: int = 7) -> str:
        wiki = self._wiki_summary(subject, sentences=6)

        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()

            today = datetime.now()
            context = wiki["extract"] if wiki else subject
            subtopics = self._extract_subtopics(context) if wiki else []

            prompt = f"""Create a {duration_days}-day study plan for learning about: {subject}

{"Key subtopics to cover: " + ", ".join(subtopics) if subtopics else ""}

For each day provide:
## Day [N] — [Date]
### Topic
### Time Required
### Learning Objectives
### Activities (reading, practice, review)
### Resources

Rules:
- Each day builds on the previous
- Include review sessions every 3 days
- Total weekly commitment should be reasonable (1-2 hours per day)
- Include practical exercises or application
- End with a capstone or summary activity
- Make the plan specific to {subject}"""

            plan = brain.process_command(prompt)

            if wiki:
                plan += f"""

---
📚 **Reference**: [{wiki['title']}]({wiki['url']})"""

            return plan

        except Exception as e:
            logger.error(f"Study plan error: {e}")
            return self._fallback_study_plan(subject, duration_days, wiki)

    def _fallback_study_plan(self, subject: str, days: int, wiki: Optional[Dict] = None) -> str:
        today = datetime.now()
        plan = [f"# {days}-Day Study Plan: {subject}", ""]

        subtopics = []
        if wiki:
            text = wiki["extract"]
            subtopics = self._extract_subtopics(text)
        if not subtopics:
            subtopics = [f"Introduction to {subject}", f"Core Concepts of {subject}", f"Advanced {subject}", f"Practical Applications", f"Review & Mastery"]

        for i in range(days):
            d = today + timedelta(days=i)
            date_str = d.strftime("%a, %b %d")
            sub = subtopics[i % len(subtopics)]
            hours = random.choice(["45-60 min", "1 hour", "1.5 hours", "30-45 min"])
            focus = ["Read & understand core concepts", "Practice with examples", "Review previous material", "Apply to real-world problems", "Take summary notes", "Self-assessment quiz"][i % 6]
            plan.append(f"## Day {i+1} — {date_str}")
            plan.append(f"- **Topic**: {sub}")
            plan.append(f"- **Time**: {hours}")
            plan.append(f"- **Focus**: {focus}")
            plan.append("")

        if wiki:
            plan.append(f"📚 **Reference**: [{wiki['title']}]({wiki['url']})")
        return "\n".join(plan)

    def _extract_subtopics(self, text: str) -> List[str]:
        nouns = re.findall(r'\b[A-Z][a-z]+(?:\s+[a-z]+)*\b', text)
        seen = set()
        unique = []
        for n in nouns:
            if n.lower() not in seen and len(n) > 4:
                seen.add(n.lower())
                unique.append(n)
        return unique[:8]

    # ------------------------------------------------------------------
    # SUPPORTING METHODS
    # ------------------------------------------------------------------
    def solve_problem(self, problem: str, show_steps: bool = True) -> str:
        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()
            prompt = f"""Solve this problem with step-by-step reasoning:

{problem}

Show your work clearly."""
            return brain.process_command(prompt)
        except Exception as e:
            logger.error(f"Problem solving error: {e}")
            return f"Unable to solve: {problem[:100]}..."

    def get_learning_resources(self, topic: str) -> Dict:
        resources = {"videos": [], "articles": [], "books": [], "practice": []}
        wiki = self._wiki_summary(topic)
        books = self._openlibrary_resources(topic)

        if wiki and wiki.get("url"):
            resources["articles"].append(f"Wikipedia: {wiki['title']} — {wiki['url']}")

        for b in books:
            resources["books"].append(f"{b['title']}" + (f" by {b['author']}" if b.get("author") else ""))

        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()
            response = brain.process_command(f"Suggest 2 video tutorials and 2 practice websites for learning {topic}. Format as simple bullet points.")
            lines = [l for l in response.split("\n") if l.strip().startswith("-")]
            for l in lines[:4]:
                if "http" in l:
                    resources["videos" if "youtube" in l or "video" in l else "practice"].append(l)
        except Exception:
            pass

        return resources

    def summarize_notes(self, notes: str) -> str:
        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()
            return brain.summarize_text(notes)
        except Exception as e:
            logger.error(f"Summarization error: {e}")
            return notes[:500] + "..."
