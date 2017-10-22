<?php

namespace form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use form\addon_type;
use form\email_addon_type;

class register_type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('first_name', addon_type::class)
            ->add('last_name', addon_type::class)
            ->add('email', email_addon_type::class, [
                'constraints' => new Assert\Email(),
            ])
            ->add('postcode', addon_type::class)
            ->add('mobile', addon_type::class, [
                'required'	=> false,
            ])
            ->add('telephone', addon_type::class, [
                'required'	=> false,
            ])
            ->add('accept', CheckboxType::class, [
                'constraints' => new Assert\IsTrue(),
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}