import json

class UserProfile:
    def __init__(self, user_id):
        self.user_id = user_id
        self.profile_file = f"{user_id}_profile.json"
        self.profile_data = self.load_profile()

    def load_profile(self):
        try:
            with open(self.profile_file, "r") as file:
                return json.load(file)
        except FileNotFoundError:
            return {}

    def save_profile(self):
        with open(self.profile_file, "w") as file:
            json.dump(self.profile_data, file)

    def update_preference(self, key, value):
        self.profile_data[key] = value
        self.save_profile()

    def get_preference(self, key):
        return self.profile_data.get(key, None)
