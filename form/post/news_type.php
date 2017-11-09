<?php

namespace form\post;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

use form\input\addon_type;
use form\input\email_addon_type;
use form\input\badge_choice_type;

class news_type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('itemdate', 'datepicker_type')
            ->add('sticky', CheckboxType::class, [
                'required'  => false,
            ])
            ->add('location', addon_type::class, [
                'constraints' => [
                    new Assert\Length(['max' => 128]),
                ],
                'required'  => false,
            ])
            ->add('headline', addon_type::class, [
                'constraints' => [
                    new Assert\Length(['max' => 200]),
                ],
            ])
            ->add('newsitem', TextareaType::class, [
                'constraints' => [
                    new Assert\Length(['max' => 4000]),
                ],
            ])
            ->add('access', badge_choice_type::class,[
                'choices'   => [
                    'interlets'     => 'interlets', 
                    'users'         => 'users', 
                    'admin'         => 'admin',
                ],
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}
