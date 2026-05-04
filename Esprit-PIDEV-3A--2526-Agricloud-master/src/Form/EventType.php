<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Event title…'],
            ])
            ->add('category', ChoiceType::class, [
                'required' => false,
                'placeholder' => '— Select category —',
                'choices' => [
                    'Workshop'   => 'Workshop',
                    'Conference' => 'Conference',
                    'Fair'       => 'Fair',
                    'Training'   => 'Training',
                    'Webinar'    => 'Webinar',
                    'Other'      => 'Other',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Upcoming'  => 'upcoming',
                    'Ongoing'   => 'ongoing',
                    'Completed' => 'completed',
                    'Cancelled' => 'cancelled',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('eventDate', DateTimeType::class, [
                'widget' => 'single_text',
                'attr'   => ['class' => 'form-control'],
                'label'  => 'Event Date & Time',
            ])
            ->add('endDate', DateTimeType::class, [
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
                'label'    => 'End Date & Time (optional)',
            ])
            ->add('registrationDeadline', DateTimeType::class, [
                'widget'   => 'single_text',
                'required' => false,
                'attr'     => ['class' => 'form-control'],
                'label'    => 'Registration Deadline (optional)',
            ])
            ->add('location', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'City, venue or address…'],
            ])
            ->add('capacity', IntegerType::class, [
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'Leave empty for unlimited'],
                'label'    => 'Capacity (optional)',
            ])
            ->add('description', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'rows' => 5],
            ])
            ->add('imageFile', FileType::class, [
                'mapped'   => false,
                'required' => false,
                'label'    => 'Cover Image',
                'constraints' => [
                    new File([
                        'maxSize'         => '3M',
                        'mimeTypes'       => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a JPG, PNG or WEBP image.',
                    ]),
                ],
                'attr' => ['class' => 'form-control'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Event::class]);
    }
}
