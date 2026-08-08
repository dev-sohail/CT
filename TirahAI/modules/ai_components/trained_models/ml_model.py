from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score
import pickle
import pandas as pd

class MLModel:
    def __init__(self):
        # Initialize a RandomForest classifier
        self.model = RandomForestClassifier()
        
    def train_model(self, data, target):
        """
        Train the model on the dataset.
        """
        X_train, X_test, y_train, y_test = train_test_split(data, target, test_size=0.3, random_state=42)
        self.model.fit(X_train, y_train)
        
        # Evaluate the model
        y_pred = self.model.predict(X_test)
        accuracy = accuracy_score(y_test, y_pred)
        print(f"Model Accuracy: {accuracy * 100}%")
        
        # Save the trained model
        with open("trained_model.pkl", "wb") as f:
            pickle.dump(self.model, f)
    
    def predict(self, data):
        """
        Predict using the trained model.
        """
        try:
            with open("trained_model.pkl", "rb") as f:
                model = pickle.load(f)
            return model.predict(data)
        except FileNotFoundError:
            print("Model not found. Please train the model first.")
            return None

# Example usage
data = pd.DataFrame({'feature1': [1, 2, 3, 4, 5], 'feature2': [5, 4, 3, 2, 1]})
target = [0, 1, 0, 1, 0]
ml_model = MLModel()
ml_model.train_model(data, target)
prediction = ml_model.predict([[2, 3]])
print("Prediction:", prediction)
