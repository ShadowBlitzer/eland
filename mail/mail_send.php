<?php

namespace mail;

use Monolog\Logger;
use Twig_Environment as Twig;
use service\config;

class mail_send
{
	private $mailer;
	private $monolog;
	private $twig;
	private $config;

	public function __construct(Logger $monolog,Twig $twig, config $config)
	{
		$this->monolog = $monolog;
		$this->twig = $twig;
		$this->config = $config;

		$enc = getenv('SMTP_ENC') ?: 'tls';
		$transport = new \Swift_SmtpTransport(getenv('SMTP_HOST'), getenv('SMTP_PORT'), $enc);
		$transport->setUsername(getenv('SMTP_USERNAME'))
			->setPassword(getenv('SMTP_PASSWORD'));

		$this->mailer = new \Swift_Mailer($transport);
		$this->mailer->registerPlugin(new \Swift_Plugins_AntiFloodPlugin(100, 30));
		$this->mailer->getTransport()->stop();
	}

	/**
	 *
	 */
	public function send(array $data)
	{
		$err = $monolog_vars = [];

		if (!isset($data['no_schema']))
		{
			if (!isset($data['schema']))
			{
				$err[] = sprintf(
					'no schema set for mail: %s', json_encode($data));
			}

			$schema = $data['vars']['schema'] = $data['schema'];
			$monolog_vars = ['schema' => $schema];
		
			if (!$this->config->get('mailenabled', $schema))
			{
				$err[] = sprintf('mail functionality not enabled in 
					configuration for mail %s', json_encode($data));
			}
		}

		if (!isset($data['template']))
		{
			$err[] = sprintf('no template set for mail %s', json_encode($data));
		}

		if (!isset($data['vars']) || !is_array($data['vars']))
		{
			$err[] = sprintf('no vars set for mail %s', json_encode($data));
		}

		if (!isset($data['to']))
		{
			$err[] = sprintf('no "to" set for mail %s', json_encode($data));
		}

		if (count($err))
		{
			foreach ($err as $msg)
			{
				$this->monolog->error($msg, $monolog_vars);
			}
		
			return;
		}

		if (!isset($data['reply_to']))
		{
			$data['vars']['no_reply'] = true;
		}

		$template = $this->twig->load('mail/' . $data['template'] . '.twig');
	
		$text = $template->renderBlock('text_body', $data['vars']);
		$html = $template->renderBlock('html_body', $data['vars']);
		$subject = $template->renderBlock('subject', $data['vars']);

		$message = new \Swift_Message();
		$message->setSubject($subject)
			->setBody($text)
			->addPart($html, 'text/html')
			->setTo($data['to']);

		if (isset($data['reply_to']))
		{
			$message->setReplyTo($data['reply_to']);

			$from = getenv('MAIL_FROM_ADDRESS');
	
			if (!$from)
			{
				$this->monolog->error(sprintf(
					'no MAIL_FROM_ADDRESS env set, please notify web master, mail: %s',
					json_encode($data)), $monolog_vars);
				return;
			}

			if (!filter_var($from, FILTER_VALIDATE_EMAIL))
			{
				$this->monolog->error(sprintf(
					'no valid MAIL_FROM_ADDRESS env set, please notify web master, mail: %s',
					json_encode($data)), $monolog_vars);
				return;
			}
		}
		else
		{
			$from = getenv('MAIL_NOREPLY_ADDRESS');
			
			if (!$from)
			{
				$this->monolog->error(sprintf(
					'no MAIL_NOREPLY_ADDRESS env set, please notify web master, mail: %s',
					json_encode($data)), $monolog_vars);
				return;
			}

			if (!filter_var($from, FILTER_VALIDATE_EMAIL))
			{
				$this->monolog->error(sprintf(
					'no valid MAIL_NOREPLY_ADDRESS env set, please notify web master, mail: %s',
					json_encode($data)), $monolog_vars);
				return;
			}
		}

		$from = isset($schema) ? [$from => $this->config->get('systemname', $schema)] : $from;

		$message->setFrom($from);

		if (isset($data['cc']))
		{
			$message->setCc($data['cc']);
		}

		$failed_recipients = [];

		if ($this->mailer->send($message, $failed_recipients))
		{
			$this->monolog->info(sprintf('mail send: %s', json_encode($data)), $monolog_vars);
		}
		else
		{
			$this->monolog->error(sprintf('failed sending mail %s, failed recipients: %s', 
				json_encode($data), json_encode($failed_recipients)),$monolog_vars);
		}

		$this->mailer->getTransport()->stop();
	}
}
