import cv2
import numpy as np

class ObjectDetection:
    def __init__(self):
        self.net = cv2.dnn.readNet("yolov3.weights", "yolov3.cfg")
        self.layer_names = self.net.getLayerNames()
        self.output_layers = [self.layer_names[i-1] for i in self.net.getUnconnectedOutLayers()]

    def detect_objects(self, image_path):
        img = cv2.imread(image_path)
        blob = cv2.dnn.blobFromImage(img, 0.00392, (416, 416), (0, 0, 0), True, crop=False)
        self.net.setInput(blob)
        outs = self.net.forward(self.output_layers)
        
        # Process results
        return outs
