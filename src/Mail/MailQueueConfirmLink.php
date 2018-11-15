<?php declare(strict_types=1);

namespace App\Mail;

use Symfony\Component\HttpFoundation\RequestStack;
use App\Service\TokenUrl;
use App\Mail\MailQueue;

class MailQueueConfirmLink
{
	private $request;
	private $tokenUrl;	
	private $mailQueue;

	private $data;
	private $template;
	private $route;
	private $to;

	public function __construct(	
		RequestStack $requestStack,
		TokenUrl $tokenUrl,
		MailQueue $mailQueue
	)
	{
		$this->request = $requestStack->getCurrentRequest();
		$this->tokenUrl = $tokenUrl;
		$this->mailQueue = $mailQueue;
	}

	public function setTo(array $to):MailQueueConfirmLink
	{
		$this->to = $to;
		return $this;
	}

	public function setData(array $data):MailQueueConfirmLink
	{
		$this->data = $data;
		return $this;
	}

	public function setTemplate(string $template):MailQueueConfirmLink
	{
		$this->template = $template;
		return $this;
	}

	public function setRoute(string $route):MailQueueConfirmLink
	{
		$this->route = $route;
	
		return $this;
	}

	public function put():MailQueueConfirmLink
	{
		$this->putParam($this->to, $this->data, $this->template, $this->route);
		$this->clear();	
		return $this;
	}

	public function clear():MailQueueConfirmLink
	{
		unset($this->to, $this->data, $this->template, $this->route);
		return $this;
	}

	public function putParam(array $to, array $data, string $template, string $route):MailQueueConfirmLink
	{
		$confirmLink = $this->tokenUrl->gen($route, $data);

		$vars = array_merge($data, [
			'confirm_link'		=> $confirmLink,
		]);
	
		$schema = $this->request->attributes->get('schema');

		if (isset($schema))
		{
			$this->mailQueue->setSchema($schema);
		}

		$this->mailQueue->setTemplate($template)
			->setVars($vars)
			->setTo($to)
			->setPriority(1000000)
			->put();
	
		return $this;
	}
}
