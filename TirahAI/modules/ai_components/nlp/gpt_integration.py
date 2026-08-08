import openai

class GPTIntegration:
    def __init__(self, api_key):
        openai.api_key = api_key

    def get_response(self, prompt, model="gpt-3.5-turbo"):
        response = openai.Completion.create(
            engine=model,
            prompt=prompt,
            max_tokens=100,
            temperature=0.7
        )
        return response.choices[0].text.strip()

    def chat(self, user_input):
        conversation_history = []
        conversation_history.append(f"User: {user_input}")
        prompt = "\n".join(conversation_history)
        return self.get_response(prompt)
