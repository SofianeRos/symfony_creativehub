<?php

namespace App\DataFixtures;

use DateTime;
use App\Entity\User;
use App\Entity\Media;
use App\Entity\Category;
use App\Entity\Challenge;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher) {}


    public function load(ObjectManager $manager): void
    {
        $this->loadCategorys($manager);
        $this->loadUser($manager);
        $this->loadMedia($manager);
        $this->loadChallenge($manager);





        $manager->flush();
    }

    public function loadCategorys(ObjectManager $manager)
    {
        $arrayCategory = ['Dessin', 'Photo', 'Code', 'Projet'];

        foreach ($arrayCategory as $value) {
            $category = new Category();
            $category->setLabel($value);


            $manager->persist($category);

            $this->addReference("category_" . $value, $category);
        }
    }

    public function loadUser(ObjectManager $manager)
    {
        $admin = new User();
        $admin->setEmail('admin@admin.com');
        $admin->setPseudo('Admin');
        $admin->setPassword(
            $this->passwordHasher->hashPassword(
                $admin,
                'admin'
            )
        );
        $admin->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $admin->setCreatedAt(new \DateTime());
        $admin->setIsActive(true);
        $admin->setBio('Administrateur de CreativeHub');

        $manager->persist($admin);


        //! Creation Utilisateur simple

        $arrayUser = [
            ['email' => 'user1@user.com', 'pseudo' => 'User1'],
            ['email' => 'user2@user.com', 'pseudo' => 'User2'],
            ['email' => 'user3@user.com', 'pseudo' => 'User3'],
            ['email' => 'user4@user.com', 'pseudo' => 'User4'],
            ['email' => 'user5@user.com', 'pseudo' => 'User5'],

        ];

        foreach ($arrayUser as $key => $value) {
            $user = new User();
            $user->setEmail($value['email']);
            $user->setPseudo($value['pseudo']);
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    'user'
                )
            );
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new \DateTime());
            $user->setIsActive(true);
            $user->setBio('Utilisateur de CreativeHub');

            $manager->persist($user);

            $this->addReference("user_" . $key, $user);
        }
    }

    public function loadChallenge(ObjectManager $manager)
    {
        $arrayChallenge = [
            // --- Catégorie : Dessin ---
            [
                'title' => 'Portrait en une ligne',
                'description' => 'Dessiner un visage sans jamais lever le crayon de la feuille',
                'category' => 'Dessin',
            ],
            [
                'title' => 'Perspective à un point',
                'description' => 'Dessiner une rue ou un couloir en utilisant un seul point de fuite central',
                'category' => 'Dessin',
            ],
            [
                'title' => 'Monstre mignon',
                'description' => 'Imaginer et dessiner une créature effrayante mais avec des traits adorables',
                'category' => 'Dessin',
            ],

            // --- Catégorie : Photo ---
            [
                'title' => 'Golden Hour',
                'description' => 'Prendre une photo de paysage extérieur durant l\'heure dorée au coucher du soleil',
                'category' => 'Photo',
            ],
            [
                'title' => 'Détail Macro',
                'description' => 'Photographier un objet du quotidien de très près pour en révéler la texture',
                'category' => 'Photo',
            ],
            [
                'title' => 'Symétrie urbaine',
                'description' => 'Trouver et capturer une architecture parfaitement symétrique en ville',
                'category' => 'Photo',
            ],

            // --- Catégorie : Code ---
            [
                'title' => 'Calculatrice JS',
                'description' => 'Coder une interface de calculatrice fonctionnelle avec les opérations de base',
                'category' => 'Code',
            ],
            [
                'title' => 'Switch Dark Mode',
                'description' => 'Créer un bouton qui permet de basculer le thème du site de clair à sombre',
                'category' => 'Code',
            ],
            [
                'title' => 'Clone de Login',
                'description' => 'Reproduire le design de la page de connexion de Netflix ou Facebook en HTML/CSS',
                'category' => 'Code',
            ],

            // --- Catégorie : Projet ---
            [
                'title' => 'Roadmap trimestrielle',
                'description' => 'Définir et planifier 3 objectifs principaux pour les 3 prochains mois',
                'category' => 'Projet',
            ],
            [
                'title' => 'Pitch Deck',
                'description' => 'Créer 5 slides de présentation pour vendre une idée innovante',
                'category' => 'Projet',
            ],
            [
                'title' => 'Moodboard de marque',
                'description' => 'Rassembler une planche d\'images pour définir l\'identité visuelle d\'un produit',
                'category' => 'Projet',
            ],
            [
                'title' => 'Plan de lancement',
                'description' => 'Rédiger une check-list chronologique des étapes clés pour lancer un nouveau produit',
                'category' => 'Projet',
            ],


        ];
        foreach ($arrayChallenge as $value) {
            $challenge = new Challenge();
            $challenge->setTitle($value['title']);
            $challenge->setDescription($value['description']);

            $createdAt = new \DateTime();
            $createdAt->modify('-' . rand(0, 30) . 'days');
            $challenge->setCreatedAt($createdAt);
            $challenge->setUpdatedAt($createdAt);

            if (rand(0, 1)) {
                $deadline = new \DateTime();
                $deadline->modify('+' . rand(7, 60) . 'days');
                $challenge->setDeadline($deadline);
            }
            $challenge->setIsActive(true);
            $challenge->setUser($this->getReference('user_' . rand(0, 4), User::class));
            $challenge->setCategory($this->getReference('category_' . $value['category'], Category::class));

            $challenge->addMedia($this->getReference('media_' . $value['category'], Media::class));

            $manager->persist($challenge);
        }
    }

    public function loadMedia(ObjectManager $manager)
    {
        $arrayMedia = [
            ['ref' => 'Dessin', 'path' => '/images/dessin.png'],
            ['ref' => 'Photo', 'path' => '/images/photo.png'],
            ['ref' => 'Code', 'path' => '/images/code.png'],
            ['ref' => 'Projet', 'path' => '/images/projet.png'],
        ];
        foreach ($arrayMedia as $value) {
            $media = new Media();
            $media->setPath($value['path']);


            $manager->persist($media);

            $this->addReference("media_" . $value['ref'], $media);
        }
    }
}
