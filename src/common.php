<?php ob_start();ob_implicit_flush(false);require_once('lib/log4jphp/Logger.php');Logger::configure(realpath(dirname(__FILE__).'/../conf/log4php.properties'));require_once('class.Conf.php');require_once('class.Language.php');require_once('class.Message.php');// Convenient shortcuts to Language::$lang, Language::lang($str) and Language::_lang($str)$lang = Language::$lang;function lang($arr) {	return call_user_func_array('Language::lang', func_get_args());}function _lang($arr) {	print call_user_func_array('Language::lang', func_get_args());}