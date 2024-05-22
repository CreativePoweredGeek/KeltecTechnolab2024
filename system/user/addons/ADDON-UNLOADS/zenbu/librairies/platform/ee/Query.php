<?php namespace Zenbu\librairies\platform\ee;

use Zenbu\librairies\platform\ee\View;
use Zenbu\librairies\platform\ee\Url;
use Zenbu\librairies\platform\ee\Localize;
use Zenbu\librairies;

class Query
{
    static public function whereOperator($row)
	{
		if(in_array($row[1], ['contains', 'beginsWith', 'endsWith']))
		{
			return 'LIKE';
		}

		if(in_array($row[1], ['doesntContain', 'doesntBeginWith', 'doesntEndWith']))
		{
			return 'NOT LIKE';
		}

		if(in_array($row[1], ['is', 'isOn', 'containsExactly', 'isEmpty']))
		{
			return '==';
		}

		if(in_array($row[1], ['isNot', 'isOff', 'isNotEmpty']))
		{
			return '!=';
		}

		return '==';
	}

	static public function whereValue($row)
	{
		if(in_array($row[1], ['contains', 'doesntContain']))
		{
			return '%'.$row[2].'%';
		}

		if(in_array($row[1], ['beginsWith', 'doesntBeginWith']))
		{
			return $row[2].'%';
		}

		if(in_array($row[1], ['endsWith', 'doesntEndWith']))
		{
			return '%'.$row[2];
		}

		if(in_array($row[1], ['is', 'isNot', 'containsExactly']))
		{
			return $row[2];
		}

		if(in_array($row[1], ['isOn', 'isOff']))
		{
			if(in_array($row[0],  ['sticky']))
			{
				return 'y';
			}

			return 1;
		}

		if(in_array($row[1], ['isEmpty', 'isNotEmpty']))
		{
			return '';
		}

		return null;
	}

	static public function whereDateOperator($row)
	{
		if(strncmp($row[1], 'inTheLast', 9) == 0)
		{
			return '>=';
		}

		if(strncmp($row[1], 'inTheNext', 9) == 0)
		{
			return '<=';
		}

		return '==';
	}

	static public function whereDateValue($row)
	{
		$days = substr($row[1], 9);

		if(strncmp($row[1], 'inTheLast', 9) == 0)
		{
			return Localize::format('U', Localize::now() - ($days * 24 * 60 * 60));
		}

		if(strncmp($row[1], 'inTheNext', 9) == 0)
		{
			return Localize::format('U', Localize::now() + ($days * 24 * 60 * 60));
		}

		return null;
	}
}