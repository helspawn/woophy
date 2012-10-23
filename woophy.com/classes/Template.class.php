<?php
class Template {
    private $tpl;
	public function __construct($tpl_file=''){
		if(file_exists(TEMPLATE_PATH. $tpl_file)){

			$this->tpl = file_get_contents(TEMPLATE_PATH.$tpl_file, FILE_TEXT);
		}else throw new Exception('Error:Template file ' . TEMPLATE_PATH . $tpl_file . ' not found.');
    }
	public function parse($tags=array()){
		$output = $this->tpl;
		if(isset($output)){
			foreach($tags as $tag=>$data){ 
				$output = str_replace('{'.$tag.'}', $data, $output);
			}
		}
		return $output;
    }
}
?>