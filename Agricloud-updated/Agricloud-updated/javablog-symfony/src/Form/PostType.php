<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Post Title',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a title']),
                    new Length(['min' => 3, 'max' => 255]),
                ],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter post title...'],
            ])
            ->add('author', TextType::class, [
                'label' => 'Author',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Your name...'],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'constraints' => [
                    new NotBlank(['message' => 'Content cannot be empty']),
                ],
                'attr' => ['class' => 'form-control', 'rows' => 8, 'placeholder' => 'Write your post content...'],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Save Post',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Post::class]);
    }
}
