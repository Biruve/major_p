# # Install required libraries if needed
# !pip install transformers

# from google.colab import files
# uploaded = files.upload()

import pandas as pd
import torch
from transformers import RobertaTokenizer, EncoderDecoderModel
from torch.utils.data import Dataset, DataLoader
from torch.optim import AdamW
from tqdm import tqdm

# ✅ Load Cleaned Data
df = pd.read_csv("new4.csv").dropna()

# Cleaning SQL
import re
def clean_sql(sql):
    sql = str(sql).strip()
    sql = re.sub(r"''", "'", sql)
    sql = sql.rstrip(';') + ';'
    sql = sql.replace('\n', ' ')
    sql = re.sub(r'\s+', ' ', sql)
    sql = sql.upper()
    return sql

df["input_text"] = df["natural_language_query"].str.strip()
df["target_text"] = df["sql_query"].apply(clean_sql)

# ✅ Device
device = torch.device("cuda" if torch.cuda.is_available() else "cpu")

# ✅ Tokenizer and Model
tokenizer = RobertaTokenizer.from_pretrained("microsoft/codebert-base")
model = EncoderDecoderModel.from_encoder_decoder_pretrained(
    "microsoft/codebert-base", "microsoft/codebert-base"
)

model.config.decoder_start_token_id = tokenizer.cls_token_id
model.config.eos_token_id = tokenizer.sep_token_id
model.config.pad_token_id = tokenizer.pad_token_id
model.config.vocab_size = model.config.encoder.vocab_size
model.config.max_length = 128
model.config.num_beams = 4
model.to(device)

# ✅ Custom Dataset
class NL2SQLDataset(torch.utils.data.Dataset):
    def __init__(self, df, tokenizer, max_len=128):
        self.tokenizer = tokenizer
        self.input_texts = df["input_text"].tolist()
        self.target_texts = df["target_text"].tolist()
        self.max_len = max_len

    def __len__(self):
        return len(self.input_texts)

    def __getitem__(self, idx):
        input_enc = self.tokenizer(
            self.input_texts[idx],
            padding="max_length",
            truncation=True,
            max_length=self.max_len,
            return_tensors="pt"
        )
        target_enc = self.tokenizer(
            self.target_texts[idx],
            padding="max_length",
            truncation=True,
            max_length=self.max_len,
            return_tensors="pt"
        )

        source_ids = input_enc["input_ids"].squeeze()
        source_mask = input_enc["attention_mask"].squeeze()
        target_ids = target_enc["input_ids"].squeeze()
        target_ids[target_ids == tokenizer.pad_token_id] = -100  # Ignore padding

        return {
            "input_ids": source_ids,
            "attention_mask": source_mask,
            "labels": target_ids
        }

# ✅ DataLoader
dataset = NL2SQLDataset(df, tokenizer)
dataloader = DataLoader(dataset, batch_size=8, shuffle=True)

# ✅ Optimizer
optimizer = AdamW(model.parameters(), lr=5e-5)

# ✅ Training Loop
num_epochs = 7  # Based on your dataset size (~1.5k rows)
model.train()

for epoch in range(num_epochs):
    print(f"\nEpoch {epoch+1}/{num_epochs}")
    epoch_loss = 0.0
    for batch in tqdm(dataloader):
        input_ids = batch["input_ids"].to(device)
        attention_mask = batch["attention_mask"].to(device)
        labels = batch["labels"].to(device)

        outputs = model(
            input_ids=input_ids,
            attention_mask=attention_mask,
            labels=labels
        )

        loss = outputs.loss
        loss.backward()
        optimizer.step()
        optimizer.zero_grad()
        epoch_loss += loss.item()

    avg_loss = epoch_loss / len(dataloader)
    print(f"✅ Epoch {epoch+1} Average Loss: {avg_loss:.4f}")

# ✅ Save Model
model_path = "output/codebert-nl2sql-cleaned"
model.save_pretrained(model_path)
tokenizer.save_pretrained(model_path)

print(f"\n🎉 Training complete! Model saved to: {model_path}")
