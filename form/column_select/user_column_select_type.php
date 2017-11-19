<?php
namespace form\column_select;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use form\column_select\base_user_column_select_type;
use form\column_select\user_ad_column_select_type;
use form\column_select\user_activity_column_select_type;

class user_column_select_type extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('base', base_user_column_select_type::class)
 //           ->add('contact_detail', user_contact_detail_column_select_type::class)
            ->add('ad', user_ad_column_select_type::class)
            ->add('activity', user_activity_column_select_type::class)
            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        ]);
    }
}