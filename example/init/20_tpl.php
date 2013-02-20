<?php
lib('tpl');

//load tpl
Tpl::_get()->setPath(Config::get('tpl','path'));
Tpl::_get()->setThemePath(Config::get('tpl','theme_path'));
Tpl::_get()->initConstants();
Tpl::_get()->setConstant('lss_version',LSS_VERSION);
Tpl::_get()->setConstant('version',VERSION);

//title stuff
define("SITE_TITLE",' | '.Config::get('info','site_name'));
Tpl::_get()->setConstant('site_title',Config::get('info','site_name'));
