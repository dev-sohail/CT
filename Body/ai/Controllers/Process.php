<?php

declare(strict_types=1);

require_once ROOT . '/Brain/Core/Controller.php';

/**
 * AI Process Controller
 */
class ProcessController extends Controller
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Method not allowed'], 405);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['text'])) {
            $this->jsonResponse(['error' => 'Invalid input'], 400);
            return;
        }

        // Simulate AI processing
        $result = [
            'processed_text' => $input['text'],
            'sentiment' => $this->analyzeSentiment($input['text']),
            'keywords' => $this->extractKeywords($input['text']),
            'confidence' => rand(80, 95) / 100,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $this->jsonResponse($result);
    }

    private function analyzeSentiment(string $text): string
    {
        $positiveWords = ['good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic'];
        $negativeWords = ['bad', 'terrible', 'awful', 'horrible', 'disgusting', 'hate'];
        
        $text = strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($text, $word);
        }
        
        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($text, $word);
        }
        
        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }

    private function extractKeywords(string $text): array
    {
        $words = str_word_count(strtolower($text), 1);
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        
        $filteredWords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });
        
        $wordCount = array_count_values($filteredWords);
        arsort($wordCount);
        
        return array_slice(array_keys($wordCount), 0, 5);
    }
}
