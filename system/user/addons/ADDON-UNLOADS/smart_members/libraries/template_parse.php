<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Template_parse
{

	/* Important globel variables */ 
	public $member_id = 0;

	public function __construct()
	{

		/*Setup instance to this class*/
		/*ee()->tmpl_parse =& $this;*/
		
		/* Neeful Library classes */
		ee()->load->library('template');
		
	}

	/**
    * Parse the EE template to HTML
    * @param $msg_data (Array of needful data)
    **/
	function template_parser($msg_data="")
	{
		
		/*return back if nothing found*/
		if($msg_data === "")
		{
			return false;
		}
		
		/*Set old TMPL seprate to use after*/
		$OLD_TMPL = isset(ee()->TMPL) ? ee()->TMPL : NULL;
					
		/*Create new object to handle template parsing*/
		/*ee()->TMPL = new EE_Template();*/
		ee()->remove('TMPL');
		ee()->set('TMPL', new EE_Template());

		/*Check for template*/
		if($msg_data['registration_template'] == 0)
		{
			$msg_body = $msg_data['message_body'];
		}
		else
		{

			if($msg_data['template_id'] == "" || $msg_data['template_id'] == 0)
			{
				$msg_body = $msg_data['message_body'];
			}
			else
			{
				$msg_body = $this->message_body($msg_data['template_id']);
			}

		}

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
		$msg_body = preg_replace( "/{!--[\s\S]*?--}/", "", $msg_body);
		$temp = $msg_body;
		
		/*Fetch member ID*/
		$this->member_id = $msg_data['member_id'];
		ee()->TMPL->parse($msg_body, FALSE, ee()->config->item('site_id'));
		
		$this->member_id = 0;

		$vars['params'] 	= ee()->TMPL->tagparams;
		
		$vars['msg_body'] 	= ee()->TMPL->parse_globals(ee()->TMPL->final_template);

		if(! isset($vars['params']['email:subject']))
		{
			$vars['params']['email:subject'] = $msg_data['subject'];
		}

		if(! isset($vars['params']['email:word_wrap']))
		{
			$vars['params']['email:word_wrap'] = $msg_data['word_wrap'];
		}

		if(! isset($vars['params']['email:mailtype']))
		{
			$vars['params']['email:mailtype'] = $msg_data['mailtype'];
		}

		if(isset($msg_data['reset_url']))
		{
			$vars['params']['email:reset_url'] = $msg_data['reset_url'];
			$msg_data['reset_url'] = rtrim(ee()->config->item('site_url'),'/').'/'.$msg_data['reset_url'];
		}

		foreach ($vars['params'] as $key => $value) 
		{
			$vars['params'][$key] = ee()->TMPL->parse_globals($value);
		}

		$vars['msg_body'] = $this->parse_vars($vars['msg_body'], $msg_data);

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
	}

}