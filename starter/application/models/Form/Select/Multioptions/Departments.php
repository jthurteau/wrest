<?php
/*******************************************************************************
Form/Select/Multioptions/Departments.php

Created by Troy Hurteau (jthurtea@ncsu.edu) 
NCSU Libraries, NC State University (libraries.opensource@ncsu.edu).

Copyright (c) 2012 North Carolina State University, Raleigh, NC.

###LICENSE###

*******************************************************************************/

class Form_Select_Multioptions_Departments
{
	
	public static function getOptions($additions = array())
	{

		$options = array();
		$values = LibAd::getValues('department');
		$options[''] = '';
		foreach($values as $value) {
			$options[$value] = $value;
		}
		if (is_array($additions)) {
			foreach($additions as $addition){
				$options[$addition] = $addition;
			}
		}
		asort($options);
		return $options;
	}
	
}