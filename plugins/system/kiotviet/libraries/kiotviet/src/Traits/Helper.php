<?php
namespace Kiotviet\Traits;

trait Helper
{
	public static function getStockroomIdByStateCode($stateCode)
	{
		$paramsPlugin = json_decode(\JPluginHelper::getPlugin('system', 'kiotviet')->params);

		foreach ($paramsPlugin->mapping_branch as $b => $mappingBrach)
		{
			if ($stateCode == $mappingBrach->state)
			{
				foreach ($paramsPlugin->mapping_stock as $s => $mappingStock)
				{
					if ($mappingStock->branch == $mappingBrach->branch)
					{
						return $mappingStock->stock;
					}
				}
				break;
			}
		}

		return 0;
	}

	public static function lcfirstObject($object)
	{
		$result = new \stdClass;

		foreach ($object as $key => $val)
		{
			$keys = lcfirst($key);

			if (!is_array($val) || $key == 'Images')
			{
				$result->$keys = $val;
			}
			else
			{
				for ($i = 0; $i < count($val); $i++)
				{
					$subResult = new \stdClass;

					foreach ($val[$i] as $keySub => $valSub)
					{
						$keySubChange = lcfirst($keySub);
						$subResult->$keySubChange = $valSub;
					}

					$result->$keys[$i] = $subResult;
				}
			}
		}

		return $result;
	}
}