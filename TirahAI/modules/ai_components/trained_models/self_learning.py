import json
from sklearn.naive_bayes import MultinomialNB
from sklearn.feature_extraction.text import CountVectorizer

class SelfLearning:
    def __init__(self, model_file="self_learning_model.json"):
        self.model_file = model_file
        self.vectorizer = CountVectorizer()
        
        try:
            with open(self.model_file, "r") as f:
                self.model_data = json.load(f)
            self.vectorizer.fit(self.model_data["commands"])
            self.model = MultinomialNB()
            self.model.fit(self.vectorizer.transform(self.model_data["commands"]), self.model_data["responses"])
        except FileNotFoundError:
            self.model_data = {"commands": [], "responses": []}
            self.model = MultinomialNB()

    def learn(self, command, response):
        """
        Learn from a new command and response.
        """
        self.model_data["commands"].append(command)
        self.model_data["responses"].append(response)
        self.model.fit(self.vectorizer.transform(self.model_data["commands"]), self.model_data["responses"])
        
        with open(self.model_file, "w") as f:
            json.dump(self.model_data, f)

    def predict(self, command):
        """
        Predict a response based on the input command.
        """
        return self.model.predict(self.vectorizer.transform([command]))[0]

# Example usage
learner = SelfLearning()
learner.learn("What is the weather?", "It's sunny today.")
response = learner.predict("What is the weather?")
print("Predicted response:", response)
