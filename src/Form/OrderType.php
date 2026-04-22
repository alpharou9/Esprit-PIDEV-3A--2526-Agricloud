<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('customer', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $user) => sprintf('%s (%s)', $user->getName(), $user->getEmail()),
                'label' => 'Customer',
                'placeholder' => '- Select a customer -',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('seller', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $user) => sprintf('%s (%s)', $user->getName(), $user->getEmail()),
                'label' => 'Seller',
                'placeholder' => '- Select a seller -',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => fn (Product $product) => sprintf('%s - %s', $product->getName(), $product->getUnit()),
                'label' => 'Product',
                'placeholder' => '- Select a product -',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('unitPrice', MoneyType::class, [
                'label' => 'Unit price',
                'currency' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Confirmed' => 'confirmed',
                    'Processing' => 'processing',
                    'Shipped' => 'shipped',
                    'Delivered' => 'delivered',
                    'Cancelled' => 'cancelled',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Shipping address',
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('shippingCity', TextType::class, [
                'label' => 'Shipping city',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('shippingPostal', TextType::class, [
                'label' => 'Postal code',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('shippingEmail', EmailType::class, [
                'label' => 'Shipping email',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('shippingPhone', TextType::class, [
                'label' => 'Shipping phone',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('deliveryDate', DateType::class, [
                'label' => 'Delivery date',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('cancelledReason', TextareaType::class, [
                'label' => 'Cancelled reason',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
