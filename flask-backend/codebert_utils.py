# codebert_utils.py
from transformers import RobertaTokenizer, RobertaModel
import torch
import torch.nn.functional as F

# Load CodeBERT model (once)
tokenizer = RobertaTokenizer.from_pretrained("microsoft/codebert-base")
model = RobertaModel.from_pretrained("microsoft/codebert-base")
model.eval()

def get_embedding(text):
    tokens = tokenizer(text, return_tensors="pt", padding=True, truncation=True, max_length=256)
    with torch.no_grad():
        outputs = model(**tokens)
    return outputs.last_hidden_state[:, 0, :]  # [CLS] token

def get_similarity(nl_query, generated_sql):
    emb1 = get_embedding(nl_query)
    emb2 = get_embedding(generated_sql)
    similarity = F.cosine_similarity(emb1, emb2)
    return round(similarity.item(), 4)
