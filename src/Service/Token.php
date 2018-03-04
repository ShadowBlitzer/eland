<?php

namespace App\Service;

class Token
{
	private $length = 16;
	private $hyphen_chance = 2;
	private $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
	private $chars_h;
	private $chars_len;
	private $chars_h_len;

	public function __construct()
	{
		$this->set_len();
	}

	private function set_len():token
	{
		$this->chars_h = str_repeat('-', $this->hyphen_chance) . $this->chars;
		$this->chars_len = strlen($this->chars) - 1;
		$this->chars_h_len = strlen($this->chars_h) - 1;

		return $this;
	}

	public function set_length(int $length):token
	{
		$this->length = $length;

		return $this;
	}

	public function set_hyphen_chance(int $hyphen_chance):token
	{
		$this->hyphen_chance = $hyphen_chance;

		$this->set_len();

		return $this;
	}

	public function set_chars(string $chars):token
	{
		$this->chars = $chars;

		$this->set_len();

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
				$ch = $this->chars[random_int(0, $this->chars_len)];
			}
			else
			{
				$ch = $this->chars_h[random_int(0, $this->chars_h_len)];
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
