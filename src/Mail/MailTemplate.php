<?php

namespace App\Mail;

use Twig_Environment as Twig;
use App\Exception\MethodCallOrderException;

class MailTemplate
{
	private $twig;
	private $template;
	private $vars;

	public function __construct(Twig $twig)
	{
		$this->twig = $twig;
	}

	public function setTemplate(string $template):MailTemplate
	{
		if (isset($this->template))
		{
			throw new MethodCallOrderException(sprintf(
				'method clear() needs to be called first in %', __CLASS__));
		}
	
		$this->template = $this->twig->load('mail/' . $template . '.twig');	
		return $this;
	}

	public function setVars(array $vars):MailTemplate
	{
		if (isset($this->vars))
		{
			throw new MethodCallOrderException(sprintf(
				'method clear() needs to be called first in %', __CLASS__));
		}

		$this->vars = $vars;
		return $this;
	}

	public function getSubject():string 
	{
		return $this->template->renderBlock('subject', $this->vars);
	}

	public function getText():string 
	{
		return $this->template->renderBlock('text_body', $this->vars);
	}

	public function getHtml():string 
	{
		return $this->template->renderBlock('html_body', $this->vars);
	}

	public function clear():MailTemplate
	{
		unset($this->template, $this->vars);

		return $this;
	}
}
