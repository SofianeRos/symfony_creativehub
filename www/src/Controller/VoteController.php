<?php

namespace App\Controller;

use App\Entity\Vote;
use App\Repository\VoteRepository;
use App\Repository\ChallengeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class VoteController extends AbstractController
{
    /**
     * methode pour ajouter un vote à un challenge
     * @param int $id L'id du challenge à voter
     * @param VoteRepository $voteRepository
     * @param ChallengeRepository $challengeRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/challenge/{id}/vote', name: 'app_vote', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addVote(
        int $id,
        VoteRepository $voteRepository,
        ChallengeRepository $challengeRepository,
        EntityManagerInterface $entityManager,
    ) {
        $user = $this->getUser();
        $challenge = $challengeRepository->findActive($id);

        // verifier si l'utilisateur a deja vote 
        $existingVote = $voteRepository->findOneBy([
            'user' => $user,
            'challenge' => $challenge
        ]);
        if ($existingVote) {
            return new JsonResponse([
                'success' => 'false',
                'message' => 'Vous avez déjà voté pour ce défi.',
                'voteCount' => $challenge->getVotes()->count()
            ], Response::HTTP_BAD_REQUEST);
        }
        // creation du vote 
        $vote = new Vote();
        $vote->setUser($user);
        $vote->setChallenge($challenge);
        $vote->setCreatedAt(new \DateTime());
        $entityManager->persist($vote);
        $entityManager->flush();

        // recharger le challenge pour avoir le bon nombre de vote 
        $entityManager->refresh($challenge);

        return new JsonResponse([
            'success' => 'true',
            'message' => 'Vote ajouté avec succès.',
            'voteCount' => $challenge->getVotes()->count()
        ]);
    }
    /**
     * methode pour ajouter un vote à un challenge
     * @param int $id L'id du challenge à voter
     * @param VoteRepository $voteRepository
     * @param ChallengeRepository $challengeRepository
     * @param EntityManagerInterface $entityManager
     * @return JsonResponse
     */
    #[Route('/challenge/{id}/vote', name: 'app_vote_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function removeVote(
        int $id,
        VoteRepository $voteRepository,
        ChallengeRepository $challengeRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        // recupere l'utilisateur et le challenge
        $user = $this->getUser();
        $challenge = $challengeRepository->findActive($id);

        // verifier si l'utilisateur a deja vote 
        $vote = $voteRepository->findOneBy([
            'user' => $user,
            'challenge' => $challenge
        ]);

        if(!$vote){
            return new JsonResponse([
                'success' => 'false',
                'message' => 'Vous n\'avez pas voté pour ce défi.',
                'voteCount' => $challenge->getVotes()->count()
            ], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->remove($vote);
        $entityManager->flush();
        // recharger le challenge pour avoir le bon nombre de vote
        $entityManager->refresh($challenge);

        return new JsonResponse([
            'success' => 'true',
            'message' => 'Vote retiré avec succès.',
            'voteCount' => $challenge->getVotes()->count()
        ]);
    }
}
