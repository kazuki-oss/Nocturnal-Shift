<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Role;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EmployeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('name', TextType::class, [
                'label' => '氏名',
                'required' => true,
                'attr' => ['placeholder' => '山田 太郎']
            ])
            ->add('email', EmailType::class, [
                'label' => 'メールアドレス',
                'required' => true,
                'attr' => ['placeholder' => 'example@example.com']
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'パスワード',
                'mapped' => false,
                'required' => !$isEdit,
                'attr' => ['placeholder' => $isEdit ? '変更する場合のみ入力' : ''],
                'constraints' => $isEdit ? [] : [
                    new Assert\NotBlank(['message' => 'パスワードを入力してください。']),
                    new Assert\Length(['min' => 6, 'minMessage' => 'パスワードは6文字以上で入力してください。'])
                ]
            ])
            ->add('userRoles', EntityType::class, [
                'class' => Role::class,
                'choice_label' => 'name',
                'label' => 'ロール',
                'multiple' => true,
                'expanded' => true,
                'required' => false
            ]);

        // EmployeeProfileフィールド（将来的に追加）
        // 入社日、スキルなど
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false
        ]);
    }
}
