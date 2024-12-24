import requests

url = "http://127.0.0.1:5000/detect"
data = {"content": "This is a bad text!"}

response = requests.post(url, json=data)
print(response.json())