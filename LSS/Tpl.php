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

use \Exception;
use \PHPTAL;
use \tidy;

class Tpl {

	//output handling
	const OUTPUT_DIRECT = true;
	const OUTPUT_RETURN = false;

	//instance for singleton
	static $inst = false;

	//tpl environment
	public $path = 'theme';
	public $uri = '';
	public $body = null;
	public $init = false;
	public $tpl_file_ext = '.xhtml';

	//globals
	protected $constants = array();
	protected $debug = array();
	protected $css = null;
	protected $js = null;
	protected $stub = array();

	//singleton access
	public static function _get(){
		if(self::$inst === false){
			$class = __CLASS__;
			self::$inst = new $class();
		}
		return self::$inst;
	}

	//--------------------------------------------------------
	//Environment Modifiers
	//--------------------------------------------------------
	public function setPath($value){
		$this->path = $value;
		return $this;
	}

	public function setUri($value){
		if(strrpos($value,'/') !== (strlen($value)-1)) $value .= '/';
		$this->uri = $value;
		$this->initTheme();
		return $this;
	}

	//--------------------------------------------------------
	//Javascript and CSS Handlers
	//--------------------------------------------------------
	public function addCss($file,$media='screen'){
		return $this->css .= sprintf(
			 "\t<link type=\"text/css\" rel=\"stylesheet\" href=\"%s\" media=\"%s\" />\n"
			,$file
			,$media
		);
	}

	public function resetCss(){
		$this->css = null;
		return true;
	}

	public function addJs($file){
		return $this->js .= sprintf("\t<script type=\"text/javascript\" src=\"%s\"></script>\n",$file);
	}

	public function resetJs(){
		$this->js = null;
		return true;
	}

	//--------------------------------------------------------
	//Constant Handling
	//--------------------------------------------------------	
	public function set($name,$value=null,$overwrite=true){
		if(is_array($name)){
			$overwrite = $value;
			foreach($name as $key => $val) $this->set($key,$val,$overwrite);
			return true;
		}
		if(isset($this->constants[$name]) && $overwrite === false) return false;
		$this->constants[$name] = $value;
		return true;
	}

	public function get($name){
		if(is_null($name)) return $this->constants;
		if(!isset($this->constants[$name])) return null;
		return $this->constants[$name];
	}

	//--------------------------------------------------------
	//Direct body functions
	//--------------------------------------------------------
	public function add($html){
		return $this->body .= $html;
	}

	public function reset(){
		$this->body = '';
		return $this;
	}

	public function setStub($name,$value=true){
		$this->stub[$name] = ($value)?true:false;
		return $this;
	}

	//--------------------------------------------------------
	//Debug handling
	//--------------------------------------------------------
	public function addDebug($value){
		$bt = debug_backtrace();
		//false or null become nullstring ''
		$info = sprintf('%s() at line %d in %s',$bt[1]['function'],$bt[1]['line'],$bt[1]['file']);
		$this->debug[] = array($bt[1]['function'],$bt[1]['line'],$bt[1]['file'],(($value === false) || (is_null($value))) ? '' : $value);
		unset($bt);
		return $this;
	}

	public function debug(){
		$dbg = array();
		if(count($this->debug)){
			foreach($this->debug as $d){
				$title = sprintf('%s() @ line %d: %s',$d[0],$d[1],$d[2]);
				$dbg[] = array('title'=>$title,'entry'=>$d[3],'handle'=>'dbg_'.md5($title.$d[3]));
			}
		}
		return $dbg;
	}

	//--------------------------------------------------------
	//Script Stats
	//--------------------------------------------------------
	public function stats(){
		$stats = 'Execution: '.number_format((microtime(true) - START),5);
		if(is_callable(array('Db','getQueryCount')))
			$stats .= ' | Queries: '.Db::_get()->getQueryCount();
		$stats .= ' | Memory: '.number_format((memory_get_usage()/1024/1024),2).'MB';
		return $stats;
	}

	//--------------------------------------------------------
	//Output Function
	//	This is the main output handler that calls into PHPTAL
	//	it will render the specified template file and add
	//	the passed tags to the environment
	//	it also sets up the global environment for page parsing
	//	NOTE: there should only be one call to this per
	//		page unless in extreme circumstances
	//	ARGUMENTS
	//		file	the template file to be parsed
	//		tags	array of variables to be added to env
	//		echo	when true the tpl system will output
	//					directly to the browser and exit
	//--------------------------------------------------------
	public function output($file,$tags=array(),$echo=true){
		//if there is anything in the buffer, move it to debug
		if(($content = ob_get_contents()) !== '')
			$this->addDebug('<pre>'.$content.'</pre>');
		//init template handler
		$stub_overrides = $this->stub; //backup before initTheme or requested stubs get stomped
		$this->initTheme();
		//start up template engine
		$tpl = new PHPTAL($this->getTplFile($file));
		//init the tpl (load overrides)
		$this->initTpl($tpl,$file);
		//merge stub defaults with the overrides from earlier
		$tpl->stub = $this->stub;
		//setup env for template engine
		$this->setupEnv($tpl);
		//add tags to context
		foreach($tags as $name => $val){
			//dont add invalid vars
			if(strpos($name,'_') === 0) continue;
			if(strpos($name,' ') !== false) continue;
			$tpl->$name = $val;
		}
		//execute template call
		try {
			$out = $tpl->execute();
		} catch(Exception $e){
			//before we throw this upstream we want to restore
			//	the buffer and get more verbose output
			ob_clean();
			echo $content;
			throw $e;
		}
		//if we dont have the tidy extension lets just output now
		if(!extension_loaded('tidy')){
			//print output to browser / terminal
			if($echo){
				$this->reset();
				ob_end_clean();
				echo $out;
				return true;
			} else {
				$this->add($out);
				return $out;
			}
		//we do have tidy so lets do some cleanup
		} else {
			//cleanup the output
			$tidy = new tidy();
			$tidy->parseString($out,Config::get('theme','tidy'),'utf8');
			$tidy->cleanRepair();
			if($echo){
				ob_end_clean();
				echo $tidy;
				return true;
			} else {
				$this->add($tidy);
				return $tidy;
			}
		}
	}

	//--------------------------------------------------------
	//Output Helpers
	//--------------------------------------------------------
	protected function getTplFile($file,$init=false,$try_default=true){
		//figure out which file we want to find
		if($init)
			$file_ext = '.php';
		else
			$file_ext = $this->tpl_file_ext;
		//check for the override first
		$tpl_file = $this->path.'/'.$file.$file_ext;
		if(file_exists($tpl_file))
			return $tpl_file;
		if(!$try_default)
			throw new Exception('Template file doesnt exist: '.$tpl_file);
		//check the default path
		$tpl_file = dirname($this->path).'/default/'.$file.$file_ext;
		if(file_exists($tpl_file))
			return $tpl_file;
		throw new Exception('Template file doesnt exist: '.$tpl_file);
		return false;
	}

	protected function initTheme(){
		try {
			if($this->init)
				return true;
			include($this->getTplFile('init',true));
			return true;
		} catch(Exception $e){
			return false;
		}
	}

	protected function initTpl($tpl,$file){
		try {
			include($this->getTplFile($file,true));
			return true;
		} catch(Exception $e){
			return false;
		}
	}

	protected function setupEnv($tpl){
		//setup globals
		if(!empty($this->css)) $this->set('css',$this->css);
		if(!empty($this->js)) $this->set('js',$this->js);
		//stats
		$stats = $this->stats();
		if(!empty($stats)) $this->set('stats',$stats);
		unset($stats);
		//add global urls if the lib is loaded
		if(is_callable(array('\LSS\Url','_all')))
			$tpl->url = Url::_all();
		//debug
		$debug = $this->debug();
		if(!empty($debug)){
			$this->set('debug',$debug);
			ob_start();
			var_dump($debug);
			file_put_contents('/tmp/debug',ob_get_clean());
		}
		unset($debug);
		//export globals to templating engine
		$tpl->global = $this->constants;
		return true;
	}

	//--------------------------------------------------------
	//Magic Methods
	//--------------------------------------------------------
	public function __toString(){
		return $this->body;
	}

}
