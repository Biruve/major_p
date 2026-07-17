import torch
from transformers import T5Tokenizer, T5ForConditionalGeneration

# Load model and tokenizer
model_path = "./t5-nl2sql-model4"
tokenizer = T5Tokenizer.from_pretrained(model_path)
model = T5ForConditionalGeneration.from_pretrained(model_path)
model.eval()

def generate_sql(nl_query):
    input_text = "translate English to SQL: " + nl_query
    input_ids = tokenizer.encode(input_text, return_tensors="pt", truncation=True)

    with torch.no_grad():
        outputs = model.generate(input_ids, max_length=256, num_beams=5, early_stopping=True)

    sql = tokenizer.decode(outputs[0], skip_special_tokens=True)
    sql = sql.replace("__LT__", "<")  # ✅ Replace back the placeholder
    return sql

# Interactive loop
while True:
    query = input("Enter a natural language query (or type 'exit' to quit):\n>> ")
    if query.lower() == 'exit':
        break
    sql_output = generate_sql(query)
    print("Predicted SQL Query:", sql_output)
