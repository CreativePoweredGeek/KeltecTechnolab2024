<?php
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
* ZealousWeb - Smart Export
*
* @package      SmartExport
* @author       Mufi
* @copyright    Copyright (c) 2016, ZealousWeb.
* @link         http://zealousweb.com/expressionengine/smart-export
* @filesource   ./system/expressionengine/third_party/smart_export/mod.smart_export.php
*
*/

require PATH_THIRD.'smart_export/config.php';
return array(
      'author'      => ZEAL_SE_AUTHOR,
      'author_url'  => ZEAL_SE_AUTHOR_URL,
      'name'        => ZEAL_SE_NAME,
      'description' => 'Export entries in XML or CSV file format with given filters.',
      'version'     => ZEAL_SE_VER,
      'namespace'   => 'ZealousWeb\Addons\SmartExport',
      'docs_url'	=> ZEAL_SE_DOC_URL,
      'settings_exist' => TRUE
);