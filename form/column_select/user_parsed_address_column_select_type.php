<?php

namespace form\column_select;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use form\input\addon_type;

class user_parsed_address_column_select_type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('enable', CheckboxType::class, [
                'required'  => false,
            ])
            ->add('char', addon_type::class, [
                'required'  => false,
                'constraints' 	=> [
                    new Assert\Length(['max' => 1]),
                ],
                'attr'  => [
                    'maxlength' => 1,
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}
