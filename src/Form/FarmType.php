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

class FarmType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Farm Name',
                'attr' => ['placeholder' => 'Enter farm name']
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'Location (Governorate)',
                'choices'  => [
                    'Ariana' => 'Ariana',
                    'Beja' => 'Beja',
                    'Ben Arous' => 'Ben Arous',
                    'Bizerte' => 'Bizerte',
                    'Gabes' => 'Gabes',
                    'Gafsa' => 'Gafsa',
                    'Jendouba' => 'Jendouba',
                    'Kairouan' => 'Kairouan',
                    'Kasserine' => 'Kasserine',
                    'Kebili' => 'Kebili',
                    'Kef' => 'Kef',
                    'Mahdia' => 'Mahdia',
                    'Manouba' => 'Manouba',
                    'Medenine' => 'Medenine',
                    'Monastir' => 'Monastir',
                    'Nabeul' => 'Nabeul',
                    'Sfax' => 'Sfax',
                    'Sidi Bouzid' => 'Sidi Bouzid',
                    'Siliana' => 'Siliana',
                    'Sousse' => 'Sousse',
                    'Tataouine' => 'Tataouine',
                    'Tozeur' => 'Tozeur',
                    'Tunis' => 'Tunis',
                    'Zaghouan' => 'Zaghouan',
                ],
            ])
            ->add('latitude', NumberType::class, [
                'scale' => 8,
                'attr' => ['placeholder' => 'e.g. 36.8065']
            ])
            ->add('longitude', NumberType::class, [
                'scale' => 8,
                'attr' => ['placeholder' => 'e.g. 10.1815']
            ])
            ->add('area', NumberType::class, [
                'label' => 'Area (in ha)',
                'attr' => ['placeholder' => 'Enter area in hectares']
            ])
            ->add('farm_type', ChoiceType::class, [
                'label' => 'Type of the Farm',
                'choices'  => [
                    'Arable' => 'Arable',
                    'Pastoral' => 'Pastoral',
                    'Mixed' => 'Mixed',
                    'Orchard' => 'Orchard',
                    'Vegetable' => 'Vegetable',
                    'Vineyard' => 'Vineyard',
                ],
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 5]
            ])
            ->add('image', FileType::class, [
                'label' => 'Farm Photo (Upload from PC)',
                // This field is not mapped to any Entity property directly 
                // because we handle the file upload manually in the controller
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG or WEBP)',
                    ])
                ],
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