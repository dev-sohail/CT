"""
Project Context Module for TirahAi
Scans project directories, builds structure trees with file type categorization,
agentically matches file context to user queries, content preview, and inline search.
"""

import os
import re
from pathlib import Path
from typing import Optional, List, Dict, Tuple
from utils.logger import get_logger

logger = get_logger(__name__)

IGNORED_DIRS = {
    '.git', '__pycache__', 'node_modules', 'vendor', '.venv', 'venv', 'env',
    '.env', 'dist', 'build', '.next', 'out', '.nuxt', '.cache', 'target',
    'bin', 'obj', 'Debug', 'Release', '.idea', '.vscode', '.vs', '.github',
    'coverage', '.nyc_output', '.pytest_cache', '.mypy_cache', '.ruff_cache',
    '.eggs', '*.egg-info', '.tox', '.nox', 'htmlcov', '__snapshots__',
}

IGNORED_EXTS = {
    '.pyc', '.pyo', '.so', '.dll', '.dylib', '.exe', '.bin', '.class',
    '.o', '.a', '.lib', '.obj', '.pdb', '.jpg', '.jpeg', '.png', '.gif',
    '.bmp', '.ico', '.svg', '.woff', '.woff2', '.ttf', '.eot', '.pdf',
    '.zip', '.tar', '.gz', '.xz', '.bz2', '.7z', '.rar', '.lock', '.DS_Store',
    '.map', '.min.js', '.min.css',
}

MAX_READ_SIZE = 64 * 1024
MAX_TOTAL_FILES = 500

FILE_CATEGORIES = {
    "source": {".py", ".js", ".ts", ".tsx", ".jsx", ".java", ".cpp", ".c", ".h",
               ".hpp", ".cs", ".rb", ".go", ".rs", ".swift", ".kt", ".scala",
               ".php", ".pl", ".pm", ".sh", ".bash", ".zsh", ".ps1", ".bat"},
    "web": {".html", ".htm", ".css", ".scss", ".sass", ".less", ".vue", ".svelte"},
    "config": {".json", ".yaml", ".yml", ".toml", ".ini", ".cfg", ".conf",
               ".env", ".editorconfig", ".gitignore", ".dockerignore"},
    "markup": {".md", ".mdx", ".rst", ".txt", ".tex", ".asciidoc", ".adoc"},
    "data": {".csv", ".tsv", ".xml", ".sql", ".db", ".sqlite", ".parquet",
             ".feather", ".hdf5", ".npy", ".npz"},
    "docker": {"Dockerfile", "docker-compose.yml", "docker-compose.yaml"},
}

MAX_FILE_PREVIEW = 4000


