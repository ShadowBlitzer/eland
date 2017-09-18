<?php

namespace form;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Translation\TranslatorInterface;

class etoken_validation_listener implements EventSubscriberInterface
{
    private $etoken_manager;
    private $translator;

    public function __construct( 
        etoken_manager_interface $etoken_manager, 
        TranslatorInterface $translator = null
    )
    {
        $this->etoken_manager = $etoken_manager;
        $this->translator = $translator;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SUBMIT => 'pre_submit',
        );
    }

    public function pre_submit(FormEvent $event)
    {
        $form = $event->getForm();

        if ($form->isRoot() && $form->getConfig()->getOption('compound')) 
        {
            $data = $event->getData();

            $error_message = $this->etoken_manager->get_error_message($data['_etoken']);

            if ($error_message)
            {
                if (null !== $this->translator) 
                {
                    $error_message = $this->translator->trans($error_message);
                }

                $form->addError(new FormError($error_message));
            }

            if (is_array($data)) 
            {
                unset($data['_etoken']);
                $event->setData($data);
            }
        }
    }
}
