<?php
namespace form\column_select;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use form\column_select\period_column_select_type;
use form\column_select\quantity_column_select_type;

class user_activity_column_select_type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('period', period_column_select_type::class)
            ->add('exclude', TextType::class, [
                'required'  => false,
            ])
            ->add('transaction', quantity_column_select_type::class)
            ->add('amount', quantity_column_select_type::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}