<?php

namespace service;

use League\HTMLToMarkdown\HtmlConverter;
use service\queue;
use Monolog\Logger;
use service\mailaddr;
use Twig_Environment as Twig;
use service\config;
use service\token;
use service\email_validate;

class mail
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

		if ($data['template'] === 'periodic_overview_messages_top'
			|| $data['template'] === 'periodic_overview_news_top')
		{
			$this->monolog->error('mail error: template not found ' . $data['template'],
				['schema' => $sch]);
			return;
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

	public function queue(array $data)
	{
		if (!isset($data['priority']))
		{
			$data['priority'] = 1000;
		}

		if (!$data['schema'])
		{
			$m = 'Mail: no schema set, data: ' . json_encode($data) . "\n";
			$this->monolog->info('mail: ' . $m);
			return $m;
		}

		$data['vars']['validate_param'] = '';

		if (!$this->config->get('mailenabled', $data['schema']))
		{
			$m = 'Mail functions are not enabled. ' . "\n";
			$this->monolog->info('mail: ' . $m, ['schema' => $data['schema']]);
			return $m;
		}

		if (!isset($data['template']) && !isset($data['template_from_config']))
		{
			if (!isset($data['subject']) || $data['subject'] == '')
			{
				$m = 'Mail "subject" is missing.';
				$this->monolog->error('mail: '. $m, ['schema' => $data['schema']]);
				return $m;
			}

			if ((!isset($data['text']) || $data['text'] == '')
				&& (!isset($data['html']) || $data['html'] == ''))
			{
				$m = 'Mail "body" (text or html) is missing.';
				$this->monolog->error('mail: ' . $m, ['schema' => $data['schema']]);
				return $m;
			}

			$data['subject'] = '[' . $this->config->get('systemtag', $data['schema']) . '] ' . $data['subject'];
		}

		if (!isset($data['to']) || !$data['to'])
		{
			$m = 'Mail "to" is missing for "' . $data['subject'] . '"';
			$this->monolog->error('mail: ' . $m, ['schema' => $data['schema']]);
			return $m;
		}

		$data['to'] = $this->mailaddr->get($data['to']);

		if (!count($data['to']))
		{
			$m = 'error: mail without "to" | subject: ' . $data['subject'];
			$this->monolog->error('mail: ' . $m, ['schema' => $data['schema']]);
			return $m;
		}

		$validate_ary = [];

		if (isset($data['validate_email']) && isset($data['template']))
		{
			foreach ($data['to'] as $email => $name)
			{
				if (!filter_var($email, FILTER_VALIDATE_EMAIL))
				{
					continue;
				}

				if (!$this->email_validate->is_validated($email, $data['schema']))
				{
					$token = $this->email_validate->get_token($email, $data['schema'], $data['template']);

					$validate_ary[$email] = $token;
				}
			}
		}

		if (isset($data['reply_to']))
		{
			$data['reply_to'] = $this->mailaddr->get($data['reply_to'], $data['schema']);

			if (!count($data['reply_to']))
			{
				$this->monolog->error('mail: error: invalid "reply to" : ' . $data['subject']);
				unset($data['reply_to']);
			}

			$data['from'] = $this->mailaddr->get('from', $data['schema']);
		}
		else
		{
			$data['from'] = $this->mailaddr->get('noreply', $data['schema']);
		}

		if (!count($data['from']))
		{
			$m = 'error: mail without "from" | subject: ' . $data['subject'];
			$this->monolog->error('mail: ' . $m);
			return $m;
		}

		if (isset($data['cc']))
		{
			$data['cc'] = $this->mailaddr->get($data['cc']);

			if (!count($data['cc']))
			{
				$this->monolog->error('mail error: invalid "reply to" : ' . $data['subject']);
				unset($data['cc']);
			}
		}

		$reply = (isset($data['reply_to'])) ? ' reply-to: ' . json_encode($data['reply_to']) : '';

		foreach ($validate_ary as $email_to => $validate_token)
		{
			$val_data = $data;

			$val_data['to'] = [$email_to => $data['to'][$email]];
			$val_data['vars']['validate_param'] = '&ev=' . $validate_token;

			unset($data['to'][$email_to]);

			$error = $this->queue->set('mail', $val_data);

			if (!$error)
			{

				$this->monolog->info('mail: Mail in queue with validate token ' . $validate_token .
					', subject: ' .
					($data['subject'] ?? '(template)') . ', from : ' .
					json_encode($data['from']) . ' to : ' . json_encode($data['to']) . ' ' .
					$reply . ' priority: ' . $data['priority'], ['schema' => $data['schema']]);
			}
		}

		if (!count($data['to']))
		{
			return;
		}

		$error = $this->queue->set('mail', $data);

		if (!$error)
		{
			$this->monolog->info('mail: Mail in queue, subject: ' .
				($data['subject'] ?? '(template)') . ', from : ' .
				json_encode($data['from']) . ' to : ' . json_encode($data['to']) . ' ' .
				$reply . ' priority: ' . $data['priority'], ['schema' => $data['schema']]);
		}
	}
}
