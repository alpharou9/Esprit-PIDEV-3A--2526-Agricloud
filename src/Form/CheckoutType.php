<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
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
            ->add('shippingAddress', TextareaType::class, [
                'label'       => 'Street Address',
                'attr'        => ['class' => 'form-control', 'rows' => 2],
                'constraints' => [
                    new Assert\NotBlank(message: 'Shipping address is required.'),
                    new Assert\Length(min: 5, max: 500, minMessage: 'Address must be at least 5 characters.', maxMessage: 'Address cannot exceed 500 characters.'),
                ],
            ])
            ->add('shippingCity', TextType::class, [
                'label'       => 'City',
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'City is required.'),
                    new Assert\Length(max: 100, maxMessage: 'City cannot exceed 100 characters.'),
                ],
            ])
            ->add('shippingPostal', TextType::class, [
                'label'       => 'Postal Code',
                'required'    => false,
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\When(
                        expression: 'value !== null and value !== ""',
                        constraints: [
                            new Assert\Regex(pattern: '/^\d{4,10}$/', message: 'Postal code must contain only digits (4–10 numbers).'),
                        ]
                    ),
                ],
            ])
            ->add('shippingEmail', EmailType::class, [
                'label'       => 'Contact Email',
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Email is required.'),
                    new Assert\Email(message: 'Enter a valid email address.'),
                    new Assert\Length(max: 150, maxMessage: 'Email cannot exceed 150 characters.'),
                ],
            ])
            ->add('shippingPhone', TextType::class, [
                'label'       => 'Phone',
                'required'    => false,
                'attr'        => ['class' => 'form-control', 'placeholder' => '+216 XX XXX XXX'],
                'constraints' => [
                    new Assert\When(
                        expression: 'value !== null and value !== ""',
                        constraints: [
                            new Assert\Regex(pattern: '/^\+?[0-9\s\-]{8,20}$/', message: 'Phone must be 8–20 digits (optionally starting with +).'),
                        ]
                    ),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label'       => 'Notes (optional)',
                'required'    => false,
                'attr'        => ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Delivery instructions…'],
                'constraints' => [
                    new Assert\Length(max: 1000, maxMessage: 'Notes cannot exceed 1000 characters.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
