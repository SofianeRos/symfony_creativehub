<?php

namespace App\Controller\Admin;

use App\Repository\CategoryRepository;
use App\Repository\ChallengeRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use App\Repository\VoteRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]

final class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        ChallengeRepository $challengeRepository,
        VoteRepository $voteRepository,
        CommentRepository $commentRepository,
        CategoryRepository $categoryRepository

    ): Response {

        //statistique globale
        $stats = [
            'users' => [
                'total' => $userRepository->count([]),
                'active' => $userRepository->count(['isActive' => true]),
                'admins' => count(array_filter($userRepository->findAll(), fn($u) => in_array('ROLE_ADMIN', $u->getRoles()))),
            ],
            'challenges' => [
                'total' => $challengeRepository->count([]),
                'active' => $challengeRepository->count(['isActive' => true]),
            ],
            'votes' => $voteRepository->count([]),
            'comments' => $commentRepository->count([]),
            'categories' => $categoryRepository->count([]),
        ];

        // recuperer les 5 defis les plus recents
        $recentChallenges = $challengeRepository->findBy(['isActive' => true], ['createdAt' => 'DESC'], 5);

        // recuperer les dernier utilisateur inscrits
        $recentUsers = $userRepository->findBy(['isActive' => true], ['createdAt' => 'DESC'], 5);



        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recentChallenges' => $recentChallenges,
            'recentUsers' => $recentUsers,
        ]);
    }
}
