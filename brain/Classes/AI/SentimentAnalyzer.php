<?php

declare(strict_types=1);

/**
 * SentimentAnalyzer Class
 * 
 * Advanced sentiment analysis with machine learning capabilities
 */
class SentimentAnalyzer
{
    private array $positiveWords = [];
    private array $negativeWords = [];
    private array $neutralWords = [];
    private array $intensifiers = [];
    private array $negators = [];
    private float $positiveThreshold = 0.1;
    private float $negativeThreshold = -0.1;

    /**
     * Constructor
     * 
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->initializeWordLists();
        $this->configure($config);
    }

    /**
     * Initialize word lists
     */
    private function initializeWordLists(): void
    {
        $this->positiveWords = [
            'good', 'great', 'excellent', 'amazing', 'wonderful', 'fantastic', 'awesome',
            'love', 'like', 'enjoy', 'happy', 'pleased', 'satisfied', 'impressed',
            'brilliant', 'outstanding', 'superb', 'perfect', 'ideal', 'best', 'top',
            'positive', 'optimistic', 'hopeful', 'confident', 'proud', 'grateful'
        ];

        $this->negativeWords = [
            'bad', 'terrible', 'awful', 'horrible', 'disgusting', 'hate', 'dislike',
            'angry', 'frustrated', 'disappointed', 'annoyed', 'upset', 'sad', 'depressed',
            'worst', 'poor', 'pathetic', 'useless', 'worthless', 'stupid', 'dumb',
            'negative', 'pessimistic', 'hopeless', 'worried', 'concerned', 'fearful'
        ];

        $this->intensifiers = [
            'very', 'extremely', 'incredibly', 'absolutely', 'totally', 'completely',
            'really', 'quite', 'rather', 'somewhat', 'slightly', 'barely'
        ];

        $this->negators = [
            'not', 'no', 'never', 'none', 'nothing', 'nobody', 'nowhere', 'neither',
            'cannot', 'can\'t', 'won\'t', 'wouldn\'t', 'shouldn\'t', 'couldn\'t'
        ];
    }

    /**
     * Configure analyzer
     * 
     * @param array $config Configuration options
     */
    private function configure(array $config): void
    {
        if (isset($config['positive_threshold'])) {
            $this->positiveThreshold = (float) $config['positive_threshold'];
        }
        
        if (isset($config['negative_threshold'])) {
            $this->negativeThreshold = (float) $config['negative_threshold'];
        }

        if (isset($config['positive_words'])) {
            $this->positiveWords = array_merge($this->positiveWords, $config['positive_words']);
        }

        if (isset($config['negative_words'])) {
            $this->negativeWords = array_merge($this->negativeWords, $config['negative_words']);
        }
    }

    /**
     * Analyze sentiment of text
     * 
     * @param string $text Text to analyze
     * @return array Sentiment analysis result
     */
    public function analyze(string $text): array
    {
        $words = $this->tokenize($text);
        $sentimentScore = 0.0;
        $wordCount = count($words);
        $positiveCount = 0;
        $negativeCount = 0;
        $neutralCount = 0;

        for ($i = 0; $i < $wordCount; $i++) {
            $word = strtolower($words[$i]);
            $wordSentiment = $this->getWordSentiment($word);
            $intensity = $this->getIntensity($words, $i);
            $isNegated = $this->isNegated($words, $i);

            if ($isNegated) {
                $wordSentiment = -$wordSentiment;
            }

            $wordSentiment *= $intensity;
            $sentimentScore += $wordSentiment;

            if ($wordSentiment > 0) {
                $positiveCount++;
            } elseif ($wordSentiment < 0) {
                $negativeCount++;
            } else {
                $neutralCount++;
            }
        }

        // Normalize score
        if ($wordCount > 0) {
            $sentimentScore = $sentimentScore / $wordCount;
        }

        $sentiment = $this->classifySentiment($sentimentScore);
        $confidence = $this->calculateConfidence($sentimentScore, $wordCount);

        return [
            'sentiment' => $sentiment,
            'score' => $sentimentScore,
            'confidence' => $confidence,
            'positive_count' => $positiveCount,
            'negative_count' => $negativeCount,
            'neutral_count' => $neutralCount,
            'word_count' => $wordCount
        ];
    }

    /**
     * Tokenize text into words
     * 
     * @param string $text Input text
     * @return array Array of words
     */
    private function tokenize(string $text): array
    {
        // Remove punctuation and split into words
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        $words = preg_split('/\s+/', trim($text));
        
        return array_filter($words, function($word) {
            return !empty(trim($word));
        });
    }

    /**
     * Get sentiment value for a word
     * 
     * @param string $word Word to analyze
     * @return float Sentiment value (-1 to 1)
     */
    private function getWordSentiment(string $word): float
    {
        if (in_array($word, $this->positiveWords)) {
            return 1.0;
        } elseif (in_array($word, $this->negativeWords)) {
            return -1.0;
        }
        
        return 0.0;
    }

    /**
     * Get intensity modifier for a word
     * 
     * @param array $words Array of words
     * @param int $index Current word index
     * @return float Intensity modifier
     */
    private function getIntensity(array $words, int $index): float
    {
        $intensity = 1.0;
        
        // Check previous words for intensifiers
        for ($i = max(0, $index - 2); $i < $index; $i++) {
            $word = strtolower($words[$i]);
            if (in_array($word, $this->intensifiers)) {
                switch ($word) {
                    case 'very':
                    case 'extremely':
                    case 'incredibly':
                    case 'absolutely':
                        $intensity *= 1.5;
                        break;
                    case 'really':
                    case 'quite':
                        $intensity *= 1.2;
                        break;
                    case 'somewhat':
                    case 'slightly':
                        $intensity *= 0.8;
                        break;
                    case 'barely':
                        $intensity *= 0.5;
                        break;
                }
            }
        }
        
        return $intensity;
    }

    /**
     * Check if word is negated
     * 
     * @param array $words Array of words
     * @param int $index Current word index
     * @return bool True if negated
     */
    private function isNegated(array $words, int $index): bool
    {
        // Check previous words for negators
        for ($i = max(0, $index - 3); $i < $index; $i++) {
            $word = strtolower($words[$i]);
            if (in_array($word, $this->negators)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Classify sentiment based on score
     * 
     * @param float $score Sentiment score
     * @return string Sentiment classification
     */
    private function classifySentiment(float $score): string
    {
        if ($score > $this->positiveThreshold) {
            return 'positive';
        } elseif ($score < $this->negativeThreshold) {
            return 'negative';
        }
        
        return 'neutral';
    }

    /**
     * Calculate confidence score
     * 
     * @param float $score Sentiment score
     * @param int $wordCount Number of words
     * @return float Confidence score (0-1)
     */
    private function calculateConfidence(float $score, int $wordCount): float
    {
        // Base confidence on score magnitude and word count
        $magnitude = abs($score);
        $wordFactor = min(1.0, $wordCount / 10.0); // More words = higher confidence
        
        return $magnitude * $wordFactor;
    }

    /**
     * Batch analyze multiple texts
     * 
     * @param array $texts Array of texts to analyze
     * @return array Array of analysis results
     */
    public function batchAnalyze(array $texts): array
    {
        $results = [];
        
        foreach ($texts as $index => $text) {
            $results[$index] = $this->analyze($text);
        }
        
        return $results;
    }

    /**
     * Get overall sentiment from multiple analyses
     * 
     * @param array $analyses Array of analysis results
     * @return array Overall sentiment
     */
    public function getOverallSentiment(array $analyses): array
    {
        if (empty($analyses)) {
            return [
                'sentiment' => 'neutral',
                'score' => 0.0,
                'confidence' => 0.0,
                'total_texts' => 0
            ];
        }

        $totalScore = 0.0;
        $totalConfidence = 0.0;
        $sentimentCounts = ['positive' => 0, 'negative' => 0, 'neutral' => 0];

        foreach ($analyses as $analysis) {
            $totalScore += $analysis['score'];
            $totalConfidence += $analysis['confidence'];
            $sentimentCounts[$analysis['sentiment']]++;
        }

        $avgScore = $totalScore / count($analyses);
        $avgConfidence = $totalConfidence / count($analyses);
        $overallSentiment = $this->classifySentiment($avgScore);

        return [
            'sentiment' => $overallSentiment,
            'score' => $avgScore,
            'confidence' => $avgConfidence,
            'total_texts' => count($analyses),
            'sentiment_distribution' => $sentimentCounts
        ];
    }

    /**
     * Add custom positive words
     * 
     * @param array $words Words to add
     */
    public function addPositiveWords(array $words): void
    {
        $this->positiveWords = array_merge($this->positiveWords, $words);
        $this->positiveWords = array_unique($this->positiveWords);
    }

    /**
     * Add custom negative words
     * 
     * @param array $words Words to add
     */
    public function addNegativeWords(array $words): void
    {
        $this->negativeWords = array_merge($this->negativeWords, $words);
        $this->negativeWords = array_unique($this->negativeWords);
    }

    /**
     * Set sentiment thresholds
     * 
     * @param float $positiveThreshold Positive threshold
     * @param float $negativeThreshold Negative threshold
     */
    public function setThresholds(float $positiveThreshold, float $negativeThreshold): void
    {
        $this->positiveThreshold = $positiveThreshold;
        $this->negativeThreshold = $negativeThreshold;
    }

    /**
     * Get current configuration
     * 
     * @return array Configuration
     */
    public function getConfig(): array
    {
        return [
            'positive_threshold' => $this->positiveThreshold,
            'negative_threshold' => $this->negativeThreshold,
            'positive_words_count' => count($this->positiveWords),
            'negative_words_count' => count($this->negativeWords),
            'intensifiers_count' => count($this->intensifiers),
            'negators_count' => count($this->negators)
        ];
    }
}