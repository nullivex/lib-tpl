<?php
lib('tpl');

//load tpl
$theme = (Config::get('theme','name') ? Config::get('theme','name') : 'default');
Tpl::_get()->setPath(ROOT_GROUP.'/theme/'.$theme);
Tpl::_get()->setUri('/theme/'.$theme);
Tpl::_get()->set(array(
	 'lss_version'		=>	LSS_VERSION
	,'version'			=>	VERSION
	,'site_name'		=>	Config::get('info','site_name')
	,'site_title'		=>	Config::get('info','site_name')
	,'uri'				=>	Config::get('url','uri')
	,'url'				=>	Config::get('url','url')
	,'theme_path'		=>	Tpl::_get()->path
	,'copyright'		=>	'© '.date('Y').' '.Config::get('info','site_name')
));

//set delayed alerts
if(session('delayed_alert')){
	$alert = Tpl::_get()->get('alert');
	if(!is_array($alert)) $alert = array();
	$alert = array_merge($alert,session('delayed_alert'));
	Tpl::_get()->set('alert',$alert);
	session('delayed_alert','');
}

//cleanup
unset($theme);
