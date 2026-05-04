<?php

namespace App\Form;

use App\Entity\Post;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Post title…'],
            ])
            ->add('excerpt', TextareaType::class, [
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Short summary (optional)…'],
            ])
            ->add('content', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 10],
            ])
            ->add('category', ChoiceType::class, [
                'required' => false,
                'placeholder' => '— No category —',
                'choices' => [
                    'News'          => 'News',
                    'Farming Guide' => 'Farming Guide',
                    'Tips & Tricks' => 'Tips & Tricks',
                    'Technology'    => 'Technology',
                    'Market'        => 'Market',
                    'Other'         => 'Other',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('tagsInput', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Tags',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Comma-separated: organic, soil, wheat'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => ['Draft' => 'draft', 'Published' => 'published'],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Cover Image',
                'constraints' => [
                    new File([
                        'maxSize' => '3M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a JPG, PNG or WEBP image.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Post::class]);
    }
}
