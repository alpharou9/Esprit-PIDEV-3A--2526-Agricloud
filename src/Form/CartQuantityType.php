<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class CartQuantityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxQuantity = max(1, (int) $options['max_quantity']);
        $currentQuantity = max(1, (int) $options['current_quantity']);

        $builder->add('quantity', IntegerType::class, [
            'label' => false,
            'data' => min($currentQuantity, $maxQuantity),
            'attr' => [
                'min' => 1,
                'max' => $maxQuantity,
            ],
            'constraints' => [
                new Range(min: 1, max: $maxQuantity),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'max_quantity' => 1,
            'current_quantity' => 1,
        ]);

        $resolver->setAllowedTypes('max_quantity', 'int');
        $resolver->setAllowedTypes('current_quantity', 'int');
    }
}
