<?php
namespace App\Form\ColumnSelect;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use form\input\datepicker_type;

class PeriodColumnSelectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('from', 'datepicker_type', [
                'required'  => false,
                'attr'      => [
                    'data-date-default-view-date'   => '-1y',
                    'data-date-end-date'            => '0d',
                ]
            ])
            ->add('to', 'datepicker_type', [
                'required'  => false,
                'attr'      => [
                    'data-date-end-date'    => '0d',                    
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}