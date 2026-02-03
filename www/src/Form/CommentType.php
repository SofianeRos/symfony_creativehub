<?php

namespace app\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        -> add('content',TextareaType::class,[
            'label' => false,
            'attr' => [
                'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2  focus:ring-indigo-500 focus:broder-indigo-500 transition-colors outline-none resize-none',
                'placeholder' => 'Ajouter un commentaire...',
                'rows' => 4
            ],
            'constraints' => [
                new NotBlank(message: 'Le contenu du commentaire ne peut pas être vide.'),
                new Length(
                    min: 5,
                    minMessage: 'Le commentaire doit contenir au moins {{ limit }} caractères.',
                    max: 1000,
                    maxMessage: 'Le commentaire ne peut pas dépasser {{ limit }} caractères.'
                )
            ]
        ])
        ->add('parentComment', HiddenType::class, [
            'mapped' => false,
            'required' => false,
            'attr' => [
                'value' => ''
            ]
        ])



        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'csrf_token_id' => 'submit', // utiliser le token_id configure dans csrf.yaml


        ]);
    }
}