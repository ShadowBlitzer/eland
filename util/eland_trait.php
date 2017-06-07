<?php

namespace util;

trait eland_trait
{
    public function flash(string $type, $msg)
    {
		$msg = is_array($msg) ? implode('<br>', $msg) : $msg;
        return $this['session']->getFlashBag()->add($type, $msg);
    }

	public function err($msg)
	{
		$msg = is_array($msg) ? implode('<br>', $msg) : $msg;
		return $this['session']->getFlashBag()->add('error', $msg);
	}

	public function info($msg)
	{
		$msg = is_array($msg) ? implode('<br>', $msg) : $msg;
		return $this['session']->getFlashBag()->add('info', $msg);
	}

	public function success($msg)
	{
		$msg = is_array($msg) ? implode('<br>', $msg) : $msg;
		return $this['session']->getFlashBag()->add('success', $msg);
	}

	public function warning($msg)
	{
		$msg = is_array($msg) ? implode('<br>', $msg) : $msg;
		return $this['session']->getFlashBag()->add('warning', $msg);
	}
}
