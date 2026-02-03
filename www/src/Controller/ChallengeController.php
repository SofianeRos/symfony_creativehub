<?php

namespace App\Controller;

use DateTime;
use App\Entity\Comment;
use App\Entity\Challenge;
use App\Form\CommentType;
use App\Form\ChallengeType;
use App\Repository\VoteRepository;
use App\Repository\CategoryRepository;
use App\Repository\ChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/challenge')]
final class ChallengeController extends AbstractController
{
    #[Route(name: 'app_challenge_index', methods: ['GET'])]
    public function index(ChallengeRepository $challengeRepository, CategoryRepository $categoryRepository, Request $request): Response
    {
        // recupération des paramètres de tri et filtre soumis par l'utilisateur
        $categoryId = $request->query->getInt('category', 0);
        $sortBy = $request->query->get('sort', 'recent');

        //recuperation de toutes les catégories pour pouvoir les afficher (pout filtre)
        $categories = $categoryRepository->findAll();

        //recupération des challenges avec possibilité de filtres
        $challenges = $challengeRepository->findAllWithFilters($categoryId, $sortBy);

        // Compter les votes et les commentaires pour chaque challenge
        $challengeStat = [];
        foreach ($challenges as $challenge) {
            $challengeStat[$challenge->getId()] = [
                'voteCount' => $challenge->getVotes()->count(),
                'commentCount' => $challenge->getComments()->count()

            ];
        }

        return $this->render('challenge/index.html.twig', [
            'challenges' => $challenges,
            'challengeStat' => $challengeStat,
            'categories' => $categories,
            'selectCategory' => $categoryId,
            'selectSort' => $sortBy
        ]);
    }

    #[Route('/new', name: 'app_challenge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $challenge = new Challenge();
        $form = $this->createForm(ChallengeType::class, $challenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($challenge);
            $entityManager->flush();

            return $this->redirectToRoute('app_challenge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('challenge/new.html.twig', [
            'challenge' => $challenge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_challenge_show', methods: ['GET'])]
    public function show(int $id, ChallengeRepository $challengeRepository, VoteRepository $voteRepository): Response
    {

        $challenge = $challengeRepository->findActive($id);

        //! Verifier si l'utilisateur a deja voter 
        $hasVoted = false;
        if ($this->getUser()) {
            $hasVoted = $voteRepository->findOneBy([
                'user' => $this->getUser(),
                'challenge' => $challenge
            ])  !== null;
        }

        if (!$challenge) {
            $this->addFlash('error', "Ce défi n'existe pas");
            return $this->redirectToRoute('app_challenge_index', [], Response::HTTP_SEE_OTHER);
        }

        // recuperer les formulaires de commentaire principal 

        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);


        // recuperer les commentaire principaux (sans parent)
        $comments = $challenge->getComments()->filter(function (Comment $comment) {
            return $comment->getParentComment() === null;
        })->toArray();

        // trier les commentaires par date 
        usort($comments, function (Comment $a, Comment $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        return $this->render('challenge/show.html.twig', [
            'challenge' => $challenge,
            'hasVoted' => $hasVoted,
            'voteCount' => $challenge->getVotes()->count(),
            'commentForm' => $commentForm,
            'comments' => $comments,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_challenge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChallengeType::class, $challenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_challenge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('challenge/edit.html.twig', [
            'challenge' => $challenge,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_challenge_delete', methods: ['POST'])]
    public function delete(Request $request, Challenge $challenge, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur est bien l'auteur du défi
        if ($challenge->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', "Vous n'avez pas l'autorisation de supprimer ce défi.");
            return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()], Response::HTTP_FORBIDDEN);
        }

        // verifier le token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_challenge_' . $challenge->getId(), $token)) {
            $this->addFlash('error', "Token CSRF invalide.");
            return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()], Response::HTTP_FORBIDDEN);
        }

        //Soft delete
        $challenge->setIsActive(false);
        $challenge->setUpdatedAt(new DateTime());

        $entityManager->flush();
        $this->addFlash('success', "Votre défi a été supprimé avec succès.");

        return $this->redirectToRoute('app_challenge_index', [], Response::HTTP_SEE_OTHER);
    }
}
