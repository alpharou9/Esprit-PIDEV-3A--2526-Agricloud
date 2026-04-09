<?php

namespace App\Form;

use App\Entity\Farm;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FarmType extends AbstractType
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
            ->add('name', TextType::class, [
                'label' => 'Farm name',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('area', TextType::class, [
                'label' => 'Area',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '5000.00'],
            ])
            ->add('farmType', ChoiceType::class, [
                'label' => 'Farm type',
                'required' => false,
                'choices' => [
                    'Sa9weya' => 'sa9weya',
                    'Livestock' => 'livestock',
                    'Greenhouse' => 'greenhouse',
                    'Orchard' => 'orchard',
                    'Mixed' => 'mixed',
                ],
                'placeholder' => '- Select a farm type -',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'required' => false,
                'choices' => [
                    'Pending' => 'pending',
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                ],
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Farm::class,
            'show_owner' => false,
        ]);
    }
}
