<?php

namespace App\Controller;

use DateTime;
use App\Entity\Challenge;
use App\Entity\Comment;
use App\Entity\Media;
use App\Form\ChallengeType;
use App\Form\CommentType;
use App\Repository\CategoryRepository;
use App\Repository\ChallengeRepository;
use App\Repository\VoteRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/challenge')]
final class ChallengeController extends AbstractController
{
    #[Route(name: 'app_challenge_index', methods: ['GET'])]
    public function index(ChallengeRepository $challengeRepository, CategoryRepository $categoryRepository, Request $request): Response
    {
        //récuperation des paramètre de tri et filtre soumis par l'utilisateur
        $categoryId = $request->query->getInt('category', 0);
        $sortBy = $request->query->get('sort', 'recent');

        //récuperation de toute les cathegories pour pouvoir les afficher
        $categories = $categoryRepository->findAll();

        // récuperation des challenges avec possibilité de filtre
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
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $challenge = new Challenge();
        $form = $this->createForm(ChallengeType::class, $challenge);
        $form->handleRequest($request);

        // definir l'auteur aprés handleRequest() mais avant isValid()
        // car handleRequest() peut réinitialiser les valeurs non présentes dans le formulaire
        if ($form->isSubmitted()) {
            $challenge->setUser($this->getUser());
        }

        if ($form->isSubmitted() && $form->isValid()) {

            //definir les autres propriétés
            $challenge->setCreatedAt(new DateTime());
            $challenge->setUpdatedAt(new DateTime());
            $challenge->setIsActive(true);

            // sauvegarder le challenge d'abord pour avoir un ID
            $entityManager->persist($challenge);
            $entityManager->flush();

            // gerer l'upload des fichiers
            $files = $form->get('files')->getData();
            if ($files) {
                foreach ($files as $file) {
                    try {
                        // upload du fichier
                        $filename = $fileUploader->upload($file, 'challenges');

                        // on enregistre en bdd Les medias
                        $media = new Media();
                        $media->setPath($filename);

                        $entityManager->persist($media);
                        $challenge->addMedia($media);
                    } catch (Exception $e) {
                        $this->addFlash('error', "Erreur lors de l'upload d'un fichier :" . $e->getMessage());
                    }
                }
                $entityManager->persist($challenge);
            }

            $entityManager->flush();
            $this->addFlash('success', "Votre défis a été crée avec succès");
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

        //vérifier si l'utilisateur a deja voté
        $hasVoted = false;

        if ($this->getUser()) {
            $hasVoted = $voteRepository->findOneBy([
                'user' => $this->getUser(),
                'challenge' => $challenge
            ]) !== null;
        }

        // formulaire de commentaire principal
        $comment = new Comment;
        $commentForm = $this->createForm(CommentType::class, $comment);

        // Recuperer les commentaires principeaux (sans parent)
        $comments = $challenge->getComments()->filter(function (Comment $comment) {
            return $comment->getParentComment() === null;
        })->toArray();

        // trier les commentaires par date (plus recent en premier)
        usort($comments, function (Comment $a, Comment $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });

        if (!$challenge) {
            $this->addFlash('error', "Votre défi n'existe pas.");

            return $this->redirectToRoute('app_challenge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('challenge/show.html.twig', [
            'challenge' => $challenge,
            'hasVoted' => $hasVoted,
            'voteCount' => $challenge->getVotes()->count(),
            'commentForm' => $commentForm,
            'comments' => $comments
        ]);
    }

    #[Route('/{id}/edit', name: 'app_challenge_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Challenge $challenge, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        //Vérifier que l'utilisateur est l'auteur du challenge
        if ($challenge->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', "Vous n'avez pas l'autorisation de modifier ce défi");
            return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()], Response::HTTP_FORBIDDEN);
        }

        $form = $this->createForm(ChallengeType::class, $challenge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Mettre a jour la date de modification
            $challenge->setUpdatedAt(new DateTime());

            //gerer l'upload des nouveau média
            $files = $form->get('files')->getData();
            if ($files) {
                foreach ($files as $file) {
                    try {
                        // upload du fichier
                        $filename = $fileUploader->upload($file, 'challenges');

                        // on enregistre en bdd Les medias
                        $media = new Media();
                        $media->setPath($filename);

                        $entityManager->persist($media);
                        $challenge->addMedia($media);
                    } catch (Exception $e) {
                        $this->addFlash('error', "Erreur lors de l'upload d'un fichier :" . $e->getMessage());
                    }
                }
                $entityManager->persist($challenge);
            }


            $entityManager->flush();

            $this->addFlash('success', "Votre défis a été modifié avec succès");
            return $this->redirectToRoute('app_challenge_index', [], Response::HTTP_SEE_OTHER);
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
        // Vérifier que l'utilisateur est bien l'auteur dui defis
        if ($challenge->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous n\'avez pas l\'autorisation de supprimer ce défi.');
            return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()], Response::HTTP_FORBIDDEN);
        }

        //Vérifier le token
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_challenge_' . $challenge->getId(), $token)) {
            $this->addFlash('error', "Token CSRF invalide.");
            return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()], Response::HTTP_FORBIDDEN);
        }


        //soft delete
        $challenge->setIsActive(false);
        $challenge->setUpdatedAt(new DateTime());

        $entityManager->flush();
        $this->addFlash('success', "Votre défi a été supprimé avec succès.");

        return $this->redirectToRoute('app_challenge_index', [], Response::HTTP_SEE_OTHER);
    }
}
