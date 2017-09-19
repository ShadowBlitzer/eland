<?php

namespace util;

trait eland_trait
{
    public function flash(string $type, $msg)
    {
        return $this['session']->getFlashBag()->add($type, $msg);
    }

	public function err($msg)
	{
		return $this['session']->getFlashBag()->add('error', $msg);
	}

	public function info($msg)
	{
		return $this['session']->getFlashBag()->add('info', $msg);
	}

	public function success($msg)
	{
		return $this['session']->getFlashBag()->add('success', $msg);
	}

	public function warning($msg)
	{
		return $this['session']->getFlashBag()->add('warning', $msg);
	}

	public function build_form($class, $data = [], $options = [])
	{
		return $this['form.factory']->createBuilder($class, $data, $options)
			->getForm();		
	}
}
