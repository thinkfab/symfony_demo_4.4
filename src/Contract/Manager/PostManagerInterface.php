<?php

namespace App\Contract\Manager;

use App\Entity\Post;
use App\Entity\Tag;
use App\Pagination\Paginator;

interface PostManagerInterface
{
    /**
     * @param int $page
     * @param Tag|null $tag
     *
     * @return Paginator
     */
    public function findLatestPosts(int $page, Tag $tag = null): Paginator;

    /**
     * @param string $query
     * @param int $limit
     *
     * @return Post[]
     */
    public function findBySearchQuery(string $query, int $limit): array;

    /**
     * @param object|null $user
     *
     * @return Post[]
     */
    public function findByAuthor(?object $user): array;
}