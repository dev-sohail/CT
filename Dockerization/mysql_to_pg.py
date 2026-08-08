#!/usr/bin/env python3
"""Convert MySQL db_structure.sql to PostgreSQL-compatible SQL."""
import re
import sys

INPUT = r"D:\CT\WEB\Edu_PHP\storage\database\db_structure.sql"
OUTPUT = r"D:\CT\WEB\Edu_PHP\storage\database\db_structure_pg.sql"

with open(INPUT, "r", encoding="utf-8") as f:
    sql = f.read()

# Phase 1: Remove MySQL directives
sql = re.sub(r'^SET SQL_MODE.*$', '', sql, flags=re.MULTILINE)
sql = re.sub(r'^START TRANSACTION;', '', sql, flags=re.MULTILINE)
sql = re.sub(r'^SET time_zone.*$', '', sql, flags=re.MULTILINE)
sql = re.sub(r'^COMMIT;', '', sql, flags=re.MULTILINE)
sql = re.sub(r'^DELIMITER\s*//.*?END//', '', sql, flags=re.MULTILINE|re.DOTALL)
sql = re.sub(r'^DELIMITER\s*;', '', sql, flags=re.MULTILINE)

# Phase 2: Remove backticks
sql = sql.replace('`', '')

# Phase 3: Fix data types
# Must do these BEFORE general int replacement to avoid conflicts
sql = re.sub(r'bigint\(\d+\)\s+NOT\s+NULL\s+AUTO_INCREMENT', 'BIGSERIAL', sql)
sql = re.sub(r'bigint\(\d+\)', 'BIGINT', sql)
sql = re.sub(r'mediumint\(\d+\)\s+unsigned', 'INTEGER', sql)
sql = re.sub(r'mediumint\(\d+\)', 'INTEGER', sql)
sql = re.sub(r'tinyint\(\d+\)\s+unsigned', 'INTEGER', sql)
sql = re.sub(r'tinyint\(\d+\)', 'SMALLINT', sql)
sql = re.sub(r'(?<!\w)int\(\d+\)\s+NOT\s+NULL\s+AUTO_INCREMENT', 'SERIAL', sql)
sql = re.sub(r'(?<!\w)int\(\d+\)', 'INTEGER', sql)
sql = re.sub(r'mediumtext', 'TEXT', sql)
sql = re.sub(r'longtext', 'TEXT', sql)
sql = re.sub(r'datetime', 'TIMESTAMP', sql)
# year(4) type -> INTEGER, but be careful with column named "year"
# Only replace when it's used as a TYPE (after whitespace or comma, before NOT/DEFAULT/etc)
sql = re.sub(r'(\s)year\(\d+\)(?=\s)', r'\1INTEGER', sql)
# Handle standalone "year" as a MySQL type (not a column name)
# We'll handle this more carefully - only match "year" that's clearly a type
# e.g., "year NOT NULL" or "year DEFAULT" but NOT "year INTEGER NOT NULL"
sql = re.sub(r'(?<=\s)unsigned(?=\s|,|$)', '', sql)
sql = re.sub(r'mediumINTEGER', 'INTEGER', sql)

# Phase 4: Fix ENUM -> VARCHAR
def replace_enum(m):
    vals = m.group(0)
    count = vals.count("'")
    size = max(20, count * 20)
    return f'varchar({size})'

sql = re.sub(r"enum\([^)]+\)", replace_enum, sql)

# Phase 5: Fix ENGINE/CHARSET/COLLATE
sql = re.sub(r'\s*ENGINE=\w+\s*DEFAULT\s+CHARSET=\w+\s*COLLATE=\w+', '', sql)
sql = re.sub(r'\s*ENGINE=\w+', '', sql)
sql = re.sub(r'\s*CHARSET=\w+', '', sql)
sql = re.sub(r'\s*COLLATE=\w+', '', sql)
sql = re.sub(r'\s*CHARACTER SET \w+', '', sql)

# Phase 6: Fix ON UPDATE and AUTO_INCREMENT
sql = re.sub(r'\s+ON\s+UPDATE\s+current_timestamp\(\)', '', sql, flags=re.IGNORECASE)
sql = re.sub(r'\s+ON\s+UPDATE\s+CURRENT_TIMESTAMP', '', sql, flags=re.IGNORECASE)
sql = re.sub(r'current_timestamp\(\)', 'NOW()', sql, flags=re.IGNORECASE)
sql = re.sub(r'(?<!\()DEFAULT\s+CURRENT_TIMESTAMP(?!\()', 'DEFAULT NOW()', sql)
sql = re.sub(r'AUTO_INCREMENT=\d+\s*', '', sql)

