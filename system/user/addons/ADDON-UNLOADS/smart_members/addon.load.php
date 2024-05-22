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
* ZealousWeb - Smart members PRO
*
* @package      Smart members PRO
* @author       ZealousWeb
* @copyright    Copyright (c) 2018, ZealousWeb.
* @link         http://zealousweb.com/expression-engine/smart-members
*
*/
;

spl_autoload_register(
    function ($class_name) {
        $phar_path = PATH_THIRD . 'smart_members/smart_members.phar';
        $phar_stream_path = 'phar://' . $phar_path;
        $tmp_phar_path = sys_get_temp_dir() . '/pharextract/smart_members';

        $namespace = 'Zealousweb\SmartMembersPRO';
        $check_namespace = $namespace . '\ZealCore';
        if (substr($class_name, 0, strlen($check_namespace)) !== $check_namespace) {
            return null;
        }

        $class_name = str_replace($namespace, '', $class_name);
        $class_name = trim($class_name, '\\');
        $class_name = str_replace('\\', '/', $class_name);
        if (file_exists($phar_path)) {
            if (in_array('phar', stream_get_wrappers()) && class_exists('Phar', 0)) {
                $phar_stream = true;
                include_once $phar_path;
                $class_file_path = $phar_stream_path;
            } else {
                $phar_stream = false;
                include_once $phar_path;
                $class_file_path = $tmp_phar_path;
            }
            $class_file = $class_file_path . '/' . $class_name . '.php';
            if (file_exists($class_file) && ($phar_stream === true || ($phar_stream === false && stream_resolve_include_path($class_file)) !== false)) {
                $opcache_config = false;
                if (function_exists('opcache_get_configuration')) {
                    $opcache_config = @opcache_get_configuration();
                }
                if ($phar_stream === true && !empty($opcache_config) && !empty($opcache_config['directives']['opcache.validate_permission']) && $opcache_config['directives']['opcache.validate_permission'] === true) {
                    include $class_file . '?nocache=' . microtime(true);
                } else {
                    include $class_file;
                }
            } else {
                throw new \Exception('Class not found.');
            }
        } else {

            throw new \Exception('File not found.');
        }
    }
);
