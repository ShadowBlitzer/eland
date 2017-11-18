<?php
namespace form\column_select;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use form\column_select\total_column_select_type;
use form\input\datepicker_type;

class transaction_column_select_type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('since_date', datepicker_type::class, [
                'required'  => false,
            ])
            ->add('exclude_codes', CheckboxType::class, [

            ])
            ->add('total', total_column_select_type::class, [
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}