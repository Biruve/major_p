import torch
from transformers import RobertaTokenizer, EncoderDecoderModel
from transformers import RobertaTokenizer


# Load trained model and tokenizer
MODEL_PATH = "C:\\xampp\\student_project1\\codebert-nl2sql"
RobertaTokenizer.from_pretrained("microsoft/codebert-base").save_pretrained("codebert-nl2sql")
tokenizer = RobertaTokenizer.from_pretrained(MODEL_PATH)
model = EncoderDecoderModel.from_pretrained(MODEL_PATH)

# Ensure configuration is correct
model.config.decoder_start_token_id = tokenizer.cls_token_id
model.config.pad_token_id = tokenizer.pad_token_id
model = model.to("cuda" if torch.cuda.is_available() else "cpu")
model.eval()

MAX_LEN = 256
PREFIX = "translate English to SQL: "

# Function to generate SQL
def generate_sql(nl_query):
    input_text = PREFIX + nl_query
    encodings = tokenizer(
        input_text,
        max_length=MAX_LEN,
        padding="max_length",
        truncation=True,
        return_tensors="pt"
    )
    input_ids = encodings["input_ids"].to(model.device)
    attention_mask = encodings["attention_mask"].to(model.device)

    with torch.no_grad():
        outputs = model.generate(
            input_ids=input_ids,
            attention_mask=attention_mask,
            max_length=MAX_LEN,
            num_beams=5,
            early_stopping=True
        )

    decoded = tokenizer.decode(outputs[0], skip_special_tokens=True)
    decoded = decoded.replace("__LT__", "<").strip()

    # Optional: try to recover missing keywords
    if not decoded.lower().startswith(("select", "insert", "update", "delete")):
        if "select" in decoded.lower():
            decoded = "SELECT " + decoded.split("select", 1)[1]
        elif "insert" in decoded.lower():
            decoded = "INSERT " + decoded.split("insert", 1)[1]

    return decoded

# Interactive CLI
print("💬 CodeBERT NL2SQL Inference")
print("Type 'exit' to stop.\n")
while True:
    user_input = input("🧠 Enter a natural language question (or type 'exit'): ")
    if user_input.strip().lower() == "exit":
        break
    sql_output = generate_sql(user_input)
    print("🧾 Generated SQL:\n" + sql_output + "\n")
