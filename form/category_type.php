<?php

namespace form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Connection as db;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use exception\missing_options_exception;
use exception\required_argument_exception;

class category_type extends AbstractType
{	
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['db'] === null)
        {
            throw new missing_options_exception(
                sprintf('The option "db" is mandatory 
                for constraint %s', __CLASS__));
        }

        if (!$options['db'] instanceof db)
        {
            throw new invalid_argument_exception(
                sprintf('The option "db" must be an instance of 
                Doctrine\DBAL\Connection for constraint %s', __CLASS__));
        }

        if ($options['translator'] === null)
        {
            throw new missing_options_exception(
                sprintf('The option "translator" is mandatory 
                for constraint %s', __CLASS__));
        }

        if (!$options['translator'] instanceof TranslatorInterface)
        {
            throw new invalid_argument_exception(
                sprintf('The option "translator" must be a valid 
                string for constraint %s', __CLASS__));
        }

        if ($options['schema'] === null)
        {
            throw new missing_options_exception(
                sprintf('The option "schema" is mandatory 
                for constraint %s', __CLASS__));
        }

        if (!is_string($options['schema']) || $options['schema'] === '')
        {
            throw new invalid_argument_exception(
                sprintf('The option "schema" must be a valid 
                string for constraint %s', __CLASS__));
        }

        if ($options['root_selectable'])
        {
            $main_cat_string = '-- ';
            $main_cat_string .= $options['translator']->trans('category.main_category');
            $main_cat_string .= ' --';

            $parent_categories = [
                $main_cat_string  => 0,
            ];
        }
        else
        {
            $parent_categories = [];
        }

        if ($options['sub_selectable'])
        {
            $rs = $options['db']->prepare('select id, name 
                from ' . $options['schema'] . '.categories 
                where leafnote = 0 
                order by name asc');

            $rs->execute();

            while ($row = $rs->fetch())
            {
                $parent_categories[$row['name']] = $row['id'];
            }
        }

        $builder->add('name', TextType::class, [			
            'constraints' 	=> [
                new Assert\NotBlank(),
                new Assert\Length(['max' => 40, 'min' => 1]),
            ],
                'attr'	=> [
                    'maxlength'	=> 40,
                ],
            ])

            ->add('id_parent', ChoiceType::class, [
                'choices'  					=> $parent_categories,
                'choice_translation_domain' => false,
            ])

            ->add('submit', SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'db'                => null,
            'schema'            => null,
            'translator'        => null,
            'root_selectable'   => true,
            'sub_selectable'    => true,
        ]);
    }
}