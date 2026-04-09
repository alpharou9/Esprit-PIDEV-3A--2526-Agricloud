<?php

namespace App\Form;

use App\Entity\Farm;
use App\Entity\Field;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
                'attr' => ['placeholder' => 'e.g. North Wheat Section']
            ])
            ->add('area', NumberType::class, [
                'label' => 'Area (in ha)',
                'attr' => ['placeholder' => 'Enter size in hectares']
            ])
            ->add('soil_type', ChoiceType::class, [
                'label' => 'Soil Type',
                'choices' => [
                    'Sandy soil' => 'Sandy soil',
                    'Clay soil' => 'Clay soil',
                    'Silt soil' => 'Silt soil',
                    'Loamy soil' => 'Loamy soil',
                    'Peaty soil' => 'Peaty soil',
                    'Chalky soil' => 'Chalky soil',
                    'Saline soil' => 'Saline soil',
                ],
            ])
            ->add('crop_type', ChoiceType::class, [
                'label' => 'Crop Type',
                'choices' => [
                    'Cereal crops' => 'Cereal crops',
                    'Pulse crops' => 'Pulse crops',
                    'Oilseed crops' => 'Oilseed crops',
                    'Cash crops' => 'Cash crops',
                    'Horticultural crops' => 'Horticultural crops',
                    'Plantation crops' => 'Plantation crops',
                ],
            ])
            ->add('coordinates', TextType::class, [
                'label' => 'Coordinates (Polygon/Points)',
                'attr' => ['placeholder' => 'e.g. 36.80, 10.18']
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                    'Under Maintenance' => 'Under Maintenance',
                ],
            ]);
            
            // Note: 'farm_id' is excluded here because we will link it 
            // automatically in the Controller based on the URL context.
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Field::class,
        ]);
    }
}