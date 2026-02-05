<?php

namespace App\Controller\Admin;

use App\Entity\Challenge;
use App\Repository\ChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin')]
final class ChallengeController extends AbstractController
{

    /**
     * affiche la listes des challenges avec systeme de filtre et recherche
     * @param ChallengeRepository $challengeRepository
     * @param Request $request
     * @return Response
     */
    #[Route('/challenge', name: 'app_admin_challenge')]
    public function index(ChallengeRepository $challengeRepository, Request $request): Response
    {

        // on recupère les parametre de recherche ou de tri depuis l'url
        $search = $request->query->get('search', '');
        $filter = $request->query->get('filter', 'all'); // all, active, inactive

        //on recupère tous les challenges trie du plus recent au plus ancien
        $challenges = $challengeRepository->findBy([], ['createdAt' => 'DESC']);

        // Filtre de tri
        if ($filter === 'active') {
            $challenges = array_filter($challenges, fn($c) => $c->isActive());
        } elseif ($filter === 'inactive') {
            $challenges = array_filter($challenges, fn($c) => !$c->isActive());
        }


        //Recherche
        if ($search) {
            $challenges = array_filter($challenges, function ($challenge) use ($search) {
                return stripos($challenge->getTitle(), $search) !== false
                    || stripos($challenge->getDescription(), $search) !== false
                    || stripos($challenge->getCategory()->getLabel(), $search) !== false;
            });
        }

        //reindexer le tableau après filtrage
        $challenges = array_values($challenges);

        return $this->render('admin/challenge/index.html.twig', [
            'challenges' => $challenges,
            'search' => $search,
            'filter' => $filter
        ]);












        return $this->render('admin/challenge/index.html.twig', [
            'controller_name' => 'ChallengeController',
        ]);
    }

    /**
     * affiche le detail du defi
     * @param Challenge $challenge
     * @return Response
     */
    #[Route('/challenge/{id}', name: 'app_admin_challenge_show')]

    public function show(Challenge $challenge): Response
    {
        return $this->render('admin/challenge/show.html.twig', [
            'challenge' => $challenge
        ]);
    }


    /**
     * activer desactiver un challenge
     * @param Challenge $challenge
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route('/challenge/{id}/toggle-active', name: 'app_admin_challenge_toggle_active', methods: ['POST'])]
    public function challengeToggleActive(Challenge $challenge, Request $request, EntityManagerInterface $entityManager): Response
    {
        // verifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_challenge_' . $challenge->getId(), $token)) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_admin_challenge_show', ['id' => $challenge->getId()]);
        }


        $challenge->setIsActive(!$challenge->isActive());
        $entityManager->flush();


        $this->addFlash('success', sprintf(
            'Le challenge %s a été %s avec succès.',
            $challenge->getTitle(),
            $challenge->isActive() ? 'activé' : 'désactivé'
        ));
        return $this->redirectToRoute('app_admin_challenge_show', ['id' => $challenge->getId()]);
    }
    /**
     * supprimer un challenge 
     * @param Challenge $challenge
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return Response
     */

    #[Route('/challenge/{id}/delete', name: 'app_admin_challenge_delete', methods: ['POST'])]
    public function challengeDelete(
        Challenge $challenge,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // verifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_challenge_' . $challenge->getId(), $token)) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_admin_challenge_show', ['id' => $challenge->getId()]);
        }

        // Ne pas permettre de supprimer son propre compte
        if ($challenge === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte');
            return $this->redirectToRoute('app_admin_challenge_show', ['id' => $challenge->getId()]);
        }

        $challenge->setIsActive(false); // Désactiver le compte avant de le supprimer
        $challenge->setUpdatedAt(new \DateTime()); // Mettre à jour la date de modification

        $entityManager->persist($challenge);
        $entityManager->flush();
        $this->addFlash('success', "Le challenge a été supprimé avec succès.");


        return $this->redirectToRoute('app_admin_challenge');
    }
}
