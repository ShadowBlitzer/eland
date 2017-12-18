<?php
namespace App\Form\ColumnSelect;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use App\Form\ColumnSelect\BaseUserColumnSelectType;
use App\Form\ColumnSelect\UserBalanceOnDateColumnSelectType;
use App\Form\ColumnSelect\UserParsedAddressColumnSelectType;
use App\Form\ColumnSelect\UserAdColumnSelectType;
use App\Form\ColumnSelect\UserActivityColumnSelectType;

class UserColumnSelectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('base', BaseUserColumnSelectType::class)
            ->add('balance_on_date', UserBalanceOnDateColumnSelectType::class)
            ->add('contact_detail', 'user_contact_detail_column_select_type')
            ->add('parsed_address', UserParsedAddressColumnSelectType::class)
            ->add('ad', UserAdColumnSelectType::class)
            ->add('activity', UserActivityColumnSelectType::class)
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'etoken_enabled'    => false,
        ]);
    }
}