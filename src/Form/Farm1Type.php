<?php

namespace App\Form;

use App\Entity\Farm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Farm1Type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('location')
            ->add('area')
            ->add('farmType')
            ->add('description')
            ->add('status')
            ->add('nbfields')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Farm::class,
        ]);
    }
}
