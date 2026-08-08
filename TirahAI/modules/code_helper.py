"""
Code Helper Module for TirahAi
Provides coding assistance, debugging, and code analysis
"""

import ast
from typing import Optional, Dict, List
from pathlib import Path
from utils.logger import get_logger
from config.settings import CODE_CONFIG

logger = get_logger(__name__)


class CodeHelper:
    """
    Intelligent code assistance system.
    Helps with code generation, debugging, formatting, and analysis.
    """
    
    def __init__(self):
        """Initialize code helper."""
        self.supported_languages = CODE_CONFIG['supported_languages']
        logger.info("Code Helper initialized")
    
    def analyze_code(self, code: str, language: str = 'python') -> Dict:
        """
        Analyze code for errors and complexity.
        
        Args:
            code: Source code to analyze
            language: Programming language
            
        Returns:
            Analysis results
        """
        if language == 'python':
            return self._analyze_python(code)
        else:
            return {'error': f'Analysis not yet supported for {language}'}
    
    def _analyze_python(self, code: str) -> Dict:
        """Analyze Python code."""
        result = {
            'syntax_valid': False,
            'errors': [],
            'warnings': [],
            'complexity': 0,
            'lines': 0,
            'functions': 0,
            'classes': 0
        }
        
        try:
            # Check syntax
            tree = ast.parse(code)
            result['syntax_valid'] = True
            
            # Count elements
            result['lines'] = len(code.split('\n'))
            
            for node in ast.walk(tree):
                if isinstance(node, ast.FunctionDef):
                    result['functions'] += 1
                elif isinstance(node, ast.ClassDef):
                    result['classes'] += 1
            
            # Basic complexity (number of branches)
            complexity = sum(1 for node in ast.walk(tree) 
                           if isinstance(node, (ast.If, ast.For, ast.While, ast.Try)))
            result['complexity'] = complexity
            
        except SyntaxError as e:
            result['syntax_valid'] = False
            result['errors'].append(f"Syntax error at line {e.lineno}: {e.msg}")
        except Exception as e:
            result['errors'].append(f"Analysis error: {str(e)}")
        
        return result
    
    def format_code(self, code: str, language: str = 'python') -> str:
        """
        Auto-format code.
        
        Args:
            code: Code to format
            language: Programming language
            
        Returns:
            Formatted code
        """
        try:
            if language == 'python':
                if CODE_CONFIG.get('use_autopep8', True):
                    try:
                        import autopep8
                        return autopep8.fix_code(code)
                    except Exception as e:
                        logger.warning(f"autopep8 unavailable: {e}")
                        return code
                return code
            else:
                logger.warning(f"Formatting not supported for {language}")
                return code
        except Exception as e:
            logger.error(f"Formatting error: {e}")
            return code
    
    def explain_code(self, code: str, language: str = 'python') -> str:
        """
        Generate explanation of what code does.
        
        Args:
            code: Code to explain
            language: Programming language
            
        Returns:
            Explanation text
        """
        try:
            # Use NLP brain to explain
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()
            
            prompt = f"""Explain what this {language} code does in simple terms:

```
{code}
```

Provide a clear, concise explanation."""
            
            return brain.process_command(prompt)
            
        except Exception as e:
            logger.error(f"Code explanation error: {e}")
            return "Unable to generate explanation."
    
    def suggest_improvements(self, code: str) -> List[str]:
        """
        Suggest code improvements.
        
        Args:
            code: Code to review
            
        Returns:
            List of suggestions
        """
        suggestions = []
        
        try:
            # Analyze code
            analysis = self._analyze_python(code)
            
            if not analysis['syntax_valid']:
                suggestions.append("Fix syntax errors before proceeding")
                return suggestions
            
            # Check complexity
            if analysis['complexity'] > 10:
                suggestions.append("Consider refactoring - complexity is high")
            
            # Check for common issues
            if 'except:' in code:
                suggestions.append("Avoid bare except clauses - specify exception types")
            
            if 'global ' in code:
                suggestions.append("Minimize use of global variables")
            
            # Check documentation
            tree = ast.parse(code)
            for node in ast.walk(tree):
                if isinstance(node, (ast.FunctionDef, ast.ClassDef)):
                    if not ast.get_docstring(node):
                        suggestions.append(f"Add docstring to {node.name}")
            
            if not suggestions:
                suggestions.append("Code looks good!")
            
        except Exception as e:
            logger.error(f"Error generating suggestions: {e}")
            suggestions.append("Unable to analyze code")
        
        return suggestions
    
    def debug_assist(self, code: str, error_message: str) -> str:
        """
        Help debug code based on error message.
        
        Args:
            code: Code that's causing error
            error_message: Error message received
            
        Returns:
            Debugging assistance
        """
        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()
            
            prompt = f"""Help debug this code that's producing an error:

Code:
```
{code}
```

Error:
{error_message}

Provide a solution."""
            
            return brain.process_command(prompt)
            
        except Exception as e:
            logger.error(f"Debug assist error: {e}")
            return "Unable to provide debugging assistance."
    
    def generate_code(self, description: str, language: str = 'python') -> str:
        """
        Generate code from description.
        
        Args:
            description: What the code should do
            language: Target programming language
            
        Returns:
            Generated code
        """
        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()
            
            prompt = f"""Generate {language} code for the following requirement:

{description}

Provide only the code, well-commented."""
            
            return brain.process_command(prompt)
            
        except Exception as e:
            logger.error(f"Code generation error: {e}")
            return "# Unable to generate code"
    
    def convert_code(self, code: str, from_lang: str, to_lang: str) -> str:
        """
        Convert code from one language to another.
        
        Args:
            code: Source code
            from_lang: Source language
            to_lang: Target language
            
        Returns:
            Converted code
        """
        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()
            
            prompt = f"""Convert this {from_lang} code to {to_lang}:

```{from_lang}
{code}
```

Provide the equivalent {to_lang} code."""
            
            return brain.process_command(prompt)
            
        except Exception as e:
            logger.error(f"Code conversion error: {e}")
            return f"# Unable to convert code"
    
    def get_documentation(self, function_name: str, language: str = 'python') -> str:
        """
        Get documentation for a function/method.
        
        Args:
            function_name: Name of function
            language: Programming language
            
        Returns:
            Documentation
        """
        try:
            if language == 'python':
                # Try to get built-in help
                import pydoc
                return pydoc.render_doc(function_name, "Help on %s")
        except:
            pass
        
        return f"Documentation for {function_name} not available locally."