# Phase 7: Remove FULLTEXT INDEX
sql = re.sub(r'(?m)^\s*ALTER\s+TABLE\s+\w+\s+ADD\s+FULLTEXT\s+INDEX.*$', '', sql, flags=re.IGNORECASE)

# Phase 8: Fix UNIQUE KEY -> UNIQUE
sql = re.sub(r'UNIQUE KEY\s+\w+\s*\(([^)]+)\)', r'UNIQUE (\1)', sql, flags=re.IGNORECASE)

# Phase 9: Remove "AFTER column" from ALTER TABLE ADD COLUMN
sql = re.sub(r'\s+AFTER\s+\w+', '', sql, flags=re.IGNORECASE)

# Phase 10: Fix "double" -> "DOUBLE PRECISION"
sql = re.sub(r'(?<!\w)double(?!\w)', 'DOUBLE PRECISION', sql)

# Phase 11: Fix "DEFAULT;" at end of CREATE TABLE
sql = re.sub(r'\)\s*DEFAULT\s*;', ');', sql)
sql = re.sub(r'\)\s*ENGINE.*?;', ');', sql)

# Now the complex part: parse and restructure
lines = sql.split('\n')

# First pass: extract CREATE TABLE blocks and fix them
output_lines = []
fks = []  # Collect all FK constraints
indexes = []  # Collect all indexes
views = []  # Collect views
alter_tables = []  # Collect ALTER TABLE statements (soft delete, etc)
queries = []  # Collect SELECT/INSERT/etc queries

in_create = False
current_table = ""
brace_depth = 0
current_block = []
skip_block = False

i = 0
while i < len(lines):
    line = lines[i]
    stripped = line.strip()
    
    # Detect CREATE TABLE
    m = re.match(r'CREATE TABLE IF NOT EXISTS\s+(\w+)\s*\(', stripped)
    if m:
        in_create = True
        current_table = m.group(1)
        current_block = [line]
        i += 1
        continue
    
    # Skip MySQL trigger blocks
    if 'FOR EACH ROW' in stripped or stripped == 'BEGIN' or stripped == 'END':
        if i + 1 < len(lines) and ('SET NEW' in lines[i+1] or 'DELIMITER' in lines[i+1]):
            # Skip until we find END// or DELIMITER ;
            while i < len(lines):
                if 'DELIMITER' in lines[i]:
                    i += 1
                    break
                i += 1
            continue
    
    if in_create:
        current_block.append(line)
        
        # Check for FK constraints - extract them
        fk_match = re.match(r'\s+CONSTRAINT\s+(\w+)\s+FOREIGN KEY\s*\(([^)]+)\)\s+REFERENCES\s+(\w+)\s*\(([^)]+)\)(.*)', stripped)
        if fk_match:
            fk_name = fk_match.group(1)
            fk_cols = fk_match.group(2)
            fk_ref_table = fk_match.group(3)
            fk_ref_cols = fk_match.group(4)
            fk_rest = fk_match.group(5).strip()
            fks.append(f"ALTER TABLE {current_table} ADD CONSTRAINT {fk_name} FOREIGN KEY ({fk_cols}) REFERENCES {fk_ref_table} ({fk_ref_cols}) {fk_rest};")
            # Remove trailing comma from previous line if FK was last item
            if current_block and current_block[-2].rstrip().endswith(','):
                current_block[-2] = current_block[-2].rstrip().rstrip(',')
            continue
        
        # Check for KEY index lines (not PRIMARY KEY or UNIQUE)
        key_match = re.match(r'\s+KEY\s+(\w+)\s*\(([^)]+)\)', stripped)
        if key_match and not stripped.startswith('PRIMARY KEY') and not stripped.startswith('UNIQUE'):
            idx_name = key_match.group(1)
            idx_cols = key_match.group(2)
            indexes.append(f"CREATE INDEX IF NOT EXISTS {idx_name} ON {current_table} ({idx_cols});")
            # Remove trailing comma from previous line
            if current_block and current_block[-2].rstrip().endswith(','):
                current_block[-2] = current_block[-2].rstrip().rstrip(',')
            continue
        
        # End of CREATE TABLE
        if stripped.startswith(')'):
            in_create = False
            # Fix trailing commas before )
            fixed_block = []
            for bl in current_block:
                fixed_block.append(bl)
            
            # Clean up: remove trailing commas before )
            block_text = '\n'.join(fixed_block)
            block_text = re.sub(r',(\s*\n\s*\))', r'\1', block_text)
            # Remove ENGINE/charset remnants
            block_text = re.sub(r'\)\s*ENGINE.*?;', ');', block_text)
            block_text = re.sub(r'\)\s*DEFAULT;', ');', block_text)
            
            for bl in block_text.split('\n'):
                output_lines.append(bl)
            current_block = []
            i += 1
            continue
        
        i += 1
        continue
    
    # Outside CREATE TABLE - handle ALTER TABLE, views, queries
    if stripped.upper().startswith('ALTER TABLE'):
        alter_tables.append(line)
        # Collect continuation lines
        while i + 1 < len(lines):
            next_line = lines[i + 1].strip()
            if next_line and not next_line.upper().startswith(('ALTER', 'DROP', 'CREATE', '--', '')):
                i += 1
                alter_tables.append(lines[i])
            else:
                break
        i += 1
        continue
    
    # Skip views
    if 'CREATE VIEW' in stripped or 'CREATE OR REPLACE VIEW' in stripped:
        while i < len(lines) and not lines[i].strip().endswith(';'):
            i += 1
        i += 1
        continue
    
    # Skip standalone queries (SELECT, INSERT, etc at the end)
    if re.match(r'^(SELECT|INSERT|UPDATE|DELETE)\s', stripped, re.IGNORECASE):
        while i < len(lines) and not lines[i].strip().endswith(';'):
            i += 1
        i += 1
        continue
    
    # DROP TABLE
    if stripped.upper().startswith('DROP TABLE'):
        drop_match = re.match(r'DROP TABLE IF EXISTS\s+(\w+)', stripped)
        if drop_match:
            output_lines.append(f"DROP TABLE IF EXISTS {drop_match.group(1)} CASCADE;")
        i += 1
        continue
    
    # Comments and blank lines
    output_lines.append(line)
    i += 1

