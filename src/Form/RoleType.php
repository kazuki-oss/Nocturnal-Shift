<?php

namespace App\Form;

use App\Entity\Role;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class RoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'ロール名',
                'required' => true,
                'attr' => ['placeholder' => '管理者']
            ])
            ->add('identifier', TextType::class, [
                'label' => '識別子',
                'required' => true,
                'attr' => ['placeholder' => 'ROLE_ADMIN'],
                'constraints' => [
                    new Assert\NotBlank(['message' => '識別子を入力してください。']),
                    new Assert\Regex([
                        'pattern' => '/^ROLE_[A-Z_]+$/',
                        'message' => '識別子はROLE_で始まり、大文字とアンダースコアのみ使用できます。'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
        ]);
    }
}
