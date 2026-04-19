<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Invités (comptes sans ROLE_ADMIN), pour le front-office.
     *
     * @return User[]
     */
    public function findGuests(): array
    {
        $em = $this->getEntityManager();
        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(User::class, 'u');

        $sql = sprintf(
            'SELECT %s FROM "user" u WHERE CAST(u.roles AS TEXT) NOT LIKE :adminRole AND u.blocked = false ORDER BY u.name ASC',
            $rsm->generateSelectClause()
        );

        return $em->createNativeQuery($sql, $rsm)
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->getResult();
    }

    /**
     * Invités pour la page `/guests` : liste + compteur des médias.
     * Objectif : éviter le N+1 causé par `guest.medias|length` (lazy loading).
     *
     * @return array<int, array{id: int, name: string, mediaCount: int}>
     */
    public function findGuestsWithMediaCount(): array
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $sql = '
            SELECT
                u.id AS id,
                u.name AS name,
                COUNT(m.id) AS "mediaCount"
            FROM "user" u
            LEFT JOIN media m ON m.user_id = u.id
            WHERE
                CAST(u.roles AS TEXT) NOT LIKE :adminRole
                AND u.blocked = false
            GROUP BY u.id, u.name
            ORDER BY u.name ASC
        ';

        $rows = $conn->fetchAllAssociative($sql, ['adminRole' => '%ROLE_ADMIN%']);

        foreach ($rows as &$row) {
            $row['id'] = (int) $row['id'];
            $row['mediaCount'] = (int) ($row['mediaCount'] ?? 0);
        }

        return $rows;
    }

    /**
     * Tous les comptes invités (non admin), y compris bloqués — back-office.
     *
     * @return User[]
     */
    public function findGuestAccounts(): array
    {
        $em = $this->getEntityManager();
        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(User::class, 'u');

        $sql = sprintf(
            'SELECT %s FROM "user" u WHERE CAST(u.roles AS TEXT) NOT LIKE :adminRole ORDER BY u.name ASC',
            $rsm->generateSelectClause()
        );

        return $em->createNativeQuery($sql, $rsm)
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->getResult();
    }

    public function findOneAdmin(): ?User
    {
        $em = $this->getEntityManager();
        $rsm = new ResultSetMappingBuilder($em);
        $rsm->addRootEntityFromClassMetadata(User::class, 'u');

        $sql = sprintf(
            'SELECT %s FROM "user" u WHERE CAST(u.roles AS TEXT) LIKE :adminRole LIMIT 1',
            $rsm->generateSelectClause()
        );

        $result = $em->createNativeQuery($sql, $rsm)
            ->setParameter('adminRole', '%ROLE_ADMIN%')
            ->getResult();

        return $result[0] ?? null;
    }

//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