class ProjectContext:
    def __init__(self, root_path: str = "."):
        self.root = Path(root_path).resolve()
        self._tree: Optional[List[Dict]] = None
        self._file_index: Optional[Dict] = None

    def scan_directory(self, path: Optional[str] = None) -> Dict:
        p = Path(path).resolve() if path else self.root
        self._tree = []
        self._file_index = {}
        scanned = 0
        category_counts: Dict[str, int] = {}
        total_size = 0

        for root, dirs, files in os.walk(str(p)):
            rel = os.path.relpath(root, str(p))
            if rel == ".":
                rel = ""
            dirs[:] = [d for d in dirs if not d.startswith(".") and d not in IGNORED_DIRS]
            for fname in files:
                if scanned >= MAX_TOTAL_FILES:
                    break
                ext = os.path.splitext(fname)[1].lower()
                if ext in IGNORED_EXTS or fname.startswith("."):
                    continue
                key = f"{rel}/{fname}" if rel else fname
                fpath = os.path.join(root, fname)
                try:
                    fsize = os.path.getsize(fpath)
                except OSError:
                    fsize = 0
                cat = self._classify(fname, ext)
                category_counts[cat] = category_counts.get(cat, 0) + 1
                total_size += fsize
                self._file_index[key] = {"ext": ext, "size": fsize, "category": cat}
                scanned += 1
            if scanned >= MAX_TOTAL_FILES:
                break

        self._tree = self._build_index_tree()
        info = {
            "tree": self._tree,
            "tree_string": self.get_tree_string(),
            "file_count": len(self._file_index),
            "total_size": total_size,
            "categories": category_counts,
        }
        logger.info(f"Scanned {len(self._file_index)} files in {p}")
        return info

    def _classify(self, name: str, ext: str) -> str:
        if name in FILE_CATEGORIES.get("docker", set()):
            return "docker"
        for cat, exts in FILE_CATEGORIES.items():
            if ext in exts:
                return cat
        return "other"

    def _build_index_tree(self) -> List[Dict]:
        tree: Dict = {}
        for path, meta in (self._file_index or {}).items():
            parts = path.replace("\\", "/").split("/")
            node = tree
            for i, part in enumerate(parts):
                if i == len(parts) - 1:
                    if "files" not in node:
                        node["files"] = []
                    node["files"].append({
                        "name": part, "type": "file", "size": meta["size"],
                        "ext": meta["ext"], "category": meta["category"]
                    })
                else:
                    if "dirs" not in node:
                        node["dirs"] = {}
                    if part not in node["dirs"]:
                        node["dirs"][part] = {}
                    node = node["dirs"][part]

        def _dict_to_list(node, name=""):
            items = []
            if "dirs" in node:
                for dname, dnode in sorted(node["dirs"].items()):
                    children = _dict_to_list(dnode, dname)
                    items.append({"name": dname, "type": "dir", "children": children})
            if "files" in node:
                items.extend(sorted(node["files"], key=lambda x: x["name"].lower()))
            return items

        return _dict_to_list(tree)

    def get_tree_string(self, tree: Optional[List[Dict]] = None, indent: str = "") -> str:
        tree = tree or self._tree
        if not tree:
            return "(empty directory)"
        lines = []
        for item in tree:
            prefix = "📁 " if item["type"] == "dir" else "📄 "
            size_str = f" ({self._format_size(item.get('size', 0))})" if "size" in item else ""
            lines.append(f"{indent}{prefix}{item['name']}{size_str}")
            if item["type"] == "dir" and "children" in item:
                lines.append(self.get_tree_string(item["children"], indent + "  "))
        return "\n".join(lines)

    def find_relevant_files(self, query: str) -> List[Tuple[str, int, str]]:
        if not self._file_index:
            return []
        query_lower = query.lower()
        terms = re.findall(r'\w+', query_lower)

        scored: List[Tuple[float, str, int, str]] = []
        for path, meta in self._file_index.items():
            path_lower = path.lower()
            score = 0.0
            for t in terms:
                if t in path_lower:
                    score += 1.0
                    if path_lower.startswith(t) or f"/{t}" in path_lower:
                        score += 1.0
            ext = meta.get("ext", "")
            if ext:
                et = ext.lstrip(".").lower()
                if et in terms:
                    score += 2.0
            cat = meta.get("category", "")
            for t in terms:
                if t == cat:
                    score += 1.5
            if score > 0:
                scored.append((-score, path, meta.get("size", 0), cat))

        scored.sort(key=lambda x: (x[0], x[2]))
        return [(p, s, c) for _, p, s, c in scored[:15]]

    def read_file_content(self, file_path: str) -> Optional[str]:
        full = self.root / file_path
        if not full.exists() or not full.is_file():
            return None
        try:
            if full.stat().st_size > MAX_READ_SIZE:
                return f"[File too large: {full.stat().st_size} bytes]"
            return full.read_text(encoding="utf-8", errors="replace")
        except Exception as e:
            logger.warning(f"Could not read {file_path}: {e}")
            return None

    def build_context_prompt(self, query: str) -> Dict:
        project_tree = self.get_tree_string()
        relevant = self.find_relevant_files(query)
        file_snippets = []
        for path, score, cat in relevant[:5]:
            content = self.read_file_content(path)
            if content:
                snippet = content[:MAX_FILE_PREVIEW]
                file_snippets.append(f"--- {path} ({cat}) ---\n{snippet}")
        context = f"Project Structure:\n{project_tree}\n\n"
        if file_snippets:
            context += "Relevant File Contents:\n" + "\n\n".join(file_snippets)
        return {
            "project_tree": project_tree,
            "relevant_files": [p for p, _, _ in relevant],
            "context": context
        }

    def search_file_content(self, query: str) -> List[Dict]:
        if not self._file_index:
            return []
        query_lower = query.lower()
        results = []
        for path, meta in list(self._file_index.items())[:200]:
            content = self.read_file_content(path)
            if content and query_lower in content.lower():
                idx = content.lower().index(query_lower)
                start = max(0, idx - 60)
                end = min(len(content), idx + len(query) + 60)
                snippet = content[start:end]
                results.append({
                    "path": path,
                    "snippet": snippet,
                    "ext": meta.get("ext", ""),
                    "category": meta.get("category", ""),
                    "size": meta.get("size", 0),
                })
        return results[:20]

    def search_google_with_context(self, query: str, context: str) -> str:
        import urllib.request, urllib.parse, json
        combined = f"{query}\n\nContext:\n{context[:1500]}"
        search_query = urllib.parse.quote(combined[:500])
        url = f"https://en.wikipedia.org/w/api.php?action=opensearch&search={search_query}&limit=1&format=json"
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "TirahAi/1.0"})
            with urllib.request.urlopen(req, timeout=5) as resp:
                data = json.loads(resp.read().decode())
            if len(data) > 1 and data[1] and len(data) > 3 and data[3]:
                title = data[1][0]
                page_url = data[3][0]
                summary_url = f"https://en.wikipedia.org/api/rest_v1/page/summary/{urllib.parse.quote(title)}"
                req2 = urllib.request.Request(summary_url, headers={"User-Agent": "TirahAi/1.0"})
                with urllib.request.urlopen(req2, timeout=5) as resp2:
                    page = json.loads(resp2.read().decode())
                    extract = page.get("extract", "")
                    if extract:
                        return f"Based on research:\n\n{extract[:1000]}\n\n🔗 {page_url}"
            return "No research results found."
        except Exception as e:
            logger.warning(f"Research lookup error: {e}")
            return "Could not complete research lookup."

    def query_gemini(self, prompt: str) -> Optional[str]:
        try:
            from modules.nlp_brain import NLPBrain
            brain = NLPBrain()
            if brain.primary_model == "gemini" and getattr(brain, 'gemini_model', None):
                return brain.process_command(prompt)
        except Exception as e:
            logger.warning(f"Gemini query failed: {e}")
        return None

    @staticmethod
    def _format_size(size: int) -> str:
        for unit in ('B', 'KB', 'MB', 'GB'):
            if size < 1024:
                return f"{size:.1f}{unit}"
            size /= 1024
        return f"{size:.1f}TB"
