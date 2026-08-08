from transformers import pipeline

class Summarizer:
    def __init__(self):
        self.summarizer = pipeline("summarization")

    def summarize(self, text):
        summary = self.summarizer(text, max_length=200, min_length=50, do_sample=False)
        return summary[0]['summary_text']
