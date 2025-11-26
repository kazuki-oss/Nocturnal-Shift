<?php

namespace App\Form;

use App\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'イベント名',
                'attr' => ['class' => 'form-control']
            ])
            ->add('eventType', ChoiceType::class, [
                'label' => 'イベントタイプ',
                'choices' => [
                    '単発' => 'single',
                    '毎週' => 'weekly',
                    '毎月' => 'monthly',
                ],
                'attr' => ['class' => 'form-control', 'onchange' => 'toggleRecurrenceFields(this.value)']
            ])
            ->add('isAllDay', CheckboxType::class, [
                'label' => '終日イベント',
                'required' => false,
                'attr' => ['class' => 'form-check-input', 'onchange' => 'toggleTimeFields(this.checked)']
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
            // 単発用日付
            ->add('singleDate', DateType::class, [
                'label' => '開始日（単発）',
                'widget' => 'single_text',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('endDate', DateType::class, [
                'label' => '終了日（複数日の場合）',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            // 繰り返し設定
            ->add('dayOfWeek', ChoiceType::class, [
                'label' => '曜日（毎週）',
                'required' => false,
                'choices' => [
                    '日曜日' => 0,
                    '月曜日' => 1,
                    '火曜日' => 2,
                    '水曜日' => 3,
                    '木曜日' => 4,
                    '金曜日' => 5,
                    '土曜日' => 6,
                ],
                'attr' => ['class' => 'form-control']
            ])
            ->add('dayOfMonth', ChoiceType::class, [
                'label' => '日付（毎月）',
                'required' => false,
                'choices' => array_combine(range(1, 31), range(1, 31)),
                'attr' => ['class' => 'form-control']
            ])
            ->add('recurrenceEndDate', DateType::class, [
                'label' => '繰り返し終了日',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('color', ColorType::class, [
                'label' => '表示色',
                'attr' => ['class' => 'form-control form-control-color']
            ])
            ->add('description', TextareaType::class, [
                'label' => '詳細',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
