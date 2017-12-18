<?php

namespace App\Form\Extension;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Translation\TranslatorInterface;
use App\Form\Extension\EtokenManagerInterface;

class EtokenValidationListener implements EventSubscriberInterface
{
    private $etokenManager;
    private $translator;

    public function __construct( 
        EtokenManagerInterface $etokenManager, 
        TranslatorInterface $translator = null
    )
    {
        $this->etokenManager = $etokenManager;
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

            $errorMessage = $this->etokenManager->getErrorMessage($data['_etoken']);

            if ($error_message)
            {
                if (null !== $this->translator) 
                {
                    $errorMessage = $this->translator->trans($errorMessage);
                }

                $form->addError(new FormError($errorMessage));
            }

            if (is_array($data)) 
            {
                unset($data['_etoken']);
                $event->setData($data);
            }
        }
    }
}
