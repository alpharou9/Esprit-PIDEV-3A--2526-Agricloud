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
                'attr' => ['placeholder' => 'Enter field name']
            ])
            ->add('area', NumberType::class, [
                'label' => 'Area (ha)',
                'attr' => ['placeholder' => 'e.g. 1.5']
            ])
            ->add('soilType', ChoiceType::class, [
                'label' => 'Soil Type',
                'choices'  => [
                    'Sandy soil' => 'Sandy soil',
                    'Clay soil' => 'Clay soil',
                    'Silt soil' => 'Silt soil',
                    'Loamy soil' => 'Loamy soil',
                    'Peaty soil' => 'Peaty soil',
                    'Chalky soil' => 'Chalky soil',
                    'Saline soil' => 'Saline soil',
                ],
                'placeholder' => 'Select soil type',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Active' => 'active',
                    'Not Active' => 'not active',
                ],
            ])
            // We REMOVE 'Farmid' from here because the Controller 
            // handles it automatically via the URL parameter.
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Field::class,
        ]);
    }
}