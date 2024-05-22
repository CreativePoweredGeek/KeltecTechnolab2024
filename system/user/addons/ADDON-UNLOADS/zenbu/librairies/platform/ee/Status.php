<?php namespace Zenbu\librairies\platform\ee;

use Zenbu\librairies\platform\ee\Base as Base;
use Zenbu\librairies\platform\ee\ArrayHelper;
use Mexitek\PHPColors\Color;

class Status extends Base
{
	public function __construct()
	{
		parent::__construct();
		$this->default_statuses = array(
				array(
					'status_id'    => 1,
					'status'       => 'open',
					'status_order' => 1,
					'highlight'    => '009933'
				),
				array(
					'status_id'    => 2,
					'status'       => 'closed',
					'status_order' => 2,
					'highlight'    => '990000'
				)
			);

	}

	/**
	 * Get statuses
	 * @return	array
	 */
	public static function getAllStatuses()
	{
		if($statuses = Cache::get('all_statuses'))
		{
			return $statuses;
		}

		$statuses = ee('Model')->get('Status')->all();

		Cache::set('all_statuses', $statuses, 300);

		return $statuses;
	}

	public static function getStatusById($id)
	{
		$statuses = self::getAllStatuses();
		$status = $statuses->filter('status_id', $id)->first();

		$out = null;

		if($status)
		{
			$out = $status->getValues();
			$out['font_color'] = self::calculateFontColor($out['highlight']);
		}

		return $out;
	}

	public static function calculateFontColor($highlight)
	{
		try
		{
			$highlight = new \Mexitek\PHPColors\Color($highlight);
			$fontcolor = ($highlight->isLight())
				? $highlight->darken(100)
				: $highlight->lighten(100);
		}
		catch (\Exception $e)
		{
			$fontcolor = 'ffffff';
		}

		return $fontcolor;
	}


}
