<?php

namespace App\Form;

use App\Entity\Permission;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PermissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => '権限名',
                'required' => true,
                'attr' => ['placeholder' => 'シフト管理']
            ])
            ->add('identifier', TextType::class, [
                'label' => '識別子',
                'required' => true,
                'attr' => ['placeholder' => 'shift.manage'],
                'constraints' => [
                    new Assert\NotBlank(['message' => '識別子を入力してください。']),
                    new Assert\Regex([
                        'pattern' => '/^[a-z_\.]+$/',
                        'message' => '識別子は小文字、アンダースコア、ドットのみ使用できます。'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Permission::class,
        ]);
    }
}
