<?php

namespace App\Form;

use App\Entity\Attendance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AttendanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clockInAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => '出勤時刻',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('clockOutAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => '退勤時刻',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('breakMinutes', IntegerType::class, [
                'label' => '休憩時間（分）',
                'required' => false,
                'attr' => ['class' => 'form-control', 'min' => 0]
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'メモ',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3]
            ])
            ->add('modificationReason', TextareaType::class, [
                'label' => '修正理由',
                'required' => true,
                'attr' => ['class' => 'form-control', 'rows' => 2, 'placeholder' => '修正理由を入力してください']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Attendance::class,
        ]);
    }
}
