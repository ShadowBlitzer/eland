<?php

namespace form\filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use form\input\addon_type;
use form\typeahead\typeahead_user_type;
use form\input\datepicker_type;

class transaction_filter_type extends AbstractType
{
    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
			->setMethod('GET')
			->add('q', addon_type::class, [
                'required' => false,
            ])
			->add('from_user', 'typeahead_user_type', [
				'required' 		=> false,
				'source_id'		=> 'f_to_user',
			])
			->add('to_user', 'typeahead_user_type', [
				'required' 		=> false,
                'source_route'  => 'user_typeahead',
                'source_params' => [
                    'user_type'     => 'direct',
                ],
			])
			->add('andor', ChoiceType::class, [
				'required' 	=> true,
				'choices'	=> [
					'and'	=> 'and',
					'or'	=> 'or',
					'nor'	=> 'nor',
                ],
			])
			->add('from_date', 'datepicker_type', [
                'required' => false,
                'attr'  => [
                    'data-date-default-view-date'   => '-1y',
                    'data-date-end-date'            => '0d',
                ],
            ])
			->add('to_date', 'datepicker_type', [
                'required' => false,
                'attr'  => [
                    'data-date-end-date'            => '0d',
                ],
            ])
			->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection'   => false,
            'etoken_enabled'    => false,
        ]);
    }
}