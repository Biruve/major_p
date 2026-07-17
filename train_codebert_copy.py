from google.colab import files
uploaded = files.upload()

import torch
from torch.utils.data import Dataset, DataLoader
from transformers import RobertaTokenizer, EncoderDecoderModel
from transformers import get_scheduler
import pandas as pd
from sklearn.model_selection import train_test_split
from tqdm import tqdm

# Configuration
MODEL_NAME = "microsoft/codebert-base"
DEVICE = torch.device("cuda" if torch.cuda.is_available() else "cpu")
MAX_LEN = 256
BATCH_SIZE = 8
EPOCHS = 10
LEARNING_RATE = 5e-5
PREFIX = "translate English to SQL: "

# Load tokenizer and model
tokenizer = RobertaTokenizer.from_pretrained(MODEL_NAME)
model = EncoderDecoderModel.from_encoder_decoder_pretrained(MODEL_NAME, MODEL_NAME)

# ✅ Set required tokens
model.config.decoder_start_token_id = tokenizer.cls_token_id
model.config.pad_token_id = tokenizer.pad_token_id

# Move to device
model = model.to(DEVICE)

# Dataset class
class NL2SQLDataset(Dataset):
    def __init__(self, data, tokenizer, max_len=MAX_LEN):
        self.data = data.reset_index(drop=True)
        self.tokenizer = tokenizer
        self.max_len = max_len

    def __len__(self):
        return len(self.data)

    def __getitem__(self, idx):
        input_text = PREFIX + str(self.data.loc[idx, 'natural_language_query'])
        target_text = str(self.data.loc[idx, 'sql_query']).replace("<", "__LT__")

        input_enc = tokenizer(
            input_text, max_length=self.max_len, padding='max_length',
            truncation=True, return_tensors="pt"
        )
        target_enc = tokenizer(
            target_text, max_length=self.max_len, padding='max_length',
            truncation=True, return_tensors="pt"
        )

        source_ids = input_enc['input_ids'].squeeze()
        source_mask = input_enc['attention_mask'].squeeze()
        target_ids = target_enc['input_ids'].squeeze()
        target_ids[target_ids == tokenizer.pad_token_id] = -100  # ignore padding

        return {
            'input_ids': source_ids,
            'attention_mask': source_mask,
            'labels': target_ids
        }

# Evaluation
def evaluate(model, val_loader):
    model.eval()
    total_loss = 0
    with torch.no_grad():
        for batch in val_loader:
            input_ids = batch['input_ids'].to(DEVICE)
            attention_mask = batch['attention_mask'].to(DEVICE)
            labels = batch['labels'].to(DEVICE)

            outputs = model(
                input_ids=input_ids,
                attention_mask=attention_mask,
                labels=labels
            )
            total_loss += outputs.loss.item()
    return total_loss / len(val_loader)

# Load dataset
df = pd.read_csv("new4.csv")
assert 'natural_language_query' in df.columns and 'sql_query' in df.columns, "CSV must contain correct columns."

train_df, val_df = train_test_split(df, test_size=0.1, random_state=42)

train_dataset = NL2SQLDataset(train_df, tokenizer)
val_dataset = NL2SQLDataset(val_df, tokenizer)

train_loader = DataLoader(train_dataset, batch_size=BATCH_SIZE, shuffle=True)
val_loader = DataLoader(val_dataset, batch_size=BATCH_SIZE)

# Optimizer & Scheduler
optimizer = torch.optim.AdamW(model.parameters(), lr=LEARNING_RATE)

# Training Loop
for epoch in range(EPOCHS):
    model.train()
    total_loss = 0
    loop = tqdm(train_loader, desc=f"Epoch {epoch + 1}/{EPOCHS}")

    for batch in loop:
        optimizer.zero_grad()
        input_ids = batch['input_ids'].to(DEVICE)
        attention_mask = batch['attention_mask'].to(DEVICE)
        labels = batch['labels'].to(DEVICE)

        outputs = model(
            input_ids=input_ids,
            attention_mask=attention_mask,
            labels=labels
        )
        loss = outputs.loss
        loss.backward()
        torch.nn.utils.clip_grad_norm_(model.parameters(), max_norm=1.0)
        optimizer.step()

        total_loss += loss.item()
        loop.set_postfix(loss=loss.item())

    val_loss = evaluate(model, val_loader)
    print(f"✅ Epoch {epoch + 1} | Train Loss: {total_loss / len(train_loader):.4f} | Val Loss: {val_loss:.4f}")

# Save final model
model.save_pretrained("./codebert-nl2sql")
tokenizer.save_pretrained("./codebert-nl2sql")
print("✅ CodeBERT NL2SQL model saved to ./codebert-nl2sql")
