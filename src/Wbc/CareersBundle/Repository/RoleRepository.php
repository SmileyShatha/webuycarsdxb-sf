<?php

declare(strict_types=1);

namespace Wbc\CareersBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class RoleRepository.
 *
 * @author Majid Mvulle <majid@majidmvulle.com>
 */
class RoleRepository extends EntityRepository
{
    public function findActiveRoles()
    {
        $now = new \DateTime();

        $qb = $this->createQueryBuilder('r')
            ->select('r')
            ->where('r.isActive = :active')
            ->andWhere('r.publishAt <= :nowDateTime')
            ->andWhere('(r.expiresAt IS NULL OR r.expiresAt > :nowDate)')
            ->orderBy('r.publishAt', 'DESC')
            ->setParameter(':active', true, \PDO::PARAM_BOOL)
            ->setParameter(':nowDateTime', $now->format('Y-m-d H:i:s'), \PDO::PARAM_STR)
            ->setParameter(':nowDate', $now->format('Y-m-d'), \PDO::PARAM_STR)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     *
     * @return array
     */
    public function findByEntityForAdminFilters()
    {
        $statement = $this->_em->getConnection()->prepare('SELECT id, title FROM careers_role');
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
