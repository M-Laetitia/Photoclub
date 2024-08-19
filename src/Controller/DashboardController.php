<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\User;
use App\Form\SearchUserType;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Form\PublishedPhotographerPageType;
use Doctrine\ORM\EntityManagerInterface;


class DashboardController extends AbstractController
{
   //#[Route('/dashboard', name: 'app_dashboard')]
   // public function index(): Response
   // {
   //     return $this->render('dashboard/index.html.twig', [
   //         'controller_name' => 'DashboardController',
   //     ]);
   // }

// dashboard's view 
#[Route('/admin/dashboard', name: 'app_dashboard')]
    #[IsGranted("ROLE_ADMIN")]
    public function index(UserRepository $userRepository, EventRepository $eventRepository) : Response
    {
        $users = $userRepository->findAll();
        $usersCount= $userRepository->count([]);
        $currentExpos =  $eventRepository->getCurrentExpoEvents('EXPO');
        $currentEvents =  $eventRepository->getCurrentExpoEvents('EVENT');
        $photographers= $userRepository->findUsersbyRole('ROLE_PHOTOGRAPHER');
        $photographersCount = count($photographers);
        $photographerspageCount = $userRepository->count(['isPublished' => 1]);
        $usersLoggedThisWeek = $userRepository->countUsersLoggedInThisWeek();
        
        
        return $this->render('dashboard/index.html.twig', [
            'currentEvents' => $currentEvents,
            'currentExpos' => $currentExpos,
            'usersCount' => $usersCount,
            'photographersCount' => $photographersCount, 
            'photographerspageCount' => $photographerspageCount,
            'usersLoggedThisWeek' => $usersLoggedThisWeek, 
        ]);
    }

    // user's listing
    #[Route('/admin/dashboard/index', name: 'list_users')]
    #[IsGranted("ROLE_ADMIN")]
    public function list_users(UserRepository $userRepository, Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {

        $formUserSearch = $this->createForm(SearchUserType::class);
        $formUserSearch->handleRequest($request);
        $searchResults = [];
        $role = $request->query->get('role');
        $sortBy = $request->query->get('sortBy');
        $photographers = $userRepository->findBy(['roles' => 'ROLE_PHOTOGRAPHER']);
        $users = $userRepository->findBy([], ['pseudo' => 'ASC']);


        $redirectToSamePage = false; // Flag to determine if redirection is needed
        if ($role) {
            $searchResults = $userRepository->findUsersbyRole($role);
            $redirectToSamePage = true;
        } elseif ($formUserSearch->isSubmitted() && $formUserSearch->isValid()) {
            $pseudo = $formUserSearch->get('pseudo')->getData();
            $searchResults = $userRepository->findPhotographerByPseudo($pseudo);
            $redirectToSamePage = true;
        } elseif ($sortBy) {
            if ($sortBy === 'pseudo_asc') {
                $searchResults = $userRepository->findBy([], ['pseudo' => 'ASC']);
                $redirectToSamePage = true;
            } elseif ($sortBy === 'pseudo_desc') {
                $searchResults = $userRepository->findBy([], ['pseudo' => 'DESC']);
                $redirectToSamePage = true;
            } elseif ($sortBy === 'created_at_asc') {
                $searchResults = $userRepository->findBy([], ['registrationDate' => 'ASC']);
                $redirectToSamePage = true;
            } elseif ($sortBy === 'created_at_desc') {
                $searchResults = $userRepository->findBy([], ['registrationDate' => 'DESC']);
                $redirectToSamePage = true;
            }
        }
 
        if ($redirectToSamePage) {
            // Store search results in session if needed
            $session->set('searchResults', $searchResults);
            // Redirect to the same page to avoid form resubmission
            return $this->redirectToRoute('list_users');
        }
    
        // Retrieve search results from session
        if ($session->has('searchResults')) {
            $searchResults = $session->get('searchResults');
            $session->remove('searchResults'); // Remove search results from session after use
        }

        // $users = $userRepository->findBy([], ['pseudo' => 'ASC']);
        return $this->render('dashboard/indexUsers.html.twig', [
            // 'users' => $users,
            'users' => $searchResults ?: $users, // Use all users if no search result
            'formUserSearch' => $formUserSearch->createView(),
            'searchResults' => $searchResults ? true : false, // Set to true if there are search results, otherwise false
            
        ]);
    }

    // user's details
    #[Route('/admin/dashboard/user/{slug}/detail_user', name: 'detail_user_admin')]
    #[IsGranted("ROLE_ADMIN")]
    public function show_user(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {

        $formPage = $this->createForm(PublishedPhotographerPageType::class);
        $formPage->handleRequest($request);


        if ($formPage->isSubmitted() && $formPage->isValid() ) {

                if ($this->isGranted('ROLE_ADMIN')) {
                        if ($user->getIsPublished() == 1) {
                            $user->setIsPublished(-1);
                            $message = 'Photographer page successfully moderated!';
                        } else {
                            $user->setIsPublished(1);
                            $message = 'Photographer page successfully published!';
                        }
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $this->addFlash('success', $message);
                        return $this->redirectToRoute('detail_user_admin',  ['slug' => $user->getSlug()]);

                    
                } else {
                    $this->addFlash('error', 'You have not any right to modify this photographer page.');
                    return $this->redirectToRoute('app_home');

                } 
        }

        return $this->render('dashboard/showUser.html.twig', [
            'user' => $user,
            'formPublishPagePhotographer' => $formPage,
        ]);
    }

    // ^ event's listing
    #[Route('/admin/dashboard/events', name: 'list_events')]
    #[IsGranted("ROLE_ADMIN")]
    public function indexEvents(EventRepository $eventRepository): Response
    {
 
        $events = $eventRepository->findBy(['type' => 'EVENT']);
        $ongoingEvents = $eventRepository->findBy([
             'type' => 'EVENT',
             'status' => ['OPEN', 'PENDING', 'CLOSED'],
        ]);
        $pastEvents = $eventRepository->findBy([
            'type' => 'EVENT',
            'status' => ['ARCHIVED'],
        ]);

         return $this->render('dashboard/indexEvents.html.twig', [
             'events' => $events,
             'ongoingEvents' => $ongoingEvents,
             'pastEvents' => $pastEvents,

         ]);
    }

    // ^ exposition's listing
    #[Route('/admin/dashboard/expositions', name: 'list_expos')]
    #[IsGranted("ROLE_ADMIN")]
    public function indexExpos(EventRepository $eventRepository): Response
    {
    
        $events = $eventRepository->findBy(['type' => 'EXPO']);
        $ongoingEvents = $eventRepository->findBy([
                'type' => 'EXPO',
                'status' => ['OPEN', 'PENDING', 'CLOSED'],
        ]);
        $pastEvents = $eventRepository->findBy([
            'type' => 'EXPO',
            'status' => ['ARCHIVED'],
        ]);

            return $this->render('dashboard/indexExpos.html.twig', [
                'events' => $events,
                'ongoingEvents' => $ongoingEvents,
                'pastEvents' => $pastEvents,

            ]);
    }

}
