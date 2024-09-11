<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    //    /** Models for requetes
    /********************************* */
    //     * @return Event[] Returns an array of Event objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Event
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    /******************************** */


    public function countParticipationPerExpo(?int $expositionId = null) {
        // $em = $this->getEntityManager();
        // $qb = $em->createQueryBuilder();
         
        // $qb ->select('COUNT(e.id) as counts')
        // ->leftJoin('e.eventParticipation', 'ep')
        // ->from('App\Entity\Event', 'e')
        // ->where('e.id = :expositionId')
        // ->setParameter('expositionId', $expositionId)
        // ->groupBy('e.id')
        // ->getQuery();

        // $query = $qb->getQuery();
        // $query->getSingleScalarResult();


        $qb = $this->createQueryBuilder('e')
        ->select('COUNT(ep.id) as counts')
        ->leftJoin('e.eventParticipations', 'ep')
        ->andWhere('e.id = :expositionId')
        ->setParameter('expositionId', $expositionId)
        ->groupBy('e.id')
        ->getQuery();

        return $qb->getSingleScalarResult();

        // return $qb->getSingleScalarResult();
        // return $query !== null;
    }


    // >> search event (keyword)
    public function searchByKeyword(string $keyword) {  // Takes a keyword parameter of type string.
        $qb = $this->createQueryBuilder('e')
        ->where('e.name LIKE :keyword') // The keyword is inserted into the query using a parameter named :keyword.
        ->andWhere('e.status IN (:statuses)')
        ->setParameter('keyword', '%'.$keyword.'%') // The :keyword parameter is set with the searched keyword, with % to match parts of the event name.
        ->setParameter('statuses', ['OPEN', 'CLOSED', 'PENDING'])
        ->getQuery();

        return $qb->getResult();
    }


        // // >> search event (keyword)
        // // Prend un paramètre keyword de type string.
        // public function searchByKeyword(string $keyword) {  
        //     $qb = $this->createQueryBuilder('e')
        //     // Le keyword est inséré dans la requête en utilisant le paramètre nommé:keyword
        //     ->where('e.name LIKE :keyword') 
        //     // Le paramètre:keyword est défini avec le keyword recherché
        //     ->setParameter('keyword', '%'.$keyword.'%') 
        //     ->getQuery();
        //     return $qb->getResult();
        // }
  

    // >> search (period)

    public function searchByPeriod(string $period)
    {
        $now = new \DateTime();
        
        // Déterminer la date de fin en fonction de la période spécifiée
        switch ($period) {
            case 'week':
                $endTime = clone $now;
                $endTime->modify('+7 days');
                break;
            case 'days':
                $endTime = clone $now;
                $endTime->modify('+30 days');
                break;
            case 'months':
                $endTime = clone $now;
                $endTime->modify('+3 months');
                break;
            default:
                throw new \InvalidArgumentException("Invalid period: $period");
        }
        // 2nd createQueryBuilder !
        $qb = $this->createQueryBuilder('e')
            ->where('e.startDate BETWEEN :startDate AND :endTime')
            ->setParameter('startDate', $now)
            ->setParameter('endTime', $endTime)
            ->andWhere('e.status IN (:statuses)')
            ->setParameter('statuses', ['OPEN', 'CLOSED', 'PENDING'])
            ->orderBy('e.startDate', 'ASC');

        return $qb->getQuery()->getResult();
    }

    //>> Get all past content ordered by date DESC
    public function getAllPastContent() {
        $em = $this->getEntityManager();

        // Query for events
        $eventQb = $em->createQueryBuilder();
        $eventQb->select('e')
                ->from('App\Entity\Event', 'e')
                ->where('e.status = :status')
                ->setParameter('status', 'ARCHIVED')
                ->orderBy('e.startDate', 'DESC');
    
        // // Query for workshops
        // $workshopQb = $em->createQueryBuilder();
        // $workshopQb->select('w')
        //             ->from('App\Entity\Workshop', 'w')
        //             ->where('w.status = :status')
        //             ->setParameter('status', 'ARCHIVED')
        //             ->orderBy('w.startDate', 'DESC');
    
        // Execute queries
        $eventResults = $eventQb->getQuery()->getResult();
        // $workshopResults = $workshopQb->getQuery()->getResult();
    
        // Merge and sort results
        //$allPastContent = array_merge($eventResults, $workshopResults);
        $allPastContent = $eventResults;
        // usort($allPastContent, function($a, $b) {
        //     return $b->getStartDate() <=> $a->getStartDate();
        // });
        usort($allPastContent, function($a) {
             return $a->getStartDate();
        });
    
        return $allPastContent;
    }


    // >> Get ongoing event or expo
    public function getCurrentExpoEvents($type)
    {
        $em = $this->getEntityManager();

        $currentDate = new \DateTime();

        $query = $em->createQuery(
            'SELECT e FROM App\Entity\Event e
            WHERE e.type = :type
            AND e.startDate <= :currentDate
            AND e.endTime >= :currentDate'
            )
        ->setParameter('type', $type)
        ->setParameter('currentDate', $currentDate);

        return $query->getResult();
    }
}
