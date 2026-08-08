import json
import datetime

class BehaviorAnalysis:
    def __init__(self, user_id):
        self.user_id = user_id
        self.behavior_file = f"{user_id}_behavior.json"
        self.behavior_data = self.load_behavior_data()

    def load_behavior_data(self):
        try:
            with open(self.behavior_file, "r") as file:
                return json.load(file)
        except FileNotFoundError:
            return {}

    def log_behavior(self, action):
        timestamp = str(datetime.datetime.now())
        self.behavior_data[timestamp] = action
        self.save_behavior_data()

    def save_behavior_data(self):
        with open(self.behavior_file, "w") as file:
            json.dump(self.behavior_data, file)

    def get_behavior(self):
        return self.behavior_data
