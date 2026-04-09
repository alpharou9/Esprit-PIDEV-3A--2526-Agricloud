<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $tomorrow = (new \DateTimeImmutable('tomorrow'))->format('Y-m-d');

        $builder
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Shipping address',
                'constraints' => [
                    new NotBlank(['message' => 'Shipping address is required.']),
                    new Length(['min' => 5, 'minMessage' => 'Shipping address must be at least {{ limit }} characters.']),
                ],
                'attr' => [
                    'rows' => 3,
                ],
            ])
            ->add('shippingCity', TextType::class, [
                'label' => 'City',
                'required' => false,
            ])
            ->add('shippingPostal', TextType::class, [
                'label' => 'Postal code',
                'constraints' => [
                    new NotBlank(['message' => 'Postal code is required.']),
                    new Regex([
                        'pattern' => '/^\d{4}$/',
                        'message' => 'Postal code must contain exactly 4 digits.',
                    ]),
                ],
                'attr' => [
                    'inputmode' => 'numeric',
                    'maxlength' => 4,
                    'minlength' => 4,
                    'pattern' => '\d{4}',
                    'placeholder' => '1000',
                    'oninput' => "this.value=this.value.replace(/\\D/g,'').slice(0,4)",
                ],
            ])
            ->add('shippingEmail', EmailType::class, [
                'label' => 'Contact email',
                'constraints' => [
                    new NotBlank(['message' => 'Contact email is required.']),
                    new Email(['message' => 'Enter a valid shipping email address.']),
                ],
            ])
            ->add('shippingPhone', TextType::class, [
                'label' => 'Phone number',
                'constraints' => [
                    new NotBlank(['message' => 'Phone number is required.']),
                    new Regex([
                        'pattern' => '/^\d{8}$/',
                        'message' => 'Phone number must contain exactly 8 digits.',
                    ]),
                ],
                'attr' => [
                    'inputmode' => 'numeric',
                    'maxlength' => 8,
                    'minlength' => 8,
                    'pattern' => '\d{8}',
                    'placeholder' => '54022877',
                    'oninput' => "this.value=this.value.replace(/\\D/g,'').slice(0,8)",
                ],
            ])
            ->add('deliveryDate', DateType::class, [
                'label' => 'Delivery date',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => [
                    'min' => $tomorrow,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Delivery date is required.']),
                    new GreaterThan([
                        'value' => 'today',
                        'message' => 'Delivery date must be in the future.',
                    ]),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Order notes',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Delivery instructions, preferred contact time, etc.',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
