<?php

namespace App\Controller;

use DateTime;
use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\ChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommentController extends AbstractController
{
    #[Route('/challenge/{id}/comment', name: 'app_comment_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        int $id,
        ChallengeRepository $challengeRepository,
        EntityManagerInterface $entityManager,
        Request $request,
    ) {

        // on recupere le challenge actif
        $challenge = $challengeRepository->findActive($id);
        // on recupere le user
        $user = $this->getUser();
        // on cree  un objet comment
        $comment = new Comment();
        // on peut deja setter user et challenge dans comment
        $comment->setUser($user);
        $comment->setChallenge($challenge);


        // on recupere les data passe dans le formulaire
        $conmentData = $request->request->all('comment');
        // on stock le token passe oar le formulaire
        $submittedTokken = $conmentData['_token'] ?? null;
        // on stock le parentComment si il existe 
        $parentId = $conmentData['parentComment'] ?? null;
        // on stock le content du commentaire
        $content = $conmentData['content'] ?? null;

        // verifier si cest une reponse (formulaire html simple) ou un commentaire principal (formulaire symfony)
        $isReplyForm = $parentId && $parentId !== '' && is_numeric($parentId);

        if ($isReplyForm) {
            //  pour les reponses validation manuelle du token csrf et du contenu 
            if (!$submittedTokken || !$this->isCsrfTokenValid('submit', $submittedTokken)) {
                $this->addFlash('error', 'Token CSRF invalide veuillez réessayer.');
                return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()]);
            }

            // on verifie que le contenue n'est pas vide 
            if (empty(trim($content))) {
                $this->addFlash('error', 'Le contenu du commentaire ne peut pas être vide.');
                return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()]);
            }
            // on verifie que le contenue ne depasse pas les 5000 caracteres
            // on verifie que le contenue n'est pas vide 
            if (strlen($content) > 5000) {
                $this->addFlash('error', 'Le contenu du commentaire ne peut pas dépasser 5000 caractères.');
                return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()]);
            }
            // on peut setter les informations a comment
            $comment->setContent($content);
            $comment->setCreatedAt(new DateTime());
            $comment->setUpdatedAt(new DateTime());

            // definir le parent 
            $parentComment = $entityManager->getRepository(Comment::class)->find($parentId);
            if ($parentComment && $parentComment->getChallenge() === $challenge) {
                $comment->setParentComment($parentComment);
            }

            $entityManager->persist($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Votre commentaire a été ajoutée avec succès.');
            return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()]);
        }

        // pour les commentaires principaux : utiliser le formulaire symfony
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // on definit la date de creation
            $comment->setCreatedAt(new DateTime());
            $comment->setUpdatedAt(new DateTime());

            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Votre commentaire a été ajoutée avec succès.');
            return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
          $errors =[];
            foreach ($form->getErrors(true , false) as $error) {
                $errors[] = $error->getMessage();
            }

            // on ajoute les erreurs de validation des champs 
            foreach ($form->all() as $child) {
                foreach ($child->getErrors() as $error) {
                    $errors[] = $error->getMessage();
                }
            }

            // message d'erreur par defaut si aucune erreur precise n'est recuperee
            if (empty($errors)) {
                $errors[] = 'Une erreur est survenue lors de la soumission du formulaire. Veuillez réessayer.';
            }
            $this->addFlash('error', "Erreur lors de l'ajout d'un commentaire : " . implode(', ', $errors));    
    }
        return $this->redirectToRoute('app_challenge_show', ['id' => $challenge->getId()]);
    }
}
