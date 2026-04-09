<?php

namespace App\Form;

use App\Entity\Farm;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['show_owner']) {
            $builder->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $user) => sprintf('%s (%s)', $user->getName(), $user->getEmail()),
                'label' => 'Owner',
                'placeholder' => '- Select an owner -',
                'attr' => ['class' => 'form-select'],
            ]);
        }

        $builder
            ->add('farm', EntityType::class, [
                'class' => Farm::class,
                'choice_label' => fn (Farm $farm) => sprintf('%s (#%d)', $farm->getName(), $farm->getId()),
                'label' => 'Farm',
                'required' => false,
                'placeholder' => '- No farm -',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('name', TextType::class, [
                'label' => 'Product name',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Price',
                'currency' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'Unit',
                'choices' => [
                    'kg' => 'kg',
                    'g' => 'g',
                    'piece' => 'piece',
                    'dozen' => 'dozen',
                    'liter' => 'liter',
                    'ton' => 'ton',
                    'dabouza' => 'dabouza',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'required' => false,
                'choices' => [
                    'Vegetables' => 'Vegetables',
                    'Fruits' => 'Fruits',
                    'Grains' => 'Grains',
                    'Dairy' => 'Dairy',
                    'Livestock' => 'Livestock',
                    'Honey' => 'Honey',
                    'Other' => 'Other',
                ],
                'placeholder' => '- Select a category -',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Product image',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'mimeTypesMessage' => 'Please upload a valid image file (JPG, PNG, WEBP, or GIF).',
                    ]),
                ],
            ]);

        if ($options['show_status']) {
            $builder->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    'Sold out' => 'sold_out',
                ],
                'attr' => ['class' => 'form-select'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'show_owner' => false,
            'show_status' => false,
        ]);
    }
}
