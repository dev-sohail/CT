<?php
/**
 * Class HelpManager
 *
 * A simple help content management system for FAQs, guides, and help articles.
 */
class HelpManager
{
    protected array $articles = [];
    protected int $nextId = 1;

    /**
     * Add a new help article.
     */
    public function addArticle(string $title, string $content, array $tags = []): int
    {
        $id = $this->nextId++;
        $this->articles[$id] = [
            'id'      => $id,
            'title'   => $title,
            'content' => $content,
            'tags'    => $tags,
            'created' => time(),
            'updated' => time(),
        ];
        return $id;
    }

    /**
     * Update an existing help article.
     */
    public function updateArticle(int $id, array $data): bool
    {
        if (!isset($this->articles[$id])) {
            return false;
        }
        $this->articles[$id] = array_merge($this->articles[$id], $data, ['updated' => time()]);
        return true;
    }

    /**
     * Get a help article by ID.
     */
    public function getArticle(int $id): ?array
    {
        return $this->articles[$id] ?? null;
    }

    /**
     * Search help articles by keyword or tag.
     */
    public function search(string $query): array
    {
        $query = strtolower($query);
        return array_values(array_filter($this->articles, function ($article) use ($query) {
            return strpos(strtolower($article['title']), $query) !== false
                || strpos(strtolower($article['content']), $query) !== false
                || in_array($query, array_map('strtolower', $article['tags']), true);
        }));
    }

    /**
     * List all help articles.
     */
    public function listArticles(): array
    {
        return array_values($this->articles);
    }
}
