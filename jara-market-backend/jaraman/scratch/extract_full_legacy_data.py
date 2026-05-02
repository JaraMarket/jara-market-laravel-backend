import re
import json
import os

sql_file = r'c:\Users\user\.gemini\jara-market\jara-market-backend\equivuxb_jaramarket.sql'
data_dir = r'c:\Users\user\.gemini\jara-market\jara-market-backend\jaraman\database\data'

def parse_sql_table(content, table_name):
    # Matches INSERT INTO `table_name` (...) VALUES (...), (...);
    # Handles multiple INSERT statements for the same table
    pattern = rf"INSERT INTO `{table_name}` .*? VALUES\s+(.*?);"
    matches = re.finditer(pattern, content, re.DOTALL)
    
    rows_data = []
    for match in matches:
        values_str = match.group(1)
        # Split by rows: ),( or ),\n(
        # This regex looks for the closing paren of a row followed by a comma and the start of a new row
        rows = re.findall(r"\((.*?)\)(?:,|\n|;|$)", values_str, re.DOTALL)
        
        for row in rows:
            # Simple parser for SQL values
            parts = re.findall(r"(?:'((?:''|[^'])*)'|([^,]+))", row)
            cleaned_parts = []
            for p in parts:
                if p[0] is not '': # String
                    cleaned_parts.append(p[0].replace("''", "'"))
                else: # Number or NULL
                    val = p[1].strip()
                    if val.upper() == 'NULL':
                        cleaned_parts.append(None)
                    else:
                        cleaned_parts.append(val)
            rows_data.append(cleaned_parts)
    return rows_data

with open(sql_file, 'r', encoding='utf-8', errors='ignore') as f:
    content = f.read()

# Extract Products
products_raw = parse_sql_table(content, 'products')
products = []
for p in products_raw:
    if len(p) >= 11:
        products.append({
            'id': p[0], 'name': p[1], 'description': p[2], 'price': p[3],
            'discount_price': p[4], 'stock': p[5], 'preparation_steps': p[6],
            'rating': p[7], 'image_url': p[8], 'created_at': p[9], 'updated_at': p[10]
        })

# Extract Ingredients
ingredients_raw = parse_sql_table(content, 'ingredients')
ingredients = []
for i in ingredients_raw:
    if len(i) >= 11:
        ingredients.append({
            'id': i[0], 'category_id': i[1], 'name': i[2], 'description': i[3],
            'price': i[4], 'discounted_price': i[5], 'unit': i[6], 'stock': i[7],
            'image_url': i[8], 'created_at': i[9], 'updated_at': i[10]
        })

# Extract Categories
categories_raw = parse_sql_table(content, 'categories')
categories = []
for c in categories_raw:
    if len(c) >= 8:
        categories.append({
            'id': c[0], 'name': c[1], 'category_type_id': c[2], 'description': c[3],
            'sort_by': c[4], 'created_at': c[5], 'updated_at': c[6]
        })

# Extract Pivots
category_product_raw = parse_sql_table(content, 'category_product')
category_product = [{'category_id': cp[1], 'product_id': cp[2]} for cp in category_product_raw if len(cp) >= 3]

ingredient_product_raw = parse_sql_table(content, 'ingredient_product')
ingredient_product = [{'product_id': ip[1], 'ingredient_id': ip[2], 'quantity': ip[3], 'unit': ip[4]} for ip in ingredient_product_raw if len(ip) >= 5]

# Save to JSON files
os.makedirs(data_dir, exist_ok=True)
with open(os.path.join(data_dir, 'legacy_products.json'), 'w') as f: json.dump(products, f, indent=2)
with open(os.path.join(data_dir, 'legacy_ingredients.json'), 'w') as f: json.dump(ingredients, f, indent=2)
with open(os.path.join(data_dir, 'legacy_categories.json'), 'w') as f: json.dump(categories, f, indent=2)
with open(os.path.join(data_dir, 'legacy_category_product.json'), 'w') as f: json.dump(category_product, f, indent=2)
with open(os.path.join(data_dir, 'legacy_ingredient_product.json'), 'w') as f: json.dump(ingredient_product, f, indent=2)

print(f"Extracted: {len(products)} products, {len(ingredients)} ingredients, {len(categories)} categories.")
