<?php

namespace App\Form;

use App\Entity\ShiftTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShiftTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'テンプレート名',
                'attr' => ['class' => 'form-control', 'placeholder' => '例: 早番（平日）']
            ])
            ->add('startTime', TimeType::class, [
                'label' => '開始時間',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('endTime', TimeType::class, [
                'label' => '終了時間',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control']
            ])
            ->add('applicableDays', ChoiceType::class, [
                'label' => '適用曜日',
                'choices' => [
                    '日' => 0,
                    '月' => 1,
                    '火' => 2,
                    '水' => 3,
                    '木' => 4,
                    '金' => 5,
                    '土' => 6,
                ],
                'multiple' => true,
                'expanded' => true,
                'attr' => ['class' => 'days-checkboxes']
            ])
            ->add('requiredStaffCount', IntegerType::class, [
                'label' => '必要人数',
                'attr' => ['class' => 'form-control', 'min' => 1]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShiftTemplate::class,
        ]);
    }
}
