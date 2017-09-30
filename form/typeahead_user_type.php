<?php

namespace form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use form\addon_type;
use form\typeahead_type;
use transformer\typeahead_user_transformer;
use exception\conflicting_options_exception;

class typeahead_user_type extends AbstractType
{
    private $transformer;
    
    public function __construct(typeahead_user_transformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this->transformer);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }    

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'source_id'         => null,
            'data_path'         => null,
            'process'           => null,
        ]);
    }

    public function getParent()
    {
        return 'typeahead_type';
    }

    public function getBlockPrefix()
    {
        return 'typeahead_user';
    }
}