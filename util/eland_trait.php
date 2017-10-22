<?php

namespace util;

trait eland_trait
{
    public function flash(string $type, string $msg, array $param = [])
    {
		$msg = $this['translator']->trans($msg, $param);
        $this['session']->getFlashBag()->add($type, $msg);
    }

	public function err(string $msg, array $param = [])
	{
		$this->flash('error', $msg, $param);
	}

	public function info(string $msg, array $param = [])
	{
		$this->flash('info', $msg, $param);
	}

	public function success(string $msg, array $param = [])
	{
		$this->flash('success', $msg, $param);
	}

	public function warning(string $msg, array $param = [])
	{
		$this->flash('warning', $msg, $param);
	}

	public function reroute(string $route, array $param = [])
	{
		return $this->redirect($this->path($route, $param));
	}

	public function build_form($class, $data = [], $options = [])
	{
		return $this['form.factory']->createBuilder($class, $data, $options)
			->getForm();		
	}

	public function build_named_form($name, $class, $data = [], $options = [])
	{
		return $this['form.factory']->createNamedBuilder($name, $class, $data, $options)
			->getForm();		
	}	
}
