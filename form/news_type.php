<?php

namespace form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use form\addon_type;
use form\email_addon_type;
use form\inline_choice_type;

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
            ->add('access', inline_choice_type::class,[
                'choices'   => ['1', '2', '3'],
            ])
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}
