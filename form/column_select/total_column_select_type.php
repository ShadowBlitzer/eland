<?php
namespace form\column_select;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class total_column_select_type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('in', CheckboxType::class, [
                'required'  => false,
            ])
            ->add('out', CheckboxType::class, [
                'required'  => false,
            ])
            ->add('total', CheckboxType::class, [
                'required'  => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}