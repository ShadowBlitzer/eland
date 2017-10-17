<?php

namespace mail;

use Twig_Environment as Twig;
use exception\method_call_order_exception;

class mail_template
{
	private $twig;

	private $template;
	private $vars;

	public function __construct(Twig $twig)
	{
		$this->twig = $twig;
	}

	public function set_template(string $template):mail_template
	{
		if (isset($this->template))
		{
			throw new method_call_order_exception(sprintf(
				'method clear() needs to be called first in %', __CLASS__));
		}
	
		$this->template = $this->twig->load('mail/' . $template . '.twig');	
		return $this;
	}

	public function set_vars(array $vars):mail_template
	{
		if (isset($this->vars))
		{
			throw new method_call_order_exception(sprintf(
				'method clear() needs to be called first in %', __CLASS__));
		}

		$this->vars = $vars;
		return $this;
	}

	public function get_subject():string 
	{
		return $this->template->renderBlock('subject', $this->vars);
	}

	public function get_text():string 
	{
		return $this->template->renderBlock('text_body', $this->vars);
	}

	public function get_html():string 
	{
		return $this->template->renderBlock('html_body', $this->vars);
	}

	public function clear()
	{
		unset($this->template, $this->vars);
	}
}
