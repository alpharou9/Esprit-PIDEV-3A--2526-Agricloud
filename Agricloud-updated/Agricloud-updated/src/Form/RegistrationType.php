<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'John Doe'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'you@example.com'],
            ])
            ->add('phone', TextType::class, [
                'label'    => 'Phone',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => '+216 XX XXX XXX'],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label'    => 'Password',
                'mapped'   => false,
                'required' => true,
                'attr'     => ['class' => 'form-control', 'autocomplete' => 'new-password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
