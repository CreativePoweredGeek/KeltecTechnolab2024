<?php namespace Zenbu\librairies\platform\ee;

use Zenbu\librairies\platform\ee\Url;
use Zenbu\librairies\platform\ee\Request;
use Zenbu\librairies\platform\ee\View;
use EllisLab\ExpressionEngine\Library;

class Pagination
{
	/**
	* function _pagination_config
	* Creates pagination for entry listing
	* @return	string	pagination HTML
	*/
	static public function getPagination($total_rows, $limit)
	{
		// Leave if you're not in the CP. It can happen.
		if(REQ != 'CP')
		{
			return array();
		}

		// Pass the relevant data to the paginate class
		if(version_compare(APP_VER, '3.0.0', '>='))
		{
			//    ----------------------------------------
			//    We want to take control over the pagination rendering
			//    to add extra functionality on top of the default EE pagination
			//    template. Instead of the CP/Pagination service, we create our own
			//    service with our own View instance. The View instance must point to a
			//    PHP template file. In our case we just want the JSON of the parsed
			//    pagination data.
			//    ----------------------------------------

			$override_view = ee('View')->make('zenbu:main/pagination');
			$paginationService = new Library\CP\Pagination($total_rows, $override_view);

			//    ----------------------------------------
			//    We can run pagination as a normal
			//    CP\Pagination service instance.
			//    ----------------------------------------

			$total_page_links = ($total_rows / $limit) > 10 ? 10 : ceil($total_rows / $limit);
			$total_page_links = $total_page_links < 1 ? 1 : $total_page_links;

			$pagination = $limit > 0 ? $paginationService
							->perPage($limit)
							->queryStringVariable('page')
							->displayPageLinks($total_page_links)
							->currentPage(Request::param('page', 1))
							->render(Url::zenbuUrl()) : json_encode([]);

			//    ----------------------------------------
			//    We have the JSON string of pagination data.
			//    Let's open it up and feed it in our own Twig template.
			//    ----------------------------------------

			// $pagination = View::render('main/pagination.twig', ['pagination' => json_decode($pagination, true)]);

			return json_decode($pagination);
		}
		else
		{
			$config = array(
				'base_url'             => Url::zenbuUrl(),
				'total_rows'           => $total_rows,
				'per_page'             => $limit,
				'page_query_string'    => TRUE,
				'query_string_segment' => 'perpage',
				'full_tag_open'        => '<span id="paginationLinks">',
				'full_tag_close'       => '</span>',
				'prev_link'            => '<img src="'.ee()->cp->cp_theme_url.'images/pagination_prev_button.gif" width="13" height="13" alt="<" />',
				'next_link'            => '<img src="'.ee()->cp->cp_theme_url.'images/pagination_next_button.gif" width="13" height="13" alt=">" />',
				'first_link'           => '<img src="'.ee()->cp->cp_theme_url.'images/pagination_first_button.gif" width="13" height="13" alt="< <" />',
				'last_link'            => '<img src="'.ee()->cp->cp_theme_url.'images/pagination_last_button.gif" width="13" height="13" alt="> >" />',
				'anchor_class'         => 'class="pagination"',
				);

			// Set up pagination
			ee()->load->library('pagination');

			ee()->pagination->initialize($config);

			return ee()->pagination->create_links();
		}

	} // END function _pagination_config
}