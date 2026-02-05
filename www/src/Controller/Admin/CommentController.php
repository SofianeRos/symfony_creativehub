<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin')]
final class CommentController extends AbstractController
{
    #[Route('/comment', name: 'app_admin_comment')]

    public function index(CommentRepository $commentRepository, Request $request): Response

    {
        $search = $request->query->get('search', '');
        $comments = $commentRepository->findBy([], ['createdAt' => 'DESC']);


        // recherche

        if ($search) {
            $comments = array_filter($comments, function ($comment) use ($search) {
                return stripos($comment->getContent(), $search) !== false
                    || stripos($comment->getUser()->getPseudo(), $search) !== false;
            });
        }

        $comments = array_values($comments);


        return $this->render('admin/comment/index.html.twig', [
            'comments' => $comments,
            'search' => $search,

        ]);
    }

    #[Route('/comment/{id}/delete', name: 'app_admin_comment_delete', methods: ['POST'])]

    public function delete(Comment $comment, EntityManagerInterface $entityManager, Request $request): Response

    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_comment_' . $comment->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide. Le commentaire n\'a pas été supprimé.');
            return $this->redirectToRoute('app_admin_comment');
        }
        $entityManager->remove($comment);
        $entityManager->flush();
        $this->addFlash('success', 'Commentaire supprimé avec succès.');
        return $this->redirectToRoute('app_admin_comment');
    }
}
