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
                'label' => 'Title',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Post title…',
                    'autofocus'   => true,
                ],
            ])
            ->add('excerpt', TextareaType::class, [
                'label'    => 'Excerpt',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'rows'        => 2,
                    'placeholder' => 'Short summary shown in listings (optional)…',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'attr'  => [
                    'class' => 'form-control',
                    'rows'  => 12,
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label'       => 'Category',
                'required'    => false,
                'placeholder' => '— No category —',
                'choices'     => [
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
                'mapped'   => false,
                'label'    => 'Tags',
                'required' => false,
                'attr'     => [
                    'class'       => 'form-control',
                    'placeholder' => 'Comma-separated: organic, soil, wheat',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Status',
                'choices' => [
                    'Draft'       => 'draft',
                    'Published'   => 'published',
                    'Unpublished' => 'unpublished',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('imageFile', FileType::class, [
                'mapped'      => false,
                'label'       => 'Cover Image',
                'required'    => false,
                'constraints' => [
                    new File([
                        'maxSize'            => '3M',
                        'mimeTypes'          => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage'   => 'Please upload a JPG, PNG, or WEBP image.',
                        'maxSizeMessage'     => 'The file may not be larger than 3 MB.',
                    ]),
                ],
                'attr' => ['class' => 'form-control', 'accept' => 'image/jpeg,image/png,image/webp'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Post::class]);
    }
}
