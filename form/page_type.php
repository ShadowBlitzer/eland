<?php

namespace form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class page_type extends AbstractType
{
    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
			->add('menu', addon_type::class, [
            ])
			->add('slug', addon_type::class, [
			])
			->add('access', ChoiceType::class, [
                'choices'   => [
                    'label.admin'               => 'admin',
                    'label.user'                => 'user',
                    'label.interlets_admin'     => 'interlets',
                    'label.interlets_user'      => 'interlets_user',
                    'label.public'              => 'public',
                ], 
			])
			->add('content', TextareaType::class, [
            ])
			->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([

        ]);
    }
}