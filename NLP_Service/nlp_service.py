from flask import Flask, request, jsonify
import re
import os
import json  # 新增

app = Flask(__name__)

# 從外部文件載入敏感詞列表
def load_toxic_keywords(file_path):
    try:
        with open(file_path, "r", encoding="utf-8") as file:
            return [line.strip() for line in file if line.strip()]
    except FileNotFoundError:
        print("敏感詞文件未找到，請檢查文件路徑！")
        return []
    except Exception as e:
        print(f"載入敏感詞時出現錯誤: {e}")
        return []

# 初始化敏感詞列表
toxic_keywords = load_toxic_keywords(os.path.join(os.path.dirname(__file__), "toxic_keywords.txt"))

# 優化檢測敏感詞功能
def check_toxicity(text):
    found_words = []
    for word in toxic_keywords:
        if re.search(rf'\b{re.escape(word)}\b', text, re.IGNORECASE) or word in text:
            found_words.append(word)
    return found_words

@app.route('/analyze', methods=['POST'])
def analyze_text():
    try:
        data = request.json
        if not data or "text" not in data:
            return jsonify({"status": "error", "message": "請求缺少 'text' 欄位"}), 400

        text = data.get("text", "").lower()
        found_words = check_toxicity(text)

        if found_words:
            response = {"status": "toxic", "found_words": found_words}
        else:
            response = {"status": "clean"}

        # 確保返回的 JSON 不對 Unicode 進行轉碼
        return app.response_class(
            response=json.dumps(response, ensure_ascii=False),
            status=200,
            mimetype="application/json"
        )
    except Exception as e:
        error_response = {"status": "error", "message": str(e)}
        return app.response_class(
            response=json.dumps(error_response, ensure_ascii=False),
            status=500,
            mimetype="application/json"
        )

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)