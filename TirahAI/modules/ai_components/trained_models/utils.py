import json

def load_config(config_file="config.json"):
    """
    Load configuration settings from a JSON file.
    """
    try:
        with open(config_file, "r") as f:
            return json.load(f)
    except FileNotFoundError:
        print(f"Config file {config_file} not found.")
        return {}

def save_config(config_data, config_file="config.json"):
    """
    Save configuration settings to a JSON file.
    """
    with open(config_file, "w") as f:
        json.dump(config_data, f)
    print(f"Config saved to {config_file}")

# Example usage
config = load_config()
config["assistant_name"] = "JARVIS"
save_config(config)
