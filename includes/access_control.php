<?php

class access_control
{
	private $acc_ary = array(
		'admin'	=> array(
			'level'	=> 0,
			'label'	=> 'admin',
			'class'	=> 'info',
		),
		'users'	=> array(
			'level'	=> 1,
			'label'	=> 'leden',
			'class'	=> 'warning',
		),
		'interlets'	=> array(
			'level'	=> 2,
			'label'	=> 'interlets',
			'class'	=> 'success',
		),
	);

	private $acc_ary_search = array(
		0 => 'admin',
		1 => 'users',
		2 => 'interlets',
	);

	/**
	 *
	 */

	public function __construct()
	{
	}

	/*
	 *
	 */

	public function get_label($access = 'admin', $size = 'xs')
	{
		if ($this->acc_ary_search[$access])
		{
			$access = $this->acc_ary_search[$access];
		}

		$acc = $this->acc_ary[$access];

		return '<span class="label label-' . $acc['class'] . ' label-' . $size . '">' . $acc['label'] . '</span>';
	}

	/*
	 *
	 */

	public function get_post_value($name = 'access')
	{
		if ($this->acc_ary[$_POST[$name]])
		{
			return $this->acc_ary[$_POST[$name]]['level'];
		}
		return false;
	}

	/**
	 *
	 */

	public function get_post_error($name = 'access')
	{
		if ($this->acc_ary[$_POST[$name]])
		{
			return false;
		}
		return 'Kies een zichtbaarheid.';
	}

	/*
	 *
	 */

	public function get_radio_buttons($access_type_id = 'tel', $value = false, $omit_access = false, $name = 'access',$size = 'xs')
	{
		global $schema;

		$acc_ary = $this->acc_ary;

		if ($value === false)
		{
			$selected = false;
		}
		else if ($this->acc_ary[$value])
		{
			$selected = $value;
		}
		else
		{
			$selected = $this->acc_ary_search[$value];
		}

		if ($omit_access)
		{
			if (!is_array($omit_access))
			{
				$omit_access = array($omit_access);
			}

			foreach ($omit_access as $omit)
			{
				unset($acc_ary[$omit]);
			}
		}

		$out = '<div class="form-group">';
		$out .= '<label for="' . $name . '" class="col-sm-2 control-label">Zichtbaarheid</label>';
		$out .= '<div class="col-sm-10"';
		$out .= ($access_type_id) ? ' data-access-type-id="' . $schema . '_' . $access_type_id . '"' : '';
		$out .= '>';

		foreach ($acc_ary as $key => $ary)
		{
			$out .= '<label class="radio-inline">';
			$out .= '<input type="radio" name="' . $name . '"';
			$out .= ($key == 'admin') ? ' id="' . $name . '"' : '';
			$out .= ($key === $selected) ? ' checked="checked"' : '';
			$out .= ' value="' . $key . '" required> ';
			$out .= '<span class="btn btn-' . $ary['class'] . ' btn-' . $size . '">';
			$out .= $ary['label'];
			$out .= '</span>';
			$out .= '</label>';
		}

		$out .= '</div>';
		$out .= '</div>';

		return $out;
	}
}
