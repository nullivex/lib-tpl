<?php
/**
 *  OpenLSS - Lighter Smarter Simpler
 *
 *	This file is part of OpenLSS.
 *
 *	OpenLSS is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU Lesser General Public License as
 *	published by the Free Software Foundation, either version 3 of
 *	the License, or (at your option) any later version.
 *
 *	OpenLSS is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU Lesser General Public License for more details.
 *
 *	You should have received a copy of the 
 *	GNU Lesser General Public License along with OpenLSS.
 *	If not, see <http://www.gnu.org/licenses/>.
 */
namespace LSS;

ld('/func/mda_glob','tpl');

//load tpl
$theme = (Config::get('theme','name') ? Config::get('theme','name') : 'default');
Tpl::_get()->setPath(ROOT_GROUP.'/theme/'.$theme);
Tpl::_get()->setUri('/theme/'.$theme.'/');
Tpl::_get()->set(array(
	 'lss_version'		=>	LSS_VERSION
	,'version'			=>	VERSION
	,'site_name'		=>	Config::get('site_name')
	,'site_title'		=>	Config::get('site_name')
	,'uri'				=>	Config::get('url','uri')
	,'url'				=>	Config::get('url','url')
	,'theme_path'		=>	Tpl::_get()->uri
	,'copyright'		=>	'© '.date('Y').' '.Config::get('site_name')
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
