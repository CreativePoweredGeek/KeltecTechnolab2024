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
* ZealousWeb - Smart Import Export
*
* @package      SmartImportExport
* @author       ZealousWeb
* @copyright    Copyright (c) 2020, ZealousWeb.
* @link         https://www.zealousweb.com/expression-engine/smart-import-export
* @filesource   ./system/expressionengine/third_party/smart_import_export/addon.setup.php
*
*/

require PATH_THIRD.'smart_import_export/config.php';
return array(
      'author'      => ZEAL_SIE_AUTHOR,
      'author_url'  => ZEAL_SIE_AUTHOR_URL,
      'name'        => ZEAL_SIE_NAME,
      'description' => 'Export and Import Channel entries in XML or CSV file format with given filters.',
      'version'     => ZEAL_SIE_VER,
      'namespace'   => 'ZealousWeb\Addons\SmartImportExport',
      'docs_url'	=> ZEAL_SIE_DOC_URL,
      'settings_exist' => TRUE
);