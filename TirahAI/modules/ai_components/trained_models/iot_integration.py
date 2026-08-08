import paho.mqtt.client as mqtt

class IoTIntegration:
    def __init__(self, broker="mqtt.eclipse.org", port=1883):
        self.client = mqtt.Client()
        self.client.connect(broker, port, 60)

    def send_message(self, topic, message):
        """
        Publish a message to an IoT device.
        """
        self.client.publish(topic, message)
        print(f"Message sent to {topic}: {message}")

    def subscribe_topic(self, topic):
        """
        Subscribe to a topic to receive messages.
        """
        self.client.subscribe(topic)

    def on_message(self, client, userdata, msg):
        """
        Callback function to handle messages from the subscribed topic.
        """
        print(f"Received message: {msg.payload.decode()} on topic {msg.topic}")

    def start_listening(self):
        """
        Start listening for incoming messages.
        """
        self.client.on_message = self.on_message
        self.client.loop_forever()

# Example usage
iot = IoTIntegration()
iot.send_message("home/temperature", "22°C")
iot.start_listening()
