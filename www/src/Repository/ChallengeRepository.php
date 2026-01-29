<?php

namespace App\Repository;

use App\Entity\Challenge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Challenge>
 */
class ChallengeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Challenge::class);
    }

    /**
     * Recupere tout les challenges actifs avec filtres de tri 
     * 
     * @param int $categoryId (0 pour toutes les categories)
     * @param string $sortBy ('recent', 'populaire', 'ancien')
     * @return Challenge[]
     */

    public function findAllWithFilters(int $categoryId = 0, string $sortBy = 'recent'): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.votes', 'v')
            ->leftJoin('c.medias', 'm')
            ->where('c.isActive = :isActive')
            ->setParameter('isActive', true)
            ->groupBy('c.id');

        //! Filtre par categorie
        if ($categoryId > 0) {
            $qb->andWhere('cat.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        //! filtre de tri 
        switch ($sortBy) {
            case 'popular':
                $qb->addSelect('COUNT(v.id) as HIDDEN voteCount')
                    ->orderBy('voteCount', 'DESC');
                break;
            case 'oldest':
                $qb->orderBy('c.createdAt', 'ASC');
                break;
            case 'recent':
            default:
                $qb->orderBy('c.createdAt', 'DESC');
                break;
        }
        return $qb->getQuery()->getResult();
    }
    /**
     * methode quie recupere un challenge actif par son id
     * @param int $id L'id du challenge
     * @return Challenge|null
     */
    public function findActive(int $id): ?Challenge
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.category', 'cat')
            ->leftJoin('c.user', 'u')
            ->leftJoin('c.votes', 'v')
            ->leftJoin('c.medias', 'm')
            ->where('c.isActive = :isActive')
            ->andWhere('c.id = :id')
            ->setParameter('id', $id)
            ->setParameter('isActive', true)
            ;

            return $qb->getQuery()->getOneOrNullResult();
    }
}
