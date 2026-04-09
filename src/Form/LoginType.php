<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('_username', EmailType::class, [
                'label' => 'Email',
                'mapped' => false,
                'data' => $options['last_username'],
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'email',
                    'autofocus' => true,
                ],
            ])
            ->add('_password', PasswordType::class, [
                'label' => 'Password',
                'mapped' => false,
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'current-password',
                ],
            ])
            ->add('_csrf_token', HiddenType::class, [
                'mapped' => false,
                'data' => $options['csrf_token'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'last_username' => '',
            'csrf_token' => '',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
