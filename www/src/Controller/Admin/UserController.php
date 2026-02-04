<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/admin')]
final class UserController extends AbstractController
{
    #[Route('/user', name: 'app_admin_user')]
    public function index(UserRepository $userRepository, Request $request): Response
    {


        $search = $request->query->get('search', '');
        $filter = $request->query->get('filter', 'all');

        $users = $userRepository->findAll();

        //filtre de tri 

        if ($filter === 'active') {
            $users = array_filter($users, fn($u) => $u->isActive());
        } elseif ($filter === 'inactive') {
            $users = array_filter($users, fn($u) => !$u->isActive());
        } elseif ($filter === 'admins') {
            $users = array_filter($users, fn($u) => in_array('ROLE_ADMIN', $u->getRoles()));
        }

        //filtre de recherche
        if ($search) {
            $users = array_filter($users, function($user) use ($search) {
                return stripos($user->getPseudo(), $search) !== false ||
                       stripos($user->getEmail(), $search) !== false ;
            });
        }

        // reindexer le tableau après le filtrage
        $users = array_values($users);


        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'filter' => $filter
        ]);
    }

    #[Route('/user/{id}', name: 'app_admin_user_show')]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }
}
