<?php

namespace form\input;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use form\input\addon_type;

class password_addon_type extends addon_type
{
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['type'] = 'password';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'addon_fa'      => 'key',
        ]);
    }

    public function getParent()
    {
        return PasswordType::class;
    }

    public function getBlockPrefix()
    {
        return 'addon';
    }
}