<?php

namespace form\input;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use form\input\addon_type;

class number_addon_type extends addon_type
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['type'] = 'number';
    }

    public function getParent()
    {
        return NumberType::class;
    }

    public function getBlockPrefix()
    {
        return 'addon';
    }
}