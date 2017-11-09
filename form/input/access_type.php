<?php

namespace form\input;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use form\input\addon_type;
use form\typeahead\typeahead_type_attr;

class access_type extends AbstractType
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
            'expanded'          => true,
            'multiple'          => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix()
    {
        return 'access';
    }
}