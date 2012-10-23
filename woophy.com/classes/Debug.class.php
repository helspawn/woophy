<?php
class Debug{
	public $querytimes = array();
	
	public function __construct() {}
	public function benchmark_query($sql, $starttime){
		$time = microtime(true);
		$backtrace = debug_backtrace();
		$benchmark = array(
			'sql' 			=> $sql,
			'starttime' 	=> $starttime,
			'finishtime'	=> $time,
			'querytime'		=> $time-$starttime,
			'caller'		=> $backtrace[1]['file'] . ' ' . $backtrace[1]['line']
		);
		array_push($this->querytimes, $benchmark);
	}
	
	public function show_benchmarks($show_query=FALSE){
		$output = '';
		$totaltime = 0;
		foreach($this->querytimes as $query){
			$output .= '<strong>CALLER:</strong> ' . $query['caller'] . '<br />';
			$output .= '<strong>TIME ELAPSED:</strong> ' . number_format($query['querytime'], 3) . '<br />';		
			if($show_query){
				$output .= '<strong>QUERY:</strong> ' . $query['sql'] . '<br />';
			}
			$output .= '<hr />';
			$totaltime += $query['querytime'];
		}
		$output .= '<strong>TOTAL TIME FOR ALL QUERIES:</strong> ' . number_format($totaltime, 3) . ' seconds';
		return $output;
	}
}
