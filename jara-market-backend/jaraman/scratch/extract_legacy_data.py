import re
import json

sql_file = r'c:\Users\user\.gemini\jara-market\jara-market-backend\equivuxb_jaramarket.sql'
output_file = r'c:\Users\user\.gemini\jara-market\jara-market-backend\jaraman\database\data\legacy_products.json'

with open(sql_file, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# Find the INSERT INTO `products` block
# Note: It might span multiple INSERT statements
matches = re.finditer(r"INSERT INTO `products` .*? VALUES\s+(.*?);", content, re.DOTALL)

all_products = []

for match in matches:
    values_str = match.group(1)
    # Split by rows: ),( but handle potential commas inside strings
    # This is a bit complex for a regex, so we'll use a simpler split and then clean up
    # A better way is to find the pattern ),\n(
    rows = re.findall(r"\((.*?)\)(?:,|\n|;|$)", values_str, re.DOTALL)
    
    for row in rows:
        # Split the row by commas, but ignore commas inside single quotes
        # This regex handles escaped single quotes too
        parts = re.findall(r"(?:'((?:''|[^'])*)'|([^,]+))", row)
        cleaned_parts = []
        for p in parts:
            if p[0]: # String
                cleaned_parts.append(p[0].replace("''", "'"))
            else: # Number or NULL
                val = p[1].strip()
                if val.upper() == 'NULL':
                    cleaned_parts.append(None)
                else:
                    cleaned_parts.append(val)
        
        if len(cleaned_parts) >= 11:
            product = {
                'id': cleaned_parts[0],
                'name': cleaned_parts[1],
                'description': cleaned_parts[2],
                'price': cleaned_parts[3],
                'discount_price': cleaned_parts[4],
                'stock': cleaned_parts[5],
                'preparation_steps': cleaned_parts[6],
                'rating': cleaned_parts[7],
                'image_url': cleaned_parts[8],
                'created_at': cleaned_parts[9],
                'updated_at': cleaned_parts[10],
            }
            all_products.append(product)

with open(output_file, 'w', encoding='utf-8') as f:
    json.dump(all_products, f, indent=4)

print(f"Successfully extracted {len(all_products)} products to {output_file}")
