from transformers import AutoTokenizer, AutoModel

# 載入模型
MODEL_NAME = "THUDM/chatglm-6b"
tokenizer = AutoTokenizer.from_pretrained(MODEL_NAME, trust_remote_code=True)
model = AutoModel.from_pretrained(MODEL_NAME, trust_remote_code=True).half().cuda()  # 使用 GPU
model.eval()
