<?php

namespace App\Form;

use App\Entity\Farm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
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
                'attr' => ['placeholder' => 'Enter farm name']
            ])
            ->add('location', ChoiceType::class, [
                'label' => 'Location (Governorate)',
                'choices' => [
                    'Ariana' => 'ariana',
                    'Beja' => 'beja',
                    'Ben Arous' => 'ben_arous',
                    'Bizerte' => 'bizerte',
                    'Gabes' => 'gabes',
                    'Gafsa' => 'gafsa',
                    'Jendouba' => 'jendouba',
                    'Kairouan' => 'kairouan',
                    'Kasserine' => 'kasserine',
                    'Kebili' => 'kebili',
                    'Kef' => 'kef',
                    'Mahdia' => 'mahdia',
                    'Manouba' => 'manouba',
                    'Medenine' => 'medenine',
                    'Monastir' => 'monastir',
                    'Nabeul' => 'nabeul',
                    'Sfax' => 'sfax',
                    'Sidi Bouzid' => 'sidi_bouzid',
                    'Siliana' => 'siliana',
                    'Sousse' => 'sousse',
                    'Tataouine' => 'tataouine',
                    'Tozeur' => 'tozeur',
                    'Tunis' => 'tunis',
                    'Zaghouan' => 'zaghouan',
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
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['rows' => 5]
            ])
            // Using TextType for image path for now; change to FileType if uploading actual files
            ->add('image', TextType::class, [
                'required' => false,
                'label' => 'Image URL/Path'
            ])
            ->add('status', HiddenType::class, [
                'data' => 'pending',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Farm::class,
        ]);
    }
}