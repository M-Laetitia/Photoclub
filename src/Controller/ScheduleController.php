<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Event;
use App\Form\SearchScheduleType;
use App\Repository\EventRepository;
use App\Repository\PhotoRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ScheduleController extends AbstractController
{
    #[Route('/schedule', name: 'app_schedule')]
    //public function index(): Response
    //{
    //    return $this->render('schedule/index.html.twig', [
    //        'controller_name' => 'ScheduleController',
    //    ]);
    //}

    public function index(EventRepository $eventRepository, EventRepository $expositionRepository, Request $request, Security $security, ValidatorInterface $validator): Response
    {

        $formattedEvents = []; // fon ormate les events pour leur compatibilité avec FullSchedule

        // ^ events 
        $ongoingEvents = $eventRepository->findBy([
            'type' => 'EVENT',
            'status' => ['OPEN', 'PENDING', 'CLOSED'],
        ]);
        $pastEvents = $eventRepository->findBy([
            'type' => 'EVENT',
            'status' => ['ARCHIVED'],
        ]);

        foreach ($ongoingEvents as $event) {
            $formattedEvents[] = [
                'id' => $event->getId(),
                'title' => $event->getName(),
                'type' => $event->getType(),
                'start' => $event->getStartDate()->format('Y-m-d H:i:s'),
                'end' => $event->getEndDate()->format('Y-m-d H:i:s'),
                'slug' => $event->getSlug(),
            ];
        }

        // ^ expositions
        $ongoingExpo = $eventRepository->findBy([
            'type' => 'EXPO',
            'status' => ['OPEN', 'PENDING', 'CLOSED'],
        ]);
        $pastExpo = $eventRepository->findBy([
            'type' => 'EXPO',
            'status' => ['ARCHIVED'],
        ]);

        foreach ($ongoingExpo as $expo) {
            $formattedEvents[] = [
                'id' => $expo->getId(),
                'title' => $expo->getName(),
                'type' => $expo->getType(),
                'start' => $expo->getStartDate()->format('Y-m-d H:i:s'),
                'end' => $expo->getEndDate()->format('Y-m-d H:i:s'),
                'slug' => $expo->getSlug(),
            ];
        }


        // Search (type)
        $results = []; // Initialisation du tableau des résultats
        $noResultsFound = false;
        $resultsByKeywords = [];
        $resultsByStatus = [];
        $resultsByPeriod = [];
        // dd($discipline);
        if ($request->query->has('type')) {
            $type = $request->query->get('type'); 
           
            // Validation des données : Initialiser une liste pour autoriser uniquement les valeurs attendues
            $allowedTypes = ['event', 'expo'];
            
            if (!in_array($type, $allowedTypes)) {
                // Gérer le cas où la type n'est pas autorisée (>error 404);
                throw $this->createNotFoundException('Invalid type');
            }

            switch ($type) {
                case 'event':
                    $results = $ongoingEvents;
                    break;
                case 'expo':
                    $results = $ongoingExpo;
                    break;
                default:
                    // 
                    break;
            }
        
            // Vérifier si aucun résultat n'a été trouvé
            $noResultsFound = empty($results);
            
            return $this->render('schedule/index.html.twig', [
                'formattedEvents' => json_encode($formattedEvents),
                'results' => $results,
                'noResultsFound' => $noResultsFound,
                'resultsByKeywords' => $resultsByKeywords,
                'resultsByStatus' => $resultsByStatus, 
                'resultsByPeriod' => $resultsByPeriod,
            ]);
        }

        // Search (keyword)
        
        if ($request->query->has('formSearchKeyword')) {

            // $keyword = $request->query->get('keyword'); 

             // Échapper les données soumises
             $keyword = htmlspecialchars($request->query->get('keyword'), ENT_QUOTES, 'UTF-8');
             $errors = $validator->validate($keyword, new Length(['min' => 4]));

             if (count($errors) > 0) {
                // 
            } else {
                $resultsForEvent = $eventRepository->searchByKeyword($keyword);
                $resultsForExposition = $expositionRepository->searchByKeyword($keyword);
                // merge the results
                $resultsByKeywords = array_merge($resultsForEvent, $resultsForExposition);
    
                $noResultsFound = empty($results);
    
                return $this->render('schedule/index.html.twig', [
                    'formattedEvents' => json_encode($formattedEvents),
                    'results' => $results, 
                    'noResultsFound' => $noResultsFound,
                    'resultsByKeywords' => $resultsByKeywords,
                    'resultsByStatus' => $resultsByStatus, 
                    'resultsByPeriod' => $resultsByPeriod,
        
                ]);
            }
           
        }

        //  Search (status)       

        if ($request->query->has('formSearchStatus')) {
           
            // $status = $request->query->get('status'); 
            $status = htmlspecialchars($request->query->get('status'), ENT_QUOTES, 'UTF-8');
        //   dd($status);
            // Validation des données : Initialiser une liste pour autoriser uniquement les valeurs attendues
            $allowedStatus = ['open', 'closed', 'pending'];
            
            if (!in_array($status, $allowedStatus)) {
                // Gérer le cas où la type n'est pas autorisée (>error 404);
                throw $this->createNotFoundException('Invalid type');
            }

            switch ($status) {
                case 'open':
                    $eventsOpen = $eventRepository->findBy([
                        'status' => ['OPEN'],
                    ]);

                    ;
                    $expositionOpen  = $expositionRepository->findBy([
                        'status' => ['OPEN'],
                    ]);

                    $resultsByStatus = array_merge($eventsOpen, $expositionOpen);

                    break;

                case 'closed':
                    $eventsClosed = $eventRepository->findBy([
                        'status' => ['CLOSED'],
                    ]);
                    $expositionClosed =  $expositionRepository->findBy([
                        'status' => ['CLOSED'],
                    ]);

                    $resultsByStatus = array_merge($eventsClosed, $expositionClosed);

                    break;

                case 'pending':

                    $eventsPending = $eventRepository->findBy([
                        'status' => ['PENDING'],
                    ]);
                    $expositionPending =  $expositionRepository->findBy([
                        'status' => ['PENDING'],
                    ]);

                    $resultsByStatus = array_merge($eventsPending, $expositionPending);

                    break;
                default:
                    // 
                    break;
            }
        
            // Vérifier si aucun résultat n'a été trouvé
            $noResultsFound = empty($results);
            // dd($resultsByStatus);
            return $this->render('schedule/index.html.twig', [
                'formattedEvents' => json_encode($formattedEvents),
                'results' => $results,
                'noResultsFound' => $noResultsFound,
                'resultsByKeywords' => $resultsByKeywords,
                'resultsByStatus' => $resultsByStatus, 
                'resultsByPeriod' => $resultsByPeriod,
            ]);
        }

        // ^ Search (period)
        if ($request->query->has('formSearchPeriod')) { 
            $period = $request->query->get('period'); 
            

            $allowedPeriod = ['week', 'days', 'months'];
            
            if (!in_array($period, $allowedPeriod)) {
                // on gére  de type non autorisé (>error 404);
                throw $this->createNotFoundException('Invalid type');
            }

            switch ($period) {
                case 'week':
                    $eventsPeriod = $eventRepository->searchByPeriod($period);
                    $expositionPeriod = $expositionRepository->searchByPeriod($period);
                    $resultsByPeriod = array_merge($eventsPeriod, $expositionPeriod);
                    break;

                case 'days':
                    $eventsPeriod = $eventRepository->searchByPeriod($period);
                    $expositionPeriod = $expositionRepository->searchByPeriod($period);
                    $resultsByPeriod = array_merge($eventsPeriod, $expositionPeriod);
                    
                    break;

                case 'months':
                    $eventsPeriod = $eventRepository->searchByPeriod($period);
                    $expositionPeriod = $expositionRepository->searchByPeriod($period);
                    $resultsByPeriod = array_merge($eventsPeriod, $expositionPeriod);
                    break;

                default:
                    // 
                    break;
            }
        
            // Vérifier si aucun résultat n'a été trouvé par période
            $noResultsFound = empty($results);
            // dd($resultsByStatus);
            return $this->render('schedule/index.html.twig', [
                'formattedEvents' => json_encode($formattedEvents),
                'results' => $results,
                'noResultsFound' => $noResultsFound,
                'resultsByKeywords' => $resultsByKeywords,
                'resultsByStatus' => $resultsByStatus, 
                'resultsByPeriod' => $resultsByPeriod,
            ]);


        }

        //  Reset
        if ($request->query->has('reset')) {
            return $this->render('schedule/index.html.twig', [
                'formattedEvents' => json_encode($formattedEvents),
                'results' => $results, 
                'noResultsFound' => $noResultsFound,
                'resultsByKeywords' => $resultsByKeywords,
            ]);
        }

       
        return $this->render('schedule/index.html.twig', [
            'formattedEvents' => json_encode($formattedEvents),
            'results' => $results, 
            'noResultsFound' => $noResultsFound,
            'resultsByKeywords' => $resultsByKeywords,
            'resultsByStatus' => $resultsByStatus, 
            'resultsByPeriod' => $resultsByPeriod,

        ]);
    }

    #[Route('/archives', name: 'app_archives')]
    public function indexArchives (Request $request, EventRepository $eventRepository, EventRepository $expositionRepository) : Response
    {
        $pastEvents = $eventRepository->findBy([
            // 'type' => 'EVENT',
            'status' => ['ARCHIVED'],
        ]);
            // 'type' => 'EXPO',
        $pastExpositions = $expositionRepository->findBy([
            'status' => ['ARCHIVED'],
        ]);

        // merge events, expos
        $allPastEvents = array_merge($pastEvents, $pastExpositions);

        // usort($array, $callback_function);
        // An anonymous fct using the syntax function($a, $b).
        // This expression uses the spaceship comparison operator (<=>) to compare the start dates (startDate) of the two events $a and $b.
        usort($allPastEvents, function($a, $b) {
            return $a->getStartDate() <=> $b->getStartDate();
        });

        //Extracts the first 5 elements from the array:
        $latestEvents = array_slice($allPastEvents, 0, 4);

        return $this->render('schedule/archives.html.twig', [
             'latestEvents' => $latestEvents,
        ]);
    }

    public function getEventPhotosPaths(PhotoRepository $photoRepository) : Response
    {
        $photos = $photoRepository->getArchivedEventPhotos();
        $photosPaths = [];
    
        foreach ($photos as $photo) {
            $fileName = $photo['path'];
            $eventId = $photo['event'];
            $expoId = $photo['expo'];
    
          // vérifier si l'image est associé à un event ou un expo
            if ($eventId !== null) {
                $filePath = 'img/photographers/' . $eventId . '/works/' . $fileName;
            } elseif ($expoId !== null) {
                $filePath = 'img/photographers/' . $expoId . '/works/'  . $fileName;
            } else {
                // si associé à rien
                $filePath = '';
            }

            // Ajouter le chemin au tableau des chemins
            $photosPaths[] = $filePath;
            }
    
        return $photosPaths;
    }
}