# Now fix the "year" column name issue
# The MySQL `year(4)` type was replaced with INTEGER, but columns named "year" 
# might have become "INTEGER" too. We need to check ALTER TABLE lines for this.
# Actually we need to check for column "year" that got renamed to "INTEGER"

# Build the final SQL
result = """SET session_replication_role = 'replica';

"""
result += '\n'.join(output_lines)
result += '\n\n-- Foreign Key Constraints\n'
result += '\n'.join(fks)
result += '\n\n-- Indexes\n'
result += '\n'.join(indexes)

# Add soft delete ALTER TABLE statements
if alter_tables:
    result += '\n\n-- Soft Delete & Additional Columns\n'
    result += '\n'.join(alter_tables)

result += '\n\nSET session_replication_role = \'origin\';\n'

# Post-process: fix known issues
# Fix "INTEGER INTEGER" -> "year INTEGER" (year column name got converted)
# Look for patterns where a column was originally named "year"
result = re.sub(r'(\s)INTEGER INTEGER NOT NULL', r'\1year INTEGER NOT NULL', result)

# Fix UNIQUE (INTEGER) -> UNIQUE (year) - same issue
result = re.sub(r'UNIQUE \(INTEGER\)', 'UNIQUE (year)', result)
result = re.sub(r'UNIQUE \(student_id,INTEGER,month\)', 'UNIQUE (student_id,year,month)', result)

# Fix prefix indexes in UNIQUE constraints: col(250) -> col
result = re.sub(r'(\w+)\(\d+\)(?=\s*[,\)])', r'\1', result)

# Remove multiple blank lines
result = re.sub(r'\n{3,}', '\n\n', result)

with open(OUTPUT, 'w', encoding='utf-8') as f:
    f.write(result)

# Count tables
table_count = len(re.findall(r'CREATE TABLE IF NOT EXISTS \w+', result))
fk_count = len(fks)
idx_count = len(indexes)
print(f"Converted: {table_count} tables, {fk_count} FK constraints, {idx_count} indexes")
print(f"Output: {OUTPUT}")
