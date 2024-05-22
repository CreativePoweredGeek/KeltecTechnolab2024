<?php namespace Zenbu\librairies\platform\ee;

class Hook
{
    public static function call()
    {
    	$args = func_get_args();

    	$hook = isset($args[0]) ? $args[0] : null;
    	$data = isset($args[1]) ? $args[1] : null;

    	if(! $hook)
		{
			return null;
		}

        if($hook == 'zenbu_modify_cell_data')
        {
            /**
            *   ===========================================
            *   Extension Hook zenbu_modify_cell_data
            *   ===========================================
            *
            *   Modifies the display of entry data columns in the entry listing
            *   @param  string  $data         The output string to be displayed in the Zenbu column. **You must return the
            *                                   original array!**
            *   @return string  $data         The final output to be displayed in the Zenbu column
            */

			$supplementalData = isset($args[2]) ? $args[2] : null;

            if (ee()->extensions->active_hook($hook) === TRUE)
            {
                // $supplementalData is the entry
                $data = ee()->extensions->call($hook, $data, $supplementalData);
				if (ee()->extensions->end_script === TRUE)
				{
					return $data;
				}
            }

        }
		elseif($hook == 'zenbu_modify_query')
		{
			/**
			 * ===========================================
			 * Extension Hook zenbu_modify_query
			 * ===========================================
			 *
			 * Modifies the entry query the way you want (eg. add queries for a custom field)
			 * @param	object		$query			The query currently being built. Based on ee('Model')->get('ChannelEntry')
			 * @param	collection	$custom_fields	The available custom fields
			 * @param	array 		$filters 		The current search filter conditions
			 * @return object  $query
			 */

			$query         = $data;
			$custom_fields = isset($args[2]) ? $args[2] : null;
			$filters   = isset($args[3]) ? $args[3] : null;

			if (ee()->extensions->active_hook($hook) === TRUE)
			{
				$query = ee()->extensions->call($hook, $query, $custom_fields, $filters);

				if (ee()->extensions->end_script === TRUE)
				{
					return $query;
				}
			}

		}
        else
		{
			if (ee()->extensions->active_hook($hook) === TRUE)
			{
				$data = ee()->extensions->call($hook, $data);
				if (ee()->extensions->end_script === TRUE) return $data;
			}
		}

        return $data;
    }

    // --------------------------------------------------------------------
}