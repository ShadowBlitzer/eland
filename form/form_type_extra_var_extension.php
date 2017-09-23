<?php

namespace form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class form_type_extra_var_extension extends AbstractTypeExtension
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($options['explain'])) 
        {
            $view->vars['explain'] = $options['explain'];
        }

        if (isset($options['sub_label']))
        {
             $view->vars['sub_label'] = $options['sub_label'];           
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined([
            'explain',
            'sub_label'
        ]);
    }

    public function getExtendedType()
    {
        return FormType::class;
    }
}
