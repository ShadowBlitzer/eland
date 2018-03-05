<?php

namespace App\Service;

class Token
{
	private $length = 16;
	private $hyphenChance = 2;
	private $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
	private $charsH;
	private $charsLen;
	private $charsHLen;

	public function __construct()
	{
		$this->setLen();
	}

	private function setLen():token
	{
		$this->charsH = str_repeat('-', $this->hyphenChance) . $this->chars;
		$this->charsLen = strlen($this->chars) - 1;
		$this->charsHLen = strlen($this->chars_h) - 1;

		return $this;
	}

	public function setLength(int $length):token
	{
		$this->length = $length;

		return $this;
	}

	public function setHyphenhance(int $hyphenChance):token
	{
		$this->hyphenChance = $hyphenChance;

		$this->setLen();

		return $this;
	}

	public function setChars(string $chars):token
	{
		$this->chars = $chars;

		$this->setLen();

		return $this;
	}

	public function gen():string
	{
		$token = '';
		$ch = '-';

		for ($j = 0; $j < $this->length; $j++)
		{
			if ($ch === '-')
			{
				$ch = $this->chars[random_int(0, $this->charsLen)];
			}
			else
			{
				$ch = $this->charsH[random_int(0, $this->charsHLen)];
			}

			$token .= $ch;

			if ($j === $this->length - 2)
			{
				$ch = '-';
			}
		}

		return $token;
	}
}

