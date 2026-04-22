<?php

namespace App\Form;

use App\Entity\Farm;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('category', ChoiceType::class, [
                'label'       => 'Category',
                'required'    => false,
                'placeholder' => '— Select category —',
                'choices'     => [
                    'Vegetables' => 'vegetables',
                    'Fruits'     => 'fruits',
                    'Grains'     => 'grains',
                    'Dairy'      => 'dairy',
                    'Meat'       => 'meat',
                    'Herbs'      => 'herbs',
                    'Other'      => 'other',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Price (TND)',
                'scale' => 2,
                'attr'  => ['class' => 'form-control', 'placeholder' => '0.00'],
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantity',
                'attr'  => ['class' => 'form-control', 'placeholder' => '0'],
            ])
            ->add('unit', ChoiceType::class, [
                'label'   => 'Unit',
                'choices' => [
                    'kg'     => 'kg',
                    'g'      => 'g',
                    'litre'  => 'litre',
                    'piece'  => 'piece',
                    'box'    => 'box',
                    'dozen'  => 'dozen',
                    'bunch'  => 'bunch',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('farm', EntityType::class, [
                'class'        => Farm::class,
                'choice_label' => 'name',
                'label'        => 'From Farm (optional)',
                'required'     => false,
                'placeholder'  => '— Not linked to a farm —',
                'attr'         => ['class' => 'form-select'],
            ])
            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('imageFile', FileType::class, [
                'label'    => 'Product Image',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['class' => 'form-control', 'accept' => 'image/*'],
                'constraints' => [
                    new File([
                        'maxSize'          => '2M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Product::class]);
    }
}
