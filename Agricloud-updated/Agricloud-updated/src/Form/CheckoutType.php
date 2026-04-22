<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('shippingAddress', TextareaType::class, [
                'label' => 'Street Address',
                'attr'  => ['class' => 'form-control', 'rows' => 2],
            ])
            ->add('shippingCity', TextType::class, [
                'label' => 'City',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('shippingPostal', TextType::class, [
                'label'    => 'Postal Code',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
            ])
            ->add('shippingEmail', EmailType::class, [
                'label' => 'Contact Email',
                'attr'  => ['class' => 'form-control'],
            ])
            ->add('shippingPhone', TextType::class, [
                'label'    => 'Phone',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => '+216 XX XXX XXX'],
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes (optional)',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'rows' => 2, 'placeholder' => 'Delivery instructions…'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
