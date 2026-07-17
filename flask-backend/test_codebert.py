from transformers import RobertaTokenizer, EncoderDecoderModel
import torch

# Load trained model and tokenizer
model_path = "output/codebert-nl2sql-cleaned"  # Change if different
tokenizer = RobertaTokenizer.from_pretrained(model_path)
model = EncoderDecoderModel.from_pretrained(model_path)

device = torch.device("cuda" if torch.cuda.is_available() else "cpu")
model.to(device)
model.eval()

# Generate SQL from NL input
def generate_sql(nl_query):
    inputs = tokenizer(nl_query, return_tensors="pt", truncation=True, padding=True, max_length=128).to(device)
    with torch.no_grad():
        generated_ids = model.generate(
            input_ids=inputs["input_ids"],
            attention_mask=inputs["attention_mask"],
            max_length=128,
            num_beams=4,
            early_stopping=True
        )
    return tokenizer.decode(generated_ids[0], skip_special_tokens=True)

# 🔁 Interactive loop
while True:
    nl_query = input("📝 Enter natural language query (or type 'exit'): ")
    if nl_query.lower() == "exit":
        break
    sql = generate_sql(nl_query)
    print(f"📤 Predicted SQL: {sql}\n")
