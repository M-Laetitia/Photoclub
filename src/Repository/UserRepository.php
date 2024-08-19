<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

        //^ get users whith the role photographer only

        public function findPhotographerUsers(?int $userId = null) {
            $em = $this->getEntityManager();
            $qb = $em->createQueryBuilder();
    
    
            $qb ->select('u')
                ->from('App\Entity\User', 'u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%"ROLE_PHOTOGRAPHER"%');
    
            // Ajouter une condition pour filtrer par ID si un ID est fourni
            if ($userId !== null) {
                $qb->andWhere('u.id = :userId')
                    ->setParameter('userId', $userId);
            }
    
            $query = $qb->getQuery();
            return $query->getResult();
    
        }
    
        // ^ find users by pseudo
        public function findUserByPseudo(string  $criteria)
        {
            return $this->createQueryBuilder('u')
                ->andWhere('u.pseudo LIKE :pseudo')
                ->setParameter('pseudo', '%' . $criteria . '%')
                ->getQuery()
                ->getResult();
        }
    
        //^ get users filtered by role
        public function findUsersbyRole(?string $role = null, ?int $userId = null) {
            $em = $this->getEntityManager();
            $qb = $em->createQueryBuilder();
    
            $qb ->select('u')
                ->from('App\Entity\User', 'u')
                ->where('u.roles LIKE :roles')
                ->setParameter('roles', '%"'.$role.'"%');
    
            // Ajouter une condition pour filtrer par ID si un ID est fourni
            if ($userId !== null) {
                $qb->andWhere('u.id = :userId')
                    ->setParameter('userId', $userId);
            }
    
            $query = $qb->getQuery();
            return $query->getResult();
        }
    
    
        //^  Find photographers by pseudo (idem username)
        public function findPhotographerByPseudo( string $criteria) // Takes a criteria parameter of type string.
        {
            return $this->createQueryBuilder('u')
                ->andWhere('u.pseudo LIKE :pseudo') // The pseudo is inserted into the query using a parameter named :pseudo.
                ->setParameter('pseudo', '%' . $criteria . '%') // The :pseudo parameter is set with the searchedpseudo, with % to match parts of the event name.
                ->andWhere('u.roles LIKE :photographerRole')
                ->setParameter('photographerRole', '%"ROLE_PHOTOGRAPHER"%')
                ->getQuery()
                ->getResult();
        }
    
    
        //^ get artists filtered by discipline
        public function findPhotographerByDiscipline(string $criteria)
    {
        $allUsers = $this->findAll();
        $photographers = [];
    
        foreach ($allUsers as $user) {
            $authorInfos = $user->getAuthorInfos();
    
            // Vérifier si $authorInfos est null
            if ($authorInfos !== null && isset($authorInfos['discipline'])) {
                $photographerDiscipline = $authorInfos['discipline'];
                
                // Recherche par correspondance partielle
                if (stripos($photographerDiscipline, $criteria) !== false) {
                    $photographers[] = $user;
                }
            }
        }
    
        return $photographers;
    }
    
        public function findPhotographerByDisciplineFilter($criteria) 
        {
            $allUsers = $this->findAll();
            $photographers = [];
        
            foreach ($allUsers as $user) {
                $authorInfos = $user->getAuthorInfos();
                
                // Vérifier si $authorInfos est null
                if ($authorInfos !== null && isset($authorInfos['discipline'])) {
                    $photographerDiscipline = $authorInfos['discipline'];
    
                        if ($photographerDiscipline == $criteria) {
                            $photographers[] = $user;
                        }
                    }
                
            }
        
            return $photographers;
        }
    
        public function findAllDisciplines() 
        {
            $allUsers = $this->findAll();
            $disciplines = [];
        
            foreach ($allUsers as $user) {
                $authorInfos = $user->getAuthorInfos();
                
                // Vérifier si $photographerInfos est null
                if ($authorInfos !== null && isset($authorInfos['discipline'])) {
                    $photographerDiscipline = $authorInfos['discipline'];
    
                     // Ajouter la discipline au tableau des disciplines uniquement si elle n'est pas déjà présente
                    if (!in_array($photographerDiscipline, $disciplines)) {
                    $disciplines[] = $photographerDiscipline;
    
                    }
                }
            }
        
            return $disciplines;
        }
    
        public function countUsersLoggedInThisWeek()
        {
            $em = $this->getEntityManager();
    
            // get the start date of the current week
            $startOfWeek = new \DateTime('monday this week');
    
            // get the end date of the current week
            $endOfWeek = new \DateTime('sunday this week');
            $endOfWeek->setTime(23, 59, 59);
    
    
            $query = $em->createQuery(
                'SELECT COUNT(u.id) FROM App\Entity\User u 
                WHERE u.lastLoginDate >= :startOfWeek AND u.lastLoginDate <= :endOfWeek'
            )
            ->setParameter('startOfWeek', $startOfWeek)
            ->setParameter('endOfWeek', $endOfWeek);
    
            return $query->getSingleScalarResult();
        }
        
    
        // public function findPhotgrapherByCriteria($criteria)
        // {
        //     return $this->createQueryBuilder('u')
        //         ->andWhere('JSON_CONTAINS(u.authorInfos, :photographerName) = 1')
        //         ->setParameter('photographerName', '%"photographerName":"' . $criteria['photographerName'] . '"%')
        //         ->andWhere('u.roles LIKE :photographerRole')
        //         ->setParameter('photographerRole', '%"ROLE_PHOTOGRAPHER"%')
        //         ->getQuery()
        //         ->getResult();
        // }
}
