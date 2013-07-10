<?php 
require_once('Validator.php');

function check_sex(&$sex) {
	return in_array($sex, array('male','female'));
}

$param_rules = array(
	'age'		=>array('number'=> array('type'=>'int', 'min'=>0, 'max'=>130, 'error_msg'=>'年龄必须为整数[0,133]')), 
	'name'		=>array('string'=>array('minLength'=>1, 'maxLenght'=>256, 'error_msg'=>'用户名长度为[1,256]')),
	'email'		=>array('regexp'=>array('pattern' => '/^[a-z]([a-z0-9]*[-_\.]?[a-z0-9]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+[\.][a-z]{2,3}([\.][a-z]{2})?$/i', 'error_msg'=>'邮箱格式不正确')),
	'register_date' => array('date'=> array('pattern'=>'Y-m-d')),
	'sex' 		=>array('udv'=>'check_sex', 'invalid' =>array('allow'=>true, 'default'=>'male')), //if invalid, set a default value
);

function validTest($param_rules) {
	$input = array(
		'age'		=>'20',  //will automatically change to int
		'name'		=>'zhouhongwei',
		'email'		=>'zhou_hongwei@126.com',
		'register_date' => '2013-07-10',
		'sex'		=>'male',
	);
	$params = Validator::filter($param_rules, $input);

	print_r($params);
}

function test_number_check($param_rules) {
	try {
		$input = array(
			'age'		=> 234,  
			'name'		=>'zhouhongwei',
			'email'		=>'zhou_hongwei@126.com',
			'register_date' => '2013-07-10',
			'sex'		=>'male',
		);
		$params = Validator::filter($param_rules, $input);
		print_r($params);
	}
	catch(Exception $ex) {
		echo $ex->getMessage() . "\n";
	}
}


function test_regexp_check($param_rules) {
	try {
		$input = array(
			'age'		=> 23, 
			'name'		=>'zhouhongwei',
			'email'		=>'zhouhongwei126.com',
			'register_date' => '2013-07-10',
			'sex'		=>'male',
		);
		$params = Validator::filter($param_rules, $input);
		print_r($params);
	}
	catch(Exception $ex) {
		echo $ex->getMessage() . "\n";
	}
}


function test_date_check($param_rules) {
	try {
		$input = array(
			'age'		=> 23, 
			'name'		=>'zhouhongwei',
			'email'		=>'zhou_hongwei@126.com',
			'register_date' => '2013-07-10 11',
			'sex'		=>'male',
		);
		$params = Validator::filter($param_rules, $input);
		print_r($params);
	}
	catch(Exception $ex) {
		echo $ex->getMessage() . "\n";
	}
}

function test_udv_check($param_rules) {
	try {
		$input = array(
			'age'		=> 23, 
			'name'		=>'zhouhongwei',
			'email'		=>'zhou_hongwei@126.com',
			'register_date' => '2013-07-10',
			'sex'		=>'males',
		);
		$params = Validator::filter($param_rules, $input);
		print_r($params);
	}
	catch(Exception $ex) {
		echo $ex->getMessage() . "\n";
	}
}


function test_empty_check($param_rules) {
	try {
		$input = array(
		);
		$params = Validator::filter($param_rules, $input);
		print_r($params);
	}
	catch(Exception $ex) {
		echo $ex->getMessage() . "\n";
	}
}


validTest($param_rules);
test_number_check($param_rules);
test_regexp_check($param_rules);
test_date_check($param_rules);
test_udv_check($param_rules);
test_empty_check($param_rules);




