# # Upload CSV file
# from google.colab import files
# uploaded = files.upload()  # Upload your `new4.csv`

# Imports
import torch
from torch.utils.data import Dataset, DataLoader
from transformers import T5Tokenizer, T5ForConditionalGeneration
from torch.optim import AdamW
import pandas as pd
from sklearn.model_selection import train_test_split
from tqdm import tqdm

# Config
MODEL_NAME = "t5-base"
BATCH_SIZE = 8
EPOCHS = 30
LEARNING_RATE = 5e-5
MAX_LEN = 256
DEVICE = torch.device("cuda" if torch.cuda.is_available() else "cpu")
PREFIX = "translate English to SQL: "

# Dataset Class
class NL2SQLDataset(Dataset):
    def __init__(self, data, tokenizer, max_len=MAX_LEN, prefix=PREFIX):
        self.data = data.reset_index(drop=True)
        self.tokenizer = tokenizer
        self.max_len = max_len
        self.prefix = prefix

    def __len__(self):
        return len(self.data)

    def __getitem__(self, idx):
        nl = self.prefix + str(self.data.loc[idx, 'natural_language_query'])
        sql = str(self.data.loc[idx, 'sql_query']).replace("<", "__LT__")  # ✅ Replace < with placeholder

        input_enc = self.tokenizer.encode_plus(
            nl, max_length=self.max_len, padding='max_length', truncation=True, return_tensors="pt"
        )
        target_enc = self.tokenizer.encode_plus(
            sql, max_length=self.max_len, padding='max_length', truncation=True, return_tensors="pt"
        )

        source_ids = input_enc['input_ids'].squeeze()
        source_mask = input_enc['attention_mask'].squeeze()
        target_ids = target_enc['input_ids'].squeeze()
        target_ids[target_ids == self.tokenizer.pad_token_id] = -100  # Ignore padding in loss

        return {
            'input_ids': source_ids,
            'attention_mask': source_mask,
            'labels': target_ids
        }

# Evaluation
def evaluate(model, val_loader, device):
    model.eval()
    total_val_loss = 0
    with torch.no_grad():
        for batch in val_loader:
            input_ids = batch['input_ids'].to(device)
            attention_mask = batch['attention_mask'].to(device)
            labels = batch['labels'].to(device)
            outputs = model(input_ids=input_ids, attention_mask=attention_mask, labels=labels)
            total_val_loss += outputs.loss.item()
    return total_val_loss / len(val_loader)

# Training Loop
def train_model():
    df = pd.read_csv("new4.csv")
    assert 'natural_language_query' in df.columns and 'sql_query' in df.columns, "CSV must have required columns."

    train_df, val_df = train_test_split(df, test_size=0.1, random_state=42)

    tokenizer = T5Tokenizer.from_pretrained(MODEL_NAME)

    # No need to add '<' as special token — we use '__LT__' instead
    model = T5ForConditionalGeneration.from_pretrained(MODEL_NAME)
    model.to(DEVICE)

    train_dataset = NL2SQLDataset(train_df, tokenizer)
    val_dataset = NL2SQLDataset(val_df, tokenizer)

    train_loader = DataLoader(train_dataset, batch_size=BATCH_SIZE, shuffle=True, pin_memory=True)
    val_loader = DataLoader(val_dataset, batch_size=BATCH_SIZE, pin_memory=True)

    optimizer = AdamW(model.parameters(), lr=LEARNING_RATE)

    for epoch in range(EPOCHS):
        model.train()
        total_loss = 0
        loop = tqdm(train_loader, desc=f"Epoch {epoch+1}/{EPOCHS}")
        for batch in loop:
            optimizer.zero_grad()
            input_ids = batch['input_ids'].to(DEVICE)
            attention_mask = batch['attention_mask'].to(DEVICE)
            labels = batch['labels'].to(DEVICE)

            outputs = model(input_ids=input_ids, attention_mask=attention_mask, labels=labels)
            loss = outputs.loss
            loss.backward()
            torch.nn.utils.clip_grad_norm_(model.parameters(), max_norm=1.0)
            optimizer.step()

            total_loss += loss.item()
            loop.set_postfix(loss=loss.item())

        avg_train_loss = total_loss / len(train_loader)
        val_loss = evaluate(model, val_loader, DEVICE)
        print(f"✅ Epoch {epoch+1} — Train Loss: {avg_train_loss:.4f} — Val Loss: {val_loss:.4f}")

    model.save_pretrained("./t5-nl2sql-model4")
    tokenizer.save_pretrained("./t5-nl2sql-model4")
    print("✅ Model and tokenizer saved to ./t5-nl2sql-4")

# Run the training
train_model()
