<?php

namespace App\Form;

use App\Entity\Field;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Field Name',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('area', NumberType::class, [
                'label' => 'Area (hectares)',
                'attr'  => ['class' => 'form-control', 'placeholder' => '0.00'],
            ])
            ->add('soilType', ChoiceType::class, [
                'label'       => 'Soil Type',
                'required'    => false,
                'placeholder' => '— Select soil type —',
                'choices'     => [
                    'Clay'   => 'clay',
                    'Sandy'  => 'sandy',
                    'Loamy'  => 'loamy',
                    'Silty'  => 'silty',
                    'Peaty'  => 'peaty',
                    'Chalky' => 'chalky',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('cropType', TextType::class, [
                'label'    => 'Crop Type',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'e.g. Wheat, Tomato…'],
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Status',
                'choices' => [
                    'Active'   => 'active',
                    'Inactive' => 'inactive',
                    'Fallow'   => 'fallow',
                ],
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Field::class]);
    }
}
