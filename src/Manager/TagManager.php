<?php

namespace App\Manager;

use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

class TagManager
{
    /** @var EntityManagerInterface $entityManager */
    private EntityManagerInterface $entityManager;

    /** @var TagRepository $repository */
    private TagRepository $repository;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $repository = $this->entityManager->getRepository(Tag::class);
        if (!$repository instanceof TagRepository) {
            throw new InvalidArgumentException(sprintf(
                'The repository class for "%s" must be "%s" and given "%s"! ' .
                'Maybe look the "repositoryClass" declaration on %s ?',
                Tag::class,
                TagRepository::class,
                get_class($repository),
                Tag::class
            ));
        }
        $this->repository = $repository;
    }

    /**
     * @param string $query_tag
     *
     * @return Tag|null
     */
    public function findTagByQuery(string $query_tag): ?Tag
    {
        return $this->repository->findOneBy(['name' => $query_tag]);
    }
}
