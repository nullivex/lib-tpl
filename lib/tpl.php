<?php

require_once(ROOT.'/usr/phptal/PHPTAL.php');

class Tpl {

	const OUTPUT_DIRECT = true;

	static $inst = false;

	public $path = 'theme';
	public $uri = '';
	public $body = null;
	public $init = false;

	protected $constants = array();
	protected $debug = array();
	protected $css = null;
	protected $js = null;

	public static function _get(){
		if(self::$inst === false){
			$class = __CLASS__;
			self::$inst = new $class();
		}
		return self::$inst;
	}

	public function addCss($file,$media='screen'){
		return $this->css .= sprintf(
			 "\t<link type=\"text/css\" rel=\"stylesheet\" href=\"%s\" media=\"%s\" />\n"
			,$file
			,$media
		);
	}

	public function addJs($file){
		return $this->js .= sprintf("\t<script type=\"text/javascript\" src=\"%s\"></script>\n",$file);
	}

	public function setPath($value){
		$this->path = $value;
		return $this;
	}
	
	public function setUri($value){
		$this->uri = $value;
		return $this;
	}
	
	public function set($name,$value,$overwrite=true){
		$this->setConstant($name,$value,$overwrite);
	}

	public function setConstant($name,$value,$overwrite=true){
		if(isset($this->constants[$name]) && $overwrite === false) return false;
		$this->constants[$name] = $value;
		return true;
	}

	public function setConstants($constants=array(),$overwrite=true){
		if(!is_array($constants)) return $this;
		foreach($constants AS $name => $value) $this->setConstant($name,$value,$overwrite);
		return $this;
	}

	public function addDebug($value){
		$bt = debug_backtrace();
		//false or null become nullstring ''
		$info = sprintf('%s() at line %d in %s',$bt[1]['function'],$bt[1]['line'],$bt[1]['file']);
		$this->debug[] = array($bt[1]['function'],$bt[1]['line'],$bt[1]['file'],(($value === false) || (is_null($value))) ? '' : $value);
		unset($bt);
		return $this;
	}

	public function get($name){
		if(is_null($name)) return $this->constants;
		if(!isset($this->constants[$name])) return null;
		return $this->constants[$name];
	}

	public function add($html){
		return $this->body .= $html;
	}

	public function reset(){
		$this->body = '';
		return $this;
	}

	public function stats(){
		$stats = 'Execution: '.number_format((microtime(true) - START),5);
		if(is_callable(array('Db','getQueryCount')))
			$stats .= ' | Queries: '.Db::_get()->getQueryCount();
		$stats .= ' | Memory: '.number_format((memory_get_usage()/1024/1024),2).'MB';
		return $stats;
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

	public function output($file,$tags=array(),$echo=false){
		//if there is anything in the buffer, move it to debug
		if(($content = ob_get_contents()) !== '')
			$this->addDebug($content);
		//init based on the theme (only once)
		if(file_exists($this->path.'/init.php') && !$this->init){
			include($this->path.'/init.php');
			$this->init = true;
		}
		//init template handler
		$tpl = new PHPTAL($this->path.'/'.$file);
		//setup globals
		if(!empty($this->css)) $this->setConstant('css',$this->css);
		if(!empty($this->js)) $this->setConstant('js',$this->js);
		//stats
		$stats = $this->stats();
		if(!empty($stats)) $this->setConstant('stats',$stats);
		unset($stats);
		//debug
		$debug = $this->debug();
		if(!empty($debug)) $this->setConstant('debug',$debug);
		unset($debug);
		//export globals to templating engine
		$tpl->global = $this->constants;
		//add global urls if the lib is loaded
		if(is_callable(array('Url','_all')))
			$tpl->urls = Url::_all();
		//add tags to context
		foreach($tags as $name => $val){
			//dont add invalid vars
			if(strpos($name,'_') === 0) continue;
			if(strpos($name,' ') !== false) continue;
			$tpl->$name = $val;
		}
		//execute template call
		$out = $tpl->execute();
		//if we have the tidy extension lets clean up the output
		if(!extension_loaded('tidy')) return $out;
		//cleanup the output
		$tidy = new tidy();
		$tidy->parseString($out,Config::get('theme','tidy'),'utf8');
		$tidy->cleanRepair();
		if($echo){
			ob_end_clean();
			echo $tidy;
			return true;
		} else return $tidy;
	}

	public function __toString(){
		return $this->output();
	}

}


