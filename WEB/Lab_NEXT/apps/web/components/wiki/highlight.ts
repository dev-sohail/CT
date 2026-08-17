const LANGUAGES: Record<string, { keywords: string[]; types?: string[] }> = {
    javascript: {
        keywords: ['const', 'let', 'var', 'function', 'return', 'if', 'else', 'for', 'while', 'do', 'switch', 'case', 'break', 'continue', 'new', 'typeof', 'instanceof', 'in', 'of', 'async', 'await', 'class', 'extends', 'super', 'this', 'try', 'catch', 'finally', 'throw', 'import', 'from', 'export', 'default', 'null', 'undefined', 'true', 'false', 'yield', 'delete', 'void', 'static', 'get', 'set'],
        types: ['string', 'number', 'boolean', 'object', 'array', 'function', 'symbol', 'bigint'],
    },
    typescript: {
        keywords: ['const', 'let', 'var', 'function', 'return', 'if', 'else', 'for', 'while', 'do', 'switch', 'case', 'break', 'continue', 'new', 'typeof', 'instanceof', 'in', 'of', 'async', 'await', 'class', 'extends', 'implements', 'interface', 'type', 'enum', 'super', 'this', 'try', 'catch', 'finally', 'throw', 'import', 'from', 'export', 'default', 'null', 'undefined', 'true', 'false', 'yield', 'delete', 'void', 'static', 'get', 'set', 'readonly', 'namespace', 'declare', 'abstract', 'public', 'private', 'protected'],
        types: ['string', 'number', 'boolean', 'object', 'array', 'function', 'symbol', 'bigint', 'any', 'void', 'unknown', 'never'],
    },
    python: {
        keywords: ['def', 'return', 'if', 'elif', 'else', 'for', 'while', 'import', 'from', 'as', 'class', 'try', 'except', 'finally', 'raise', 'with', 'lambda', 'pass', 'yield', 'global', 'nonlocal', 'del', 'and', 'or', 'not', 'in', 'is', 'None', 'True', 'False', 'async', 'await', 'self', 'assert', 'break', 'continue'],
    },
    php: {
        keywords: ['<?php', 'echo', 'function', 'return', 'if', 'else', 'elseif', 'for', 'foreach', 'while', 'do', 'switch', 'case', 'break', 'continue', 'new', 'class', 'extends', 'implements', 'interface', 'public', 'private', 'protected', 'static', 'final', 'abstract', 'try', 'catch', 'finally', 'throw', 'use', 'namespace', 'require', 'require_once', 'include', 'include_once', 'null', 'true', 'false', 'this', 'self', 'parent', 'array', 'isset', 'empty', 'unset', 'exit', 'die'],
    },
    bash: {
        keywords: ['echo', 'if', 'then', 'else', 'fi', 'for', 'do', 'done', 'while', 'until', 'case', 'esac', 'function', 'return', 'exit', 'export', 'source', 'local', 'read', 'cd', 'mkdir', 'rm', 'cp', 'mv', 'ls', 'grep', 'sed', 'awk', 'true', 'false'],
    },
    html: { keywords: [] },
    css: { keywords: [] },
    sql: {
        keywords: ['SELECT', 'FROM', 'WHERE', 'INSERT', 'INTO', 'VALUES', 'UPDATE', 'SET', 'DELETE', 'CREATE', 'TABLE', 'ALTER', 'DROP', 'JOIN', 'LEFT', 'RIGHT', 'INNER', 'OUTER', 'ON', 'AS', 'GROUP', 'BY', 'ORDER', 'HAVING', 'LIMIT', 'OFFSET', 'AND', 'OR', 'NOT', 'NULL', 'PRIMARY', 'KEY', 'FOREIGN', 'REFERENCES', 'UNIQUE', 'INDEX', 'DISTINCT', 'COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'CASE', 'WHEN', 'THEN', 'ELSE', 'END'],
    },
    json: { keywords: [] },
    java: {
        keywords: ['public', 'private', 'protected', 'class', 'interface', 'extends', 'implements', 'return', 'new', 'if', 'else', 'for', 'while', 'do', 'switch', 'case', 'break', 'continue', 'static', 'final', 'void', 'try', 'catch', 'finally', 'throw', 'throws', 'import', 'package', 'this', 'super', 'null', 'true', 'false', 'abstract', 'enum', 'instanceof'],
        types: ['String', 'int', 'long', 'double', 'float', 'boolean', 'char', 'byte', 'short', 'List', 'Map', 'ArrayList', 'HashMap', 'Object'],
    },
    c: {
        keywords: ['int', 'char', 'float', 'double', 'void', 'return', 'if', 'else', 'for', 'while', 'do', 'switch', 'case', 'break', 'continue', 'struct', 'typedef', 'enum', 'union', 'const', 'static', 'extern', 'register', 'unsigned', 'signed', 'long', 'short', 'sizeof', 'NULL', 'true', 'false', 'include', 'define'],
    },
    cpp: {
        keywords: ['int', 'char', 'float', 'double', 'void', 'bool', 'return', 'if', 'else', 'for', 'while', 'do', 'switch', 'case', 'break', 'continue', 'struct', 'typedef', 'enum', 'union', 'const', 'static', 'extern', 'class', 'namespace', 'using', 'template', 'typename', 'public', 'private', 'protected', 'virtual', 'override', 'new', 'delete', 'this', 'nullptr', 'true', 'false', 'include', 'define', 'auto', 'string', 'vector', 'map', 'pair', 'endl', 'cin', 'cout'],
    },
};

interface Token {
    text: string;
    cls: string;
}

function escapeHtml(s: string): string {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

export function highlight(code: string, language: string): string {
    const lang = LANGUAGES[language];
    if (!lang) return escapeHtml(code);

    const tokens: Token[] = [];
    let i = 0;
    const len = code.length;

    while (i < len) {
        const ch = code[i];
        const next = code.slice(i);

        // Line comment
        const lineComment = next.match(/^(\/\/[^\n]*|\#[^\n]*|--[^\n]*)/);
        if (lineComment && (language !== 'python' || lineComment[0].startsWith('#') === false || code[i - 1] === '\n' || i === 0)) {
            if (language === 'python' && lineComment[0].startsWith('#') && i > 0 && code[i - 1] === ' ') {
                // not a comment if preceded by space after hash? keep simple
            }
            tokens.push({ text: lineComment[0], cls: 'c' });
            i += lineComment[0].length;
            continue;
        }

        // Block comment
        if (next.startsWith('/*')) {
            const end = code.indexOf('*/', i + 2);
            const content = end === -1 ? code.slice(i) : code.slice(i, end + 2);
            tokens.push({ text: content, cls: 'c' });
            i += content.length;
            continue;
        }

        // Strings
        const strMatch = next.match(/^("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*'|`(?:[^`\\]|\\.)*`)/);
        if (strMatch) {
            tokens.push({ text: strMatch[1], cls: 's' });
            i += strMatch[1].length;
            continue;
        }

        // Python triple-quoted strings
        const tri = next.match(/^("""[\s\S]*?"""|'''[\s\S]*?''')/);
        if (tri && language === 'python') {
            tokens.push({ text: tri[1], cls: 's' });
            i += tri[1].length;
            continue;
        }

        // PHP double-quoted string
        if (language === 'php') {
            const phpStr = next.match(/^("(?:[^"\\]|\\.)*"|'(?:[^'\\]|\\.)*')/);
            if (phpStr) {
                tokens.push({ text: phpStr[1], cls: 's' });
                i += phpStr[1].length;
                continue;
            }
        }

        // Identifiers / keywords / numbers
        const ident = next.match(/^([A-Za-z_][A-Za-z0-9_]*)/);
        if (ident) {
            const word = ident[1];
            const lower = language === 'sql' || language === 'html' ? word.toUpperCase() : word;
            let cls = '';
            if ((lang.types || []).includes(word)) cls = 't';
            else if (lang.keywords.includes(language === 'sql' ? word.toUpperCase() : word)) cls = 'k';
            tokens.push({ text: word, cls });
            i += word.length;
            continue;
        }

        // Numbers
        const num = next.match(/^(\d+\.?\d*|0x[0-9a-fA-F]+)/);
        if (num) {
            tokens.push({ text: num[1], cls: 'n' });
            i += num[1].length;
            continue;
        }

        // PHP operators $var, functions
        if (language === 'php' && ch === '$') {
            const varMatch = next.match(/^(\$[A-Za-z_][A-Za-z0-9_]*)/);
            if (varMatch) {
                tokens.push({ text: varMatch[1], cls: 'v' });
                i += varMatch[1].length;
                continue;
            }
        }

        // HTML tags
        if (language === 'html' && ch === '<') {
            const tag = next.match(/^(<\/?[A-Za-z][^>]*>)/);
            if (tag) {
                tokens.push({ text: tag[1], cls: 'h' });
                i += tag[1].length;
                continue;
            }
        }

        // CSS properties
        if (language === 'css') {
            const prop = next.match(/^([A-Za-z-]+)(?=\s*:)/);
            if (prop) {
                tokens.push({ text: prop[1], cls: 'p' });
                i += prop[1].length;
                continue;
            }
        }

        tokens.push({ text: ch, cls: '' });
        i++;
    }

    return tokens.map((t) => (t.cls ? `<span class="hl-${t.cls}">${escapeHtml(t.text)}</span>` : escapeHtml(t.text))).join('');
}
