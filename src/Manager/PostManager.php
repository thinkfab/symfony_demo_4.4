<?php

namespace App\Manager;

use App\Entity\Post;
use App\Entity\Tag;
use App\Pagination\Paginator;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class PostManager
{
    /** @var EntityManagerInterface $entityManager */
    private EntityManagerInterface $entityManager;

    /** @var PostRepository $repository */
    private PostRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $repository = $this->entityManager->getRepository(Post::class);
        if (!$repository instanceof PostRepository) {
            throw new InvalidArgumentException(sprintf(
                'The repository class for "%s" must be "%s" and given "%s"! ' .
                'Maybe look the "repositoryClass" declaration on %s ?',
                Post::class,
                PostRepository::class,
                get_class($repository),
                Post::class
            ));
        }
        $this->repository = $repository;
    }

    /**
     * @param int $page
     * @param Tag|null $tag
     *
     * @return Paginator
     */
    public function findLatestPosts(int $page, Tag $tag = null): Paginator
    {
        return $this->repository->findLatest($page, $tag);
    }

    /**
     * @param string $query
     * @param int $limit
     *
     * @return Post[]
     */
    public function findBySearchQuery(string $query, int $limit): array
    {
        return $this->repository->findBySearchQuery($query, $limit);
    }

    /**
     * @param object|null $user
     *
     * @return Post[]
     */
    public function findByAuthor(?object $user): array
    {
        return $this->repository->findBy(['author' => $user], ['publishedAt' => 'DESC']);
    }
}
