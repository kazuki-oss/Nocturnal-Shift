<?php

namespace App\Form;

use App\Entity\Shift;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShiftType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'name',
                'label' => '従業員',
                'attr' => ['class' => 'form-control']
            ])
            ->add('startTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => '開始時刻',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endTime', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => '終了時刻',
                'attr' => ['class' => 'form-control']
            ])
            ->add('status', ChoiceType::class, [
                'choices' => [
                    '予定' => 'scheduled',
                    '完了' => 'completed',
                    'キャンセル' => 'cancelled',
                ],
                'label' => 'ステータス',
                'attr' => ['class' => 'form-control']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Shift::class,
        ]);
    }
}
