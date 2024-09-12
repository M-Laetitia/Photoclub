<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Event;
use App\Entity\Photo;
use App\Form\EventType;
use App\Form\PhotoFormType;
use App\Service\PhotoService;
use App\Repository\EventRepository;
use App\Repository\PictureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Security;
use App\Repository\EventParticipationRepository;

class EventController extends AbstractController
// {
//     #[Route('/event', name: 'app_event')]
//     public function index(): Response
//     {
//         return $this->render('event/index.html.twig', [
//             'controller_name' => 'EventController',
//         ]);
//     }
// }
{
    // ^ show list events (all)
    #[Route('/event', name: 'app_event')]
    public function index(EventRepository $eventRepository): Response
    {

        
        $ongoingEvents = $eventRepository->findBy([
            'type' => 'EVENT',
            'status' => ['OPEN', 'PENDING', 'CLOSED'],
        ]);
        $pastEvents = $eventRepository->findBy([
            'type' => 'EVENT',
            'status' => ['ARCHIVED'],
        ]);

        $formattedEvents = []; // formater les events pour la compatibilité avec FullCalendar

        foreach ($ongoingEvents as $event) {
            $formattedEvents[] = [
                'id' => $event->getId(),
                'title' => $event->getName(),
                'start' => $event->getStartDate()->format('Y-m-d H:i:s'),
                'end' => $event->getEndTime()->format('Y-m-d H:i:s'),
                'slug' => $event->getSlug(),
            ];
        }
        // $response = new JsonResponse($formattedEvents);
        // dump($formattedEvents);die;

        return $this->render('event/index.html.twig', [
            'ongoingEvents' => $ongoingEvents,
            'pastEvents' => $pastEvents,
            'formattedEvents' => json_encode($formattedEvents), // Passer les données formatées en JSON à la vue
         
        ]);
    }

    // ^ Get events for the schedule
    public function getEventsSchedule(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findBy([
            'type' => 'EVENT',
            'status' => ['OPEN', 'PENDING', 'CLOSED'],
        ]);

        $formattedEvents = []; // formater les events pour les rendre compatibles avec FullCalendar

        foreach ($events as $event) {
            $formattedEvents[] = [
                'id' => $event->getId(),
                'name' => $event->getName(),
                'start' => $event->getStartDate()->format('Y-m-d H:i:s'),
                'end' => $event->getEndTime()->format('Y-m-d H:i:s'),
            ];
        }
        

        $jsonResponse = new JsonResponse($formattedEvents);
        dump($jsonResponse); die; 

        // Pour déboguer,  envoyer le contenu de la réponse directement dans le corps de la réponse HTTP
        // Cela peut être consulté dans l'onglet "Réseau" des outils de développement de votre navigateur
        // $jsonResponse->setContent(json_encode($formattedEvents));

        return $jsonResponse;
    }

    //^  new/edit event (admin)
    #[Route('/dashboard/event/new', name:'new_event', priority:1)]
    #[Route('/dashboard/event/edit/{id}', name:'edit_event', priority:1)]
    #[IsGranted("ROLE_ADMIN")]
    public function new_edit(Event $event = null, Request $request, PhotoRepository $photoRepo, PictureService $photoService, EntityManagerInterface $entityManager ) : Response
    {
        $isNewEvent = !$event;
        if(!$event) {
            $event = new Event();
        }

        $bannerExists = null;
        $previewExists = null;
        $eventId = $event->getId();
        $form= $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        $formPhoto = $this->createForm(PhotoFormType::class);      
        $formPhoto->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() ) {
            $event->setType('EVENT');
            $event = $form->getData();
            $event->setSlug($event->generateSlug());
            $entityManager->persist($event);
            $entityManager->flush();

            $eventId = $event->getId();
            $bannerDirectory = 'img/action/event/' . $eventId . '/banner';

            // ^ BANNER IMAGE
            $bannerFile = $form->get('banner')->getData();
            $bannerTitle = $form->get('titleBanner')->getData();
            $bannerAlt = $form->get('altDescriptionBanner')->getData();
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            

            if ($bannerFile) {
                $oldBanner = $photoRepo->findOneBy(['event' => $eventId, 'type' => 'banner']); 
                if (!in_array($bannerFile->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Wrong image format. Formats authorized: jpg, jpeg, png, webp');
                    return $this->redirectToRoute('new_event');
                }
                $maxSize = 2 * 1024 * 1024; // 2 Mo
                if ($bannerFile->getSize() > $maxSize) {
                    $this->addFlash('error', 'Image is too heavy. Maximum size allowed: 2MB');
                    return $this->redirectToRoute('new_event');
                }

                $newFilename = md5(uniqid(rand(), true)) . '.webp';
                if ($oldBanner) {
                    $oldBannerName = $oldBanner->getPath();
                    $bannerDirectory = 'img/action/event/' . $eventId . '/banner';
                    $absoluteOldBannerPath = $this->getParameter('kernel.project_dir') . '/public/' . $bannerDirectory . '/' . $oldBannerName;

                    $filesystem = new Filesystem();
                    if ($filesystem->exists($absoluteOldBannerPath)) {
                        $filesystem->remove($absoluteOldBannerPath);
                    }
                    $oldBanner->setPath($newFilename);

                } else {
                    // Si aucune bannière existante, créer le dossier "banner"

                $filesystem = new Filesystem();
                $filesystem->mkdir($bannerDirectory);

                $photo = new Photo();
                $photo->setTitle($bannerTitle);
                $photo->setAltDescription($bannerAlt);
                $photo->setEvent($event);
                $photo->setType('banner');
                $photo->setPath($newFilename);
                $entityManager->persist($photo);
                }
                
                // Déplacer la nouvelle image vers le dossier "banner"
                $bannerFile->move($bannerDirectory, $newFilename);
                $entityManager->flush();
            }

            // ^ PREVIEW IMAGE

            $previewFile = $form->get('preview')->getData();
            $previewTitle = $form->get('titlePreview')->getData();
            $previewAlt = $form->get('altDescriptionPreview')->getData();
            $allowedMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

            if ($previewFile) {
                $oldPreview = $photoRepo->findOneBy(['event' => $eventId, 'type' => 'preview']); 
                if (!in_array($previewFile->getMimeType(), $allowedMimeTypes)) {
                    $this->addFlash('error', 'Wrong image format. Formats authorized: jpg, jpeg, png, webp');
                    return $this->redirectToRoute('show_event', ['slug' => $event->getSlug()]);
                }
                $maxSize = 2 * 1024 * 1024; // 2 Mo
                if ($previewFile->getSize() > $maxSize) {
                    $this->addFlash('error', 'Image is too heavy. Maximum size allowed: 2MB');
                    return $this->redirectToRoute('new_event');
                }

                $newFilename = md5(uniqid(rand(), true)) . '.webp';
                $eventId = $event->getId();

                if ($oldPreview) {
                    $oldPreviewName = $oldPreview->getPath();
                    $previewDirectory = 'img/action/event/' . $eventId . '/banner';
                    $absoluteOldPreviewPath = $this->getParameter('kernel.project_dir') . '/public/' . $previewDirectory . '/' . $oldPreviewName;

                    $filesystem = new Filesystem();
                    if ($filesystem->exists($absoluteOldPreviewPath)) {
                        $filesystem->remove($absoluteOldPreviewPath);
                    }
                    $oldPreview->setPath($newFilename);

                } else {


                $filesystem = new Filesystem();
                $filesystem->mkdir($bannerDirectory);

                $photo = new Photo();
                $photo->setTitle($previewTitle);
                $photo->setAltDescription($previewAlt);
                $photo->setEvent($event);
                $photo->setType('preview');
                $photo->setPath($newFilename);
                $entityManager->persist($photo);
                }
                
                // Déplacer la nouvelle image vers le dossier "banner"
                $previewFile->move($bannerDirectory, $newFilename);
                $entityManager->flush();
            }

          

            $message = $isNewEvent ? 'Event created successfully!' : 'Event edited successfully!';
            $this->addFlash('success', $message);
            return $this->redirectToRoute('show_event', ['slug' => $event->getSlug()]);
        }


          // ^ GALLERY IMAGES
          $photosGallery = $photoRepo->findBy(['event' => $eventId, 'type' => 'photo']); 
          $maxImagesAllowed = 12;
          $numberOfImages = count($photoRepo->findBy(['event' => $eventId, 'type' => 'photo']));
          // check if the suer car upload  an img or not , return true or false 
          $canUploadImage = $numberOfImages < $maxImagesAllowed;

          $folder = $event->getName();
          
          
          if ($formPhoto->isSubmitted() && $formPhoto->isValid() && $numberOfImages < $maxImagesAllowed ) {
              $photoFile = $formPhoto->get('photo')->getData();
               // on appelle le service d'ajout
              if ($photoFile !== null) 
              {
                  $file = $photoService->add($photoFile, $folder, 500, 500);
                  $img = new Photo();
                  $img = $formPhoto->getData();
                  $img->setPath($file);
                  $img->setType('photo');
                  $img->setEvent($event);
                  $entityManager->persist($img);
                  $entityManager->flush();   
                  
                  $this->addFlash('success', 'Your photo has been successfully added');
                  return $this->redirectToRoute('show_event', ['slug' => $event->getSlug()]);
              }           
          

            } else {
                // $this->addFlash('error', 'Maximum image limit reached. Please delete some before adding more.');
                // return $this->redirectToRoute('manage_profil', ['slug' => $photographer->getSlug()]);
            }


        return $this->render('dashboard/newEvent.html.twig', [
            'formAddEvent' => $form,
            'edit' =>$event->getId(),
            'maxImagesAllowed' => $maxImagesAllowed,
            'canUploadImage' => $canUploadImage,
            'formAddPhotoGallery' => $formPhoto,
            'photosGallery' => $photosGallery,
            'event' =>$event,

            'bannerExists' => $bannerExists,
            'previewExists' => $previewExists,
        ]);
    }

    // ^ show event (admin)
    #[Route('/dashboard/event/{slug}', name: 'show_event_admin')]
    #[IsGranted("ROLE_ADMIN")]
    public function show_admin(Event $event = null, EventParticipationRepository $eventParticipationRepository, Security $security): Response 
    {
        
        return $this->render('dashboard/showEvent.html.twig', [
            'event' => $event,
        ]);
    }
    

    
    // & show event
    // Définit une route pour afficher un événement en utilisant son slug
    #[Route('archived/event/{slug}', name: 'show_archived_event')]
    #[Route('/event/{slug}', name: 'show_event')]
    public function show(Event $event = null, User $user = null, EventRepository $eventRepository,  EventParticipationRepository $eventParticipationRepository, Security $security): Response 
    {
        // Récupérer l'objet AEvent (événement) en fonction du slug passé en paramètre
        $event = $eventRepository->findOneBy(['slug' => $event->getslug()]);
        // vérifier si l'event (ici un EVENT et non EXPO ou autre chose) existe 
        if (!$event) {
            // Si non, rediriger vers la page d'erreur
            return $this->render('error/error404.html.twig', [], new Response('', Response::HTTP_NOT_FOUND));
        }
        $eventId = $event->getId();
        // Vérifier si un utilisateur est connecté
        if($user = $security->getUser()) {
            $userId = $user->getId();
            $existingParticipation = [];
            // Vérifier si l'utilisateur a déjà une participation à cet événement
            $hasExistingParticipation = $eventParticipationRepository->findOneBy(['user' => $user->getId(), 'event' => $eventId]);
            $existingParticipation = $hasExistingParticipation !== null;

            // Retourner la vue de l'événement avec l'information sur la participation
            return $this->render('event/show.html.twig', [
                'event' => $event,
                'existingParticipation' => $existingParticipation
            ]);
        }
        // Si aucun utilisateur n'est connecté, simplement retourner la vue de l'événement
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }


    // ^ Delete Event (admin)
    #[Route('/dashboard/event/{slug}/delete', name: 'delete_event')]
    #[IsGranted("ROLE_ADMIN")]
    public function delete_event(Event $event, EntityManagerInterface $entityManager) :Response 
    {
        $entityManager->remove($event);
        $entityManager->flush();
        
        $this->addFlash('success', 'The event has been successfully deleted');
        return $this->redirectToRoute('app_dashboard');
    }

    // ^ Delete Photo
    #[Route('/delete/photo/event/{id}', name: 'delete_event_photo')]
    public function deletePhotoEvent( Security $security, Photo $photo, Request $request, EntityManagerInterface $entityManager, PhotoService $photoService ): Response
    {
       
        $eventId = $photo->getEvent()->getId();
        $name = $photo->getPath();
        

        if($photoService->delete($name, $eventId , 500, 500)) {
            //on supprime l'image de la base données
            $entityManager->remove($photo);
            $entityManager->flush();


            $this->addFlash('success', 'Image deleted successfully.'); // Message flash de succès

            return $this->redirectToRoute('edit_event', ['id' => $eventId]);
        }
        return $this->redirectToRoute('edit_event', ['id' => $eventId]);
    }


    // ^ AJAX - all archived events
    #[Route('/all-past-events', name: 'all-past-events', methods: ['POST'])]
    public function getPastEvents(Request $request, EventRepository $eventRepository)
    {
        $pastEvents = $eventRepository->findBy(
            [
                'type' => 'EVENT',
                'status' => ['ARCHIVED'],
            ],
            ['startDate' => 'DESC'] 
        );


        // Convert objects to associative arrays
        $eventsArray = [];
        foreach ($pastEvents as $event) {
            $formattedStartDate = $event->getStartDate()->format('d-m-Y');
            $formattedEndDate = $event->getEndDate()->format('d-m-Y');
            $eventsArray[] = [
                'id' => $event->getId(),
                'name' => $event->getName(),
                'slug' => $event->getSlug(),
                'startDate' => $formattedStartDate,
                'endTime' => $formattedEndTime,
                'type' => $event->getType(),
            ];
        }

        // Convert associative array to JSON and send response
        return new JsonResponse($eventsArray);
    }
    
    // ^ AJAX - all archived content
    #[Route('/all-past-content', name: 'all-past-content', methods: ['POST'])]
    public function getPastContent(Request $request, EventRepository $eventRepository)
    {
        $pastContent = $eventRepository->getAllPastContent();

        
        // Convert objects to associative arrays
        $pastContentArray = [];
        foreach ($pastContent as $content) {
            $formattedStartDate = $content->getStartDate()->format('d-m-Y');
            $formattedEndTime = $content->getEndTime()->format('d-m-Y');

            $type = null;
            if ($content instanceof Event) {
                $type = $content->getType();
            }

            $pastContentArray[] = [
                'id' => $content->getId(),
                'name' => $content->getName(),
                'slug' => $content->getSlug(),
                'startDate' => $formattedStartDate,
                'endTime' => $formattedEndTime,
                'type' => $type,
            ];
        }

        // Convert associative array to JSON and send response
        return new JsonResponse($pastContentArray);
    }
}