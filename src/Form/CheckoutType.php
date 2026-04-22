<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('paymentMethod', ChoiceType::class, [
                'label' => 'Payment Method',
                'choices' => [
                    'Cash on delivery' => 'cash',
                    'Pay with Stripe' => 'stripe',
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => 'cash',
                'constraints' => [
                    new Assert\NotBlank(message: 'Please choose a payment method.'),
                    new Assert\Choice(choices: ['cash', 'stripe'], message: 'Choose a valid payment method.'),
                ],
            ])
            ->add('shippingName', TextType::class, [
                'label' => 'Full Name',
                'required' => false,
                'attr' => ['class' => 'form-control', 'autocomplete' => 'name'],
            ])
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Street Address',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2, 'autocomplete' => 'street-address'],
            ])
            ->add('shippingCity', TextType::class, [
                'label' => 'City',
                'required' => false,
                'attr' => ['class' => 'form-control', 'autocomplete' => 'address-level2'],
            ])
            ->add('shippingPostal', TextType::class, [
                'label' => 'Postal Code',
                'required' => false,
                'attr' => ['class' => 'form-control', 'autocomplete' => 'postal-code'],
            ])
            ->add('shippingEmail', EmailType::class, [
                'label' => 'Contact Email',
                'required' => false,
                'attr' => ['class' => 'form-control', 'autocomplete' => 'email'],
            ])
            ->add('shippingPhone', TextType::class, [
                'label' => 'Phone',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '+216 XX XXX XXX', 'autocomplete' => 'tel'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Delivery Notes (optional)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Delivery instructions...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
