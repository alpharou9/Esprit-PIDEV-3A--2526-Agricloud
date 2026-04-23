<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', TextType::class, [
                'label' => 'Your Name',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Anonymous'],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Comment',
                'constraints' => [new NotBlank(['message' => 'Comment cannot be empty'])],
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Write your comment...'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Post Comment',
                'attr' => ['class' => 'btn btn-success mt-2'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Comment::class]);
    }
}
