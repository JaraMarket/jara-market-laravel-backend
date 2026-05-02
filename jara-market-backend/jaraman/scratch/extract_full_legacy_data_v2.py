import re
import json
import os

sql_file = r'c:\Users\user\.gemini\jara-market\jara-market-backend\equivuxb_jaramarket.sql'
data_dir = r'c:\Users\user\.gemini\jara-market\jara-market-backend\jaraman\database\data'

def split_sql_row(row):
    """
    Splits a SQL row (val1, val2, 'val3 with , comma', NULL) into parts correctly.
    """
    parts = []
    current = ""
    in_quotes = False
    i = 0
    while i < len(row):
        char = row[i]
        if char == "'" and (i == 0 or row[i-1] != "\\"):
            # Check for escaped single quotes in SQL ('')
            if in_quotes and i + 1 < len(row) and row[i+1] == "'":
                current += "'"
                i += 1
            else:
                in_quotes = not in_quotes
        elif char == "," and not in_quotes:
            parts.append(current.strip())
            current = ""
        else:
            current += char
        i += 1
    parts.append(current.strip())
    
    # Final cleanup of parts
    cleaned = []
    for p in parts:
        p = p.strip()
        if p.upper() == 'NULL':
            cleaned.append(None)
        elif p.startswith("'") and p.endswith("'"):
            cleaned.append(p[1:-1].replace("''", "'"))
        else:
            cleaned.append(p)
    return cleaned

def parse_sql_table(content, table_name):
    pattern = rf"INSERT INTO `{table_name}` .*? VALUES\s+(.*?);"
    matches = re.finditer(pattern, content, re.DOTALL)
    
    rows_data = []
    for match in matches:
        values_str = match.group(1)
        # Find individual rows (...) by looking for the pattern ), (
        # We use a state machine approach to find the splitting points
        row_start = 0
        in_quotes = False
        for i in range(len(values_str)):
            char = values_str[i]
            if char == "'" and (i == 0 or values_str[i-1] != "\\"):
                if in_quotes and i + 1 < len(values_str) and values_str[i+1] == "'":
                    i += 1 # Skip escaped quote
                else:
                    in_quotes = not in_quotes
            elif char == ")" and not in_quotes:
                # Potential end of row
                row_content = values_str[row_start:i].strip()
                if row_content.startswith("("):
                    rows_data.append(split_sql_row(row_content[1:]))
                
                # Move to next row start
                next_comma = values_str.find(",", i)
                if next_comma != -1:
                    row_start = next_comma + 1
                else:
                    row_start = i + 1
                    
    return rows_data

with open(sql_file, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

os.makedirs(data_dir, exist_ok=True)

# Process all required tables
tables = {
    'products': ['id', 'name', 'description', 'price', 'discount_price', 'stock', 'preparation_steps', 'rating', 'image_url', 'created_at', 'updated_at'],
    'ingredients': ['id', 'category_id', 'name', 'description', 'price', 'discounted_price', 'unit', 'stock', 'image_url', 'created_at', 'updated_at'],
    'categories': ['id', 'name', 'category_type_id', 'description', 'sort_by', 'created_at', 'updated_at'],
    'category_product': ['id', 'category_id', 'product_id'],
    'ingredient_product': ['id', 'product_id', 'ingredient_id', 'quantity', 'unit']
}

for table, cols in tables.items():
    raw_data = parse_sql_table(content, table)
    cleaned_data = []
    for r in raw_data:
        if len(r) >= len(cols):
            item = {cols[i]: r[i] for i in range(len(cols))}
            cleaned_data.append(item)
    
    output_path = os.path.join(data_dir, f'legacy_{table}.json')
    with open(output_path, 'w', encoding='utf-8') as f:
        json.dump(cleaned_data, f, indent=2)
    print(f"Extracted {len(cleaned_data)} rows for {table}")
