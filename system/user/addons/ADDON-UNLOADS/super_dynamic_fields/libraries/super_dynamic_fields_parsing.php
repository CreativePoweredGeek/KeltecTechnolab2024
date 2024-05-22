<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* The software is provided "as is", without warranty of any
* kind, express or implied, including but not limited to the
* warranties of merchantability, fitness for a particular
* purpose and noninfringement. in no event shall the authors
* or copyright holders be liable for any claim, damages or
* other liability, whether in an action of contract, tort or
* otherwise, arising from, out of or in connection with the
* software or the use or other dealings in the software.
* -----------------------------------------------------------
* Amici Infotech - Super Dynamic Fields
*
* @package      superDynamicFields
* @author       Mufi
* @copyright    Copyright (c) 2019, Amici Infotech.
* @link         http://expressionengine.amiciinfotech.com/super-dynamic-fields
* @filesource   ./system/expressionengine/third_party/super_dynamic_fields/libraries/super_dynamic_fields_parsing.php
*/

class Super_dynamic_fields_parsing
{

	public function __construct()
	{
		ee()->load->library('template');
	}

	/**
    * Parse the EE template to HTML
    * @param $body (Actual Body to parse things)
    **/
	function template_parser($t_id)
	{
		$body = $this->message_body($t_id);
		/*return back if nothing found*/
		if($body === "")
		{
			return false;
		}
		
		/*Set old TMPL seprate to use after*/
		$OLD_TMPL = isset(ee()->TMPL) ? ee()->TMPL : NULL;
					
		/*Create new object to handle template parsing*/
		/*ee()->TMPL = new EE_Template();*/
		ee()->remove('TMPL');
		ee()->set('TMPL', new EE_Template());

		/*Load all EE snippets*/
		$this->load_snippets();

		/*Load needful library classes*/
		ee()->load->library('typography');

		/*Inititalize*/
		ee()->typography->initialize();
		ee()->typography->convert_curly = FALSE;
		ee()->typography->allow_img_url = FALSE;
		ee()->typography->auto_links    = FALSE;
		ee()->typography->encode_email  = FALSE;

		/*Remove all EE comments*/
		$body = preg_replace( "/{!--[\s\S]*?--}/", "", $body);
		ee()->TMPL->parse($body, FALSE, ee()->config->item('site_id'));

		$vars['params'] 	= ee()->TMPL->tagparams;
		$vars['msg_body'] 	= ee()->TMPL->parse_globals(ee()->TMPL->final_template);
		$vars['msg_body'] 	= $this->parse_vars($vars['msg_body'], $body);

		/*Reset old TMPL after use the template parser*/
		// ee()->TMPL = $OLD_TMPL;
		ee()->remove('TMPL');
		ee()->set('TMPL', $OLD_TMPL);

		return $vars;

	}

	/**
    * Parse Variables with tagdata
    * @param $str (String tagdata)
    * @param $data (Array of Parse variables)
    **/
	function parse_vars($str, $data)
	{
		$out = ee()->TMPL->parse_variables_row($str, $data);
		return $out;
	}

	/*Load EE snippets and replace with our tagdata*/
	function load_snippets()
	{

		ee()->db->select('snippet_name, snippet_contents');
		ee()->db->where('(site_id = ' . ee()->db->escape_str(ee()->config->item('site_id')) . ' OR site_id = 0)');
		$fresh = ee()->db->get('snippets');

		if ($fresh->num_rows() > 0)
		{

			$snippets = array();

			foreach ($fresh->result() as $var)
			{
				$snippets[$var->snippet_name] = $var->snippet_contents;
			}

			ee()->config->_global_vars = array_merge(ee()->config->_global_vars, $snippets);

			unset($snippets);
			unset($fresh);

		}

	}

	/*Get Template data from either template or file*/
	function message_body($t_id)
	{

		$template_data = "";
		$templates = ee('Model')->get('Template')->filter('template_id', $t_id)->first();
		if($templates != "")
		{
			$template_data = $templates->template_data;
		}

		return $template_data;
		
		/*$query = ee()->db->query("SELECT tg.group_name, template_name, template_data, template_type, template_notes, cache, refresh, no_auth_bounce, allow_php, php_parse_location, save_template_file
								 FROM exp_templates t, exp_template_groups tg
								 WHERE t.template_id = '" . ee()->db->escape_str($t_id) . "'
								 AND tg.group_id = t.group_id");

		if ($total > 0)
		{

			$out = $query->row('template_data');

			if (ee()->config->item('save_tmpl_files') == 'y' && ee()->config->item('tmpl_file_basepath') != '' && $query->row('save_template_file') == 'y')
			{

				ee()->load->library('api');
				ee()->api->instantiate('template_structure');

				$basepath = rtrim(ee()->config->item('tmpl_file_basepath'), '/') . '/';

				$basepath .= ee()->config->item('site_short_name') . '/' . $query->row('group_name') . '.group/' . $query->row('template_name') . ee()->api_template_structure->file_extensions($query->row('template_type'));


				if (file_exists($basepath))
				{
					$out = file_get_contents($basepath);
				}

			}
			
		}

		return $out;*/
	}

}