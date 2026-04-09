<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $user) => sprintf('%s (%s)', $user->getName(), $user->getEmail()),
                'label' => 'Owner',
                'placeholder' => '- Select an owner -',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('farmId', IntegerType::class, [
                'label' => 'Farm ID',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Optional farm reference'],
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
            ->add('category', TextType::class, [
                'label' => 'Category',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Honey, Fruits, Vegetables...'],
            ])
            ->add('image', TextType::class, [
                'label' => 'Image path',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'uploads/products/example.jpg'],
            ])
            ->add('status', ChoiceType::class, [
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
