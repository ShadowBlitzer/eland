<?php

namespace form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;

class type_contact_type extends AbstractType
{	

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, [			
            'constraints' 	=> [
                new Assert\Length(['max' => 40, 'min' => 1]),
            ],
                'attr'	=> [
                    'maxlength'	=> 40,
                ],
            ])

            ->add('abbrev', TextType::class, [			
            'constraints' 	=> [
                new Assert\Length(['max' => 40, 'min' => 1]),
            ],
                'attr'	=> [
                    'maxlength'	=> 40,
                ],
            ])    

            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'blocked_ary' => [],
        ]);
    }
}