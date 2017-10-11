<?php

namespace mail;

use League\HTMLToMarkdown\HtmlConverter;
use service\queue;
use Monolog\Logger;
use service\mailaddr;
use Twig_Environment as Twig;
use service\config;
use service\token;
use service\email_validate;
use exception\missing_schema_exception;
use exception\missing_parameter_exception;
use exception\configuration_exception;

class mail_process
{
	private $converter;
	private $mailer;
	private $queue;
	private $monolog;
	private $mailaddr;
	private $twig;
	private $email_validate;

	public function __construct(queue $queue, Logger $monolog,
		mailaddr $mailaddr, Twig $twig, config $config,
		email_validate $email_validate)
	{
		$this->queue = $queue;
		$this->monolog = $monolog;
		$this->mailaddr = $mailaddr;
		$this->twig = $twig;
		$this->config = $config;
		$this->email_validate = $email_validate;

		$enc = getenv('SMTP_ENC') ?: 'tls';
		$transport = new \Swift_SmtpTransport(getenv('SMTP_HOST'), getenv('SMTP_PORT'), $enc);
		$transport->setUsername(getenv('SMTP_USERNAME'))
			->setPassword(getenv('SMTP_PASSWORD'));

		$this->mailer = new \Swift_Mailer($transport);

		$this->mailer->registerPlugin(new \Swift_Plugins_AntiFloodPlugin(100, 30));

		$this->mailer->getTransport()->stop();

		$this->converter = new HtmlConverter();
		$converter_config = $this->converter->getConfig();
		$converter_config->setOption('strip_tags', true);
		$converter_config->setOption('remove_nodes', 'img');
	}

	/**
	 *
	 */
	public function process(array $data)
	{
		if (!isset($data['schema']))
		{
			$app->monolog->error('mail error: mail in queue without schema');
			return;
		}

		$data['vars']['schema'] = $data['schema'];

		$sch = $data['schema'];
		unset($data['schema']);

		if (!$this->config->get('mailenabled', $sch))
		{
			$m = 'Mail functions are not enabled. ' . "\n";
			echo $m;
			$this->monolog->error('mail: ' . $m, ['schema' => $sch]);
			return ;
		}

		if (isset($data['template']) && isset($data['vars']))
		{
			$template_subject = $this->twig->loadTemplate('mail/' . $data['template'] . '.subject.twig');
			$template_html = $this->twig->loadTemplate('mail/' . $data['template'] . '.html.twig');
			$template_text = $this->twig->loadTemplate('mail/' . $data['template'] . '.text.twig');

			$data['subject']  = $template_subject->render($data['vars']);
			$data['text'] = $template_text->render($data['vars']);
			$data['html'] = $template_html->render($data['vars']);
		}
		else if (isset($data['template_from_config']) && isset($data['vars']))
		{
			$template = $this->config->get($data['template_from_config'], $sch);

			if (!$template)
			{
				$this->monolog->error('mail error: no template set in config for ' . $data['template_from_config'],
					['schema' => $sch]);
				return;
			}

			try
			{
				$template_subject = $this->twig->loadTemplate('mail/' . $data['template_from_config'] . '.subject.twig');
				$template_html = $this->twig->createTemplate($template);

				$data['subject']  = $template_subject->render($data['vars']);
				$data['text'] = $this->converter->convert($data['html']);
				$data['html'] = $template_html->render($data['vars']);
			}
			catch (Exception $e)
			{
				$this->monolog->error('Fout in mail template: ' . $e->getMessage(), ['schema' => $sch]);
				return;
			}
		}
		else
		{
			if (!isset($data['subject']))
			{
				$this->monolog->error('mail error: mail without subject', ['schema' => $sch]);
				return;
			}

			if (!isset($data['text']))
			{
				if (isset($data['html']))
				{
					$data['text'] = $this->converter->convert($data['html']);
				}
				else
				{
					$this->monolog->error('mail error: mail without body content', ['schema' => $sch]);
					return;
				}
			}
		}

		if (!$data['to'])
		{
			$this->monolog->error('mail error: mail without "to" | subject: ' . $data['subject'], ['schema' => $sch]);
			return;
		}

		if (!$data['from'])
		{
			$this->monolog->error('mail error: mail without "from" | subject: ' . $data['subject'], ['schema' => $sch]);
			return;
		}

		$message = new \Swift_Message();
		$message->setSubject($data['subject'])
			->setBody($data['text'])
			->setTo($data['to'])
			->setFrom($data['from']);

		if (isset($data['html']))
		{
			$message->addPart($data['html'], 'text/html');
		}

		if (isset($data['reply_to']))
		{
			$message->setReplyTo($data['reply_to']);
		}

		if (isset($data['cc']))
		{
			$message->setCc($data['cc']);
		}

		try
		{
			if ($this->mailer->send($message, $failed_recipients))
			{
				$this->monolog->info('mail: message send to ' . implode(', ', $data['to']) . ' subject: ' . $data['subject'], ['schema' => $sch]);
			}
			else
			{
				$this->monolog->error('mail error: failed sending message to ' . implode(', ', $data['to']) . ' subject: ' . $data['subject'], ['schema' => $sch]);
				$this->monolog->error('Failed recipients: ' . implode(', ', $failed_recipients), ['schema' => $sch]);
			}
		}
		catch (Exception $e)
		{
			$err = $e->getMessage();
			error_log('mail queue: ' . $err);
			$this->monolog->error('mail queue error: ' . $err . ' | subject: ' . $data['subject'] . ' ' . implode(', ', $data['to']), ['schema' => $sch]);		
		}

		$this->mailer->getTransport()->stop();
	}


	public function put(array $data)
	{
		if (!isset($data['schema']))
		{
			throw new missing_schema_exception(
				'Schema is missing in mail ' . json_encode($data)
			);
		}

		if (!$this->config('mailenabled', $data['schema']))
		{
			throw new configuration_exception(sprintf(
				'mail functionality not enabled in 
				configuration in schema %s for mail %s',
				$schema, json_encode($data)
			);
		}

		if (!isset($data['template']))
		{
			throw new missing_parameter_exception(
				'No template set in mail ' . json_encode($data)
			);
		}

		if (!isset($data['subject']))
		{
			throw new missing_parameter_exception(
				'No subject set in mail ' . json_encode($data)
			);			
		}

		if (!isset($data['vars']) || !is_array($data['vars']))
		{
			throw new missing_parameter_exception(
				'No vars array set in mail ' . json_encode($data)
			);			
		}
	
		if (!isset($data['to']))
		{
			throw new missing_parameter_exception(
				'No "to" set in mail ' . json_encode($data)
			);	
		}

		if (!isset($data['priority']))
		{
			$data['priority'] = 1000;
		}

		$this->queue->set('mail', $data);
	}
}
