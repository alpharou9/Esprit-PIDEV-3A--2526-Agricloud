<?php

namespace App\Form;

use App\Entity\Farm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;

class FarmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $coordinateConstraints = [];
        if ($options['require_coordinates']) {
            $coordinateConstraints[] = new NotBlank([
                'message' => 'Choose the farm location on the map.',
            ]);
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'Farm Name',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'attr'  => [
                    'class' => 'form-control',
                    'placeholder' => 'City, Region or address',
                    'data-farm-location-input' => 'true',
                ],
            ])
            ->add('farmType', ChoiceType::class, [
                'label'       => 'Farm Type',
                'required'    => false,
                'placeholder' => '-- Select type --',
                'choices'     => [
                    'Crop Farm'      => 'crop',
                    'Livestock Farm' => 'livestock',
                    'Mixed Farm'     => 'mixed',
                    'Orchard'        => 'orchard',
                    'Greenhouse'     => 'greenhouse',
                    'Other'          => 'other',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('area', NumberType::class, [
                'label'    => 'Area (hectares)',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => '0.00'],
            ])
            ->add('latitude', NumberType::class, [
                'label'       => 'Latitude',
                'required'    => $options['require_coordinates'],
                'scale'       => 8,
                'constraints' => $coordinateConstraints,
                'attr'        => [
                    'class' => 'form-control',
                    'placeholder' => '36.81897000',
                    'step' => '0.00000001',
                    'data-farm-latitude-input' => 'true',
                ],
            ])
            ->add('longitude', NumberType::class, [
                'label'       => 'Longitude',
                'required'    => $options['require_coordinates'],
                'scale'       => 8,
                'constraints' => $coordinateConstraints,
                'attr'        => [
                    'class' => 'form-control',
                    'placeholder' => '10.16579000',
                    'step' => '0.00000001',
                    'data-farm-longitude-input' => 'true',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Farm Image',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['class' => 'form-control', 'accept' => 'image/*'],
                'constraints' => [
                    new File([
                        'maxSize'          => '2M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG, WEBP).',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Farm::class,
            'require_coordinates' => false,
        ]);
        $resolver->setAllowedTypes('require_coordinates', 'bool');
    }
}
