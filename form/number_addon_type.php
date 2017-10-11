<?php

namespace form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use form\addon_type;

class number_addon_type extends AbstractType
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($options['addon_fa'])) 
        {
            $view->vars['addon_fa'] = $options['addon_fa'];
        }

        if (isset($options['addon_label'])) 
        {
            $view->vars['addon_label'] = $options['addon_label'];
        }
    }    

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'addon_fa'      => null,
            'addon_label'   => null,
        ]);
    }

    public function getParent()
    {
        return NumberType::class;
    }

    public function getBlockPrefix()
    {
        return 'number_addon';
    }
}