<?php

namespace App\Form;

use App\Entity\Farm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FarmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Farm Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Enter farm name']
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'Location (Governorate)',
                'choices' => [
                    'Ariana' => 'Ariana', 'Beja' => 'Beja', 'Ben Arous' => 'Ben Arous', 'Bizerte' => 'Bizerte',
                    'Gabes' => 'Gabes', 'Gafsa' => 'Gafsa', 'Jendouba' => 'Jendouba', 'Kairouan' => 'Kairouan',
                    'Kasserine' => 'Kasserine', 'Kebili' => 'Kebili', 'Kef' => 'Kef', 'Mahdia' => 'Mahdia',
                    'Manouba' => 'Manouba', 'Medenine' => 'Medenine', 'Monastir' => 'Monastir', 'Nabeul' => 'Nabeul',
                    'Sfax' => 'Sfax', 'Sidi Bouzid' => 'Sidi Bouzid', 'Siliana' => 'Siliana', 'Sousse' => 'Sousse',
                    'Tataouine' => 'Tataouine', 'Tozeur' => 'Tozeur', 'Tunis' => 'Tunis', 'Zaghouan' => 'Zaghouan',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('area', NumberType::class, [
                'label' => 'Area (ha)',
                'attr' => ['class' => 'form-control', 'step' => '0.1']
            ])
            ->add('farmType', ChoiceType::class, [
                'label' => 'Type of Farm',
                'choices' => [
                    'Arable' => 'Arable',
                    'Vegetable' => 'Vegetable',
                    'Orchard' => 'Orchard',
                    'Herb' => 'Herb',
                    'Flower' => 'Flower',
                    'Organic' => 'Organic',
                    'Hydroponic' => 'Hydroponic',
                    'Vertical' => 'Vertical',
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['class' => 'form-control', 'rows' => 4]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Farm::class,
        ]);
    }
}