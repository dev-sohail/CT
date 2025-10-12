<?php

declare(strict_types=1);

/**
 * NLPProcessor Class
 * 
 * Basic Natural Language Processing utilities
 */
class NLPProcessor
{
    private array $stopWords = [];
    private array $sentimentWords = [
        'positive' => ['good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'love', 'like'],
        'negative' => ['bad', 'terrible', 'awful', 'horrible', 'hate', 'dislike', 'poor', 'worst']
    ];

    /**
     * Constructor
     * 
     * @param array $stopWords Custom stop words list
     */
    public function __construct(array $stopWords = [])
    {
        $this->stopWords = $stopWords ?: $this->getDefaultStopWords();
    }

    /**
     * Get default stop words
     * 
     * @return array Default stop words
     */
    private function getDefaultStopWords(): array
    {
        return [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
            'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
            'to', 'was', 'will', 'with'
        ];
    }

    /**
     * Tokenize text into words
     * 
     * @param string $text Input text
     * @return array Array of tokens
     */
    public function tokenize(string $text): array
    {
        // Convert to lowercase and remove punctuation
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        
        // Split into words and filter empty strings
        $tokens = array_filter(explode(' ', $text), function($token) {
            return !empty(trim($token));
        });
        
        return array_values($tokens);
    }

    /**
     * Remove stop words from tokens
     * 
     * @param array $tokens Array of tokens
     * @return array Filtered tokens
     */
    public function removeStopWords(array $tokens): array
    {
        return array_filter($tokens, function($token) {
            return !in_array($token, $this->stopWords);
        });
    }

    /**
     * Calculate word frequency
     * 
     * @param array $tokens Array of tokens
     * @return array Word frequency array
     */
    public function getWordFrequency(array $tokens): array
    {
        return array_count_values($tokens);
    }

    /**
     * Get most common words
     * 
     * @param array $tokens Array of tokens
     * @param int $limit Number of words to return
     * @return array Most common words
     */
    public function getMostCommonWords(array $tokens, int $limit = 10): array
    {
        $frequency = $this->getWordFrequency($tokens);
        arsort($frequency);
        return array_slice($frequency, 0, $limit, true);
    }

    /**
     * Analyze sentiment of text
     * 
     * @param string $text Input text
     * @return array Sentiment analysis result
     */
    public function analyzeSentiment(string $text): array
    {
        $tokens = $this->tokenize($text);
        $tokens = $this->removeStopWords($tokens);
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($tokens as $token) {
            if (in_array($token, $this->sentimentWords['positive'])) {
                $positiveCount++;
            } elseif (in_array($token, $this->sentimentWords['negative'])) {
                $negativeCount++;
            }
        }
        
        $totalSentimentWords = $positiveCount + $negativeCount;
        
        if ($totalSentimentWords === 0) {
            return [
                'sentiment' => 'neutral',
                'score' => 0,
                'positive' => 0,
                'negative' => 0
            ];
        }
        
        $score = ($positiveCount - $negativeCount) / $totalSentimentWords;
        
        if ($score > 0.1) {
            $sentiment = 'positive';
        } elseif ($score < -0.1) {
            $sentiment = 'negative';
        } else {
            $sentiment = 'neutral';
        }
        
        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'positive' => $positiveCount,
            'negative' => $negativeCount
        ];
    }

    /**
     * Extract keywords from text
     * 
     * @param string $text Input text
     * @param int $limit Number of keywords to return
     * @return array Extracted keywords
     */
    public function extractKeywords(string $text, int $limit = 5): array
    {
        $tokens = $this->tokenize($text);
        $tokens = $this->removeStopWords($tokens);
        
        // Filter out very short words
        $tokens = array_filter($tokens, function($token) {
            return strlen($token) > 2;
        });
        
        $frequency = $this->getWordFrequency($tokens);
        arsort($frequency);
        
        return array_keys(array_slice($frequency, 0, $limit, true));
    }

    /**
     * Calculate text similarity using Jaccard similarity
     * 
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0-1)
     */
    public function calculateSimilarity(string $text1, string $text2): float
    {
        $tokens1 = array_unique($this->tokenize($text1));
        $tokens2 = array_unique($this->tokenize($text2));
        
        $intersection = array_intersect($tokens1, $tokens2);
        $union = array_unique(array_merge($tokens1, $tokens2));
        
        if (empty($union)) {
            return 0.0;
        }
        
        return count($intersection) / count($union);
    }

    /**
     * Add custom stop words
     * 
     * @param array $words Stop words to add
     */
    public function addStopWords(array $words): void
    {
        $this->stopWords = array_unique(array_merge($this->stopWords, $words));
    }

    /**
     * Add custom sentiment words
     * 
     * @param string $category 'positive' or 'negative'
     * @param array $words Words to add
     */
    public function addSentimentWords(string $category, array $words): void
    {
        if (isset($this->sentimentWords[$category])) {
            $this->sentimentWords[$category] = array_unique(
                array_merge($this->sentimentWords[$category], $words)
            );
        }
    }

    /**
     * Get word count
     * 
     * @param string $text Input text
     * @return int Word count
     */
    public function getWordCount(string $text): int
    {
        return count($this->tokenize($text));
    }

    /**
     * Get character count
     * 
     * @param string $text Input text
     * @return int Character count
     */
    public function getCharacterCount(string $text): int
    {
        return strlen($text);
    }

    /**
     * Get reading time estimate (words per minute)
     * 
     * @param string $text Input text
     * @param int $wpm Words per minute (default 200)
     * @return float Reading time in minutes
     */
    public function getReadingTime(string $text, int $wpm = 200): float
    {
        $wordCount = $this->getWordCount($text);
        return $wordCount / $wpm;
    }
}