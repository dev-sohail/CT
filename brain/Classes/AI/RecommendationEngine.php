<?php

declare(strict_types=1);

/**
 * RecommendationEngine Class
 * 
 * Simple collaborative filtering recommendation engine
 */
class RecommendationEngine
{
    private array $userRatings = [];
    private array $itemRatings = [];

    /**
     * Add user rating for an item
     * 
     * @param int $userId User ID
     * @param int $itemId Item ID
     * @param float $rating Rating (1-5)
     */
    public function addRating(int $userId, int $itemId, float $rating): void
    {
        $this->userRatings[$userId][$itemId] = $rating;
        $this->itemRatings[$itemId][$userId] = $rating;
    }

    /**
     * Get user ratings
     * 
     * @param int $userId User ID
     * @return array User ratings
     */
    public function getUserRatings(int $userId): array
    {
        return $this->userRatings[$userId] ?? [];
    }

    /**
     * Get item ratings
     * 
     * @param int $itemId Item ID
     * @return array Item ratings
     */
    public function getItemRatings(int $itemId): array
    {
        return $this->itemRatings[$itemId] ?? [];
    }

    /**
     * Calculate similarity between two users using cosine similarity
     * 
     * @param int $userId1 First user ID
     * @param int $userId2 Second user ID
     * @return float Similarity score (0-1)
     */
    public function calculateUserSimilarity(int $userId1, int $userId2): float
    {
        $ratings1 = $this->getUserRatings($userId1);
        $ratings2 = $this->getUserRatings($userId2);

        // Find common items
        $commonItems = array_intersect_key($ratings1, $ratings2);

        if (empty($commonItems)) {
            return 0.0;
        }

        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        foreach ($commonItems as $itemId => $rating1) {
            $rating2 = $ratings2[$itemId];
            $dotProduct += $rating1 * $rating2;
            $norm1 += $rating1 * $rating1;
            $norm2 += $rating2 * $rating2;
        }

        if ($norm1 == 0 || $norm2 == 0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }

    /**
     * Get similar users
     * 
     * @param int $userId User ID
     * @param int $limit Number of similar users to return
     * @return array Similar users with scores
     */
    public function getSimilarUsers(int $userId, int $limit = 5): array
    {
        $similarities = [];
        $userRatings = $this->getUserRatings($userId);

        if (empty($userRatings)) {
            return [];
        }

        foreach ($this->userRatings as $otherUserId => $ratings) {
            if ($otherUserId === $userId) {
                continue;
            }

            $similarity = $this->calculateUserSimilarity($userId, $otherUserId);
            if ($similarity > 0) {
                $similarities[$otherUserId] = $similarity;
            }
        }

        arsort($similarities);
        return array_slice($similarities, 0, $limit, true);
    }

    /**
     * Get recommendations for a user
     * 
     * @param int $userId User ID
     * @param int $limit Number of recommendations
     * @return array Recommended items with scores
     */
    public function getRecommendations(int $userId, int $limit = 10): array
    {
        $userRatings = $this->getUserRatings($userId);
        $similarUsers = $this->getSimilarUsers($userId);
        
        if (empty($similarUsers)) {
            return [];
        }

        $recommendations = [];
        $totalSimilarity = array_sum($similarUsers);

        foreach ($similarUsers as $similarUserId => $similarity) {
            $similarUserRatings = $this->getUserRatings($similarUserId);
            
            foreach ($similarUserRatings as $itemId => $rating) {
                // Skip items already rated by the user
                if (isset($userRatings[$itemId])) {
                    continue;
                }

                if (!isset($recommendations[$itemId])) {
                    $recommendations[$itemId] = 0;
                }

                $recommendations[$itemId] += $rating * ($similarity / $totalSimilarity);
            }
        }

        arsort($recommendations);
        return array_slice($recommendations, 0, $limit, true);
    }

    /**
     * Get item-based recommendations
     * 
     * @param int $itemId Item ID
     * @param int $limit Number of recommendations
     * @return array Recommended items with scores
     */
    public function getItemBasedRecommendations(int $itemId, int $limit = 10): array
    {
        $itemRatings = $this->getItemRatings($itemId);
        
        if (empty($itemRatings)) {
            return [];
        }

        $recommendations = [];

        foreach ($this->itemRatings as $otherItemId => $ratings) {
            if ($otherItemId === $itemId) {
                continue;
            }

            $similarity = $this->calculateItemSimilarity($itemId, $otherItemId);
            if ($similarity > 0) {
                $recommendations[$otherItemId] = $similarity;
            }
        }

        arsort($recommendations);
        return array_slice($recommendations, 0, $limit, true);
    }

    /**
     * Calculate similarity between two items
     * 
     * @param int $itemId1 First item ID
     * @param int $itemId2 Second item ID
     * @return float Similarity score (0-1)
     */
    public function calculateItemSimilarity(int $itemId1, int $itemId2): float
    {
        $ratings1 = $this->getItemRatings($itemId1);
        $ratings2 = $this->getItemRatings($itemId2);

        // Find common users
        $commonUsers = array_intersect_key($ratings1, $ratings2);

        if (empty($commonUsers)) {
            return 0.0;
        }

        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        foreach ($commonUsers as $userId => $rating1) {
            $rating2 = $ratings2[$userId];
            $dotProduct += $rating1 * $rating2;
            $norm1 += $rating1 * $rating1;
            $norm2 += $rating2 * $rating2;
        }

        if ($norm1 == 0 || $norm2 == 0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }

    /**
     * Get average rating for an item
     * 
     * @param int $itemId Item ID
     * @return float Average rating
     */
    public function getAverageRating(int $itemId): float
    {
        $ratings = $this->getItemRatings($itemId);
        
        if (empty($ratings)) {
            return 0.0;
        }

        return array_sum($ratings) / count($ratings);
    }

    /**
     * Get most popular items
     * 
     * @param int $limit Number of items to return
     * @return array Popular items with average ratings
     */
    public function getMostPopularItems(int $limit = 10): array
    {
        $popularity = [];

        foreach ($this->itemRatings as $itemId => $ratings) {
            $averageRating = $this->getAverageRating($itemId);
            $ratingCount = count($ratings);
            
            // Weight by number of ratings to avoid items with single high ratings
            $popularity[$itemId] = $averageRating * log(1 + $ratingCount);
        }

        arsort($popularity);
        return array_slice($popularity, 0, $limit, true);
    }

    /**
     * Get user statistics
     * 
     * @param int $userId User ID
     * @return array User statistics
     */
    public function getUserStats(int $userId): array
    {
        $ratings = $this->getUserRatings($userId);
        
        if (empty($ratings)) {
            return [
                'total_ratings' => 0,
                'average_rating' => 0,
                'rating_distribution' => []
            ];
        }

        $ratingDistribution = array_count_values($ratings);
        ksort($ratingDistribution);

        return [
            'total_ratings' => count($ratings),
            'average_rating' => array_sum($ratings) / count($ratings),
            'rating_distribution' => $ratingDistribution
        ];
    }

    /**
     * Clear all data
     */
    public function clear(): void
    {
        $this->userRatings = [];
        $this->itemRatings = [];
    }

    /**
     * Get total number of users
     * 
     * @return int Number of users
     */
    public function getUserCount(): int
    {
        return count($this->userRatings);
    }

    /**
     * Get total number of items
     * 
     * @return int Number of items
     */
    public function getItemCount(): int
    {
        return count($this->itemRatings);
    }
}