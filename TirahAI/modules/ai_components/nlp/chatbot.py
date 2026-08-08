from gpt_integration import GPTIntegration

class Chatbot:
    def __init__(self, api_key):
        self.gpt = GPTIntegration(api_key)

    def respond(self, user_input):
        return self.gpt.chat(user_input)
