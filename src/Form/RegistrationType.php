<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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
                'label'       => 'Password',
                'mapped'      => false,
                'required'    => true,
                'attr'        => ['class' => 'form-control', 'autocomplete' => 'new-password'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Password is required.'),
                    new Assert\Length(min: 6, max: 100, minMessage: 'Password must be at least 6 characters.'),
                    new Assert\Regex(pattern: '/[A-Z]/', message: 'Password must contain at least one uppercase letter.'),
                    new Assert\Regex(pattern: '/[0-9]/', message: 'Password must contain at least one number.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => User::class]);
    }
}
