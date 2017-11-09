<?php

namespace form\typeahead;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use form\input\addon_type;
use form\typeahead\typeahead_type_attr;

class typeahead_type extends AbstractType
{
    private $typeahead_type_attr;

    public function __construct(typeahead_type_attr $typeahead_type_attr)
    {
        $this->typeahead_type_attr = $typeahead_type_attr;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['typeahead_attr'] = $this->typeahead_type_attr->get($options);
    }    

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'source_id'         => null,
            'source_route'      => null,     
            'source_params'     => null,
            'source'            => null, 
            'process'           => null,
        ]);
    }

    public function getParent()
    {
        return addon_type::class;
    }

    public function getBlockPrefix()
    {
        return 'typeahead';
    }
}