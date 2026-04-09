<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarketFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', SearchType::class, [
                'required' => false,
                'label' => 'Search',
                'attr' => [
                    'placeholder' => 'Name, category, farmer',
                ],
            ])
            ->add('category', ChoiceType::class, [
                'required' => false,
                'label' => 'Category',
                'placeholder' => 'All categories',
                'choices' => $options['category_choices'],
            ])
            ->add('sort', ChoiceType::class, [
                'required' => false,
                'label' => 'Sort by',
                'choices' => $options['sort_choices'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
            'category_choices' => [],
            'sort_choices' => [],
        ]);

        $resolver->setAllowedTypes('category_choices', 'array');
        $resolver->setAllowedTypes('sort_choices', 'array');
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
