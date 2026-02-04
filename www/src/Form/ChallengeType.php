<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Challenge;
use App\Entity\Media;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

class ChallengeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du defi',
                'attr' => [
                    'class' => 'form-input',
                    'placeholder' => 'Ex: Créer un logo moderne'
                ],
                'constraints' => [
                    new NotBlank(message: 'Le titre ne peut pas être vide'),
                    new Length(
                        min: 3,
                        max: 255,
                        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères',
                        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères'
                    ),
                ]

            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du défi (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-textarea',
                    'rows' => 6,
                    'placeholder' => 'Décrivez le défi en détails',
                ],
                    'constraints' => [

                        new Length(
                            max: 5000,
                            maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères'
                        ),
                    ]

                
            ])
            ->add('deadline', DateTimeType::class, [
                'label' => 'Date limite (optionnel)',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-input',
                ],
            ])
            ->add('category', EntityType::class, [
                'label' => 'Catégorie',
                'class' => Category::class,
                'choice_label' => 'label',
                'placeholder' => 'Choisissez une catégorie',
                'attr' => [
                    'class' => 'form-select',
                ],
                'constraints' => [
                    new NotNull(message: 'La catégorie est obligatoire'),
                ]
            ])
            ->add('files', FileType::class, [
                'label' => 'Fichiers (optionnel)',
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'form-input',
                    'accept' => 'image/*',
                ],
                'constraints' => [
                    new All(
                        constraints:[
                            new File(
                                maxSize: '5M',
                                mimeTypes: [
                                    'image/jpeg',
                                    'image/png',
                                    'image/gif',
                                    'image/webp',
                                    'image/svg+xml',
                                    'image/avif',
                                ],
                                mimeTypesMessage: 'Veuillez télécharger une image valide (JPEG, PNG, GIF, WEBP, SVG, AVIF).',
                            )
                        ]
                    )
                    
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Challenge::class,
        ]);
    }
}
