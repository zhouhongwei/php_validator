 <?php
 /**
  * http get post request kv like params validate
  * @author zhou_hongwei@126.com
  * @date   2013-07-10
  */
class Validator {
    /**
     * 
     * @author zhouhongwei
     * @param array $rules
     * @param array $request_params
     * @throws ValidateException 
     */
    public static function filter($rules, $request_params) {
        $params = array();
        foreach($rules as $key => $rule) {
            $invalid_config = null;
            try {
                $value = isset($request_params[$key]) ? $request_params[$key] : null;
                if(isset($rule['invalid'])) {
                    $invalid_config = $rule['invalid'];
                    if($value === null) {
                        $params[$key] = isset($invalid_config['default']) ? $invalid_config['default'] : null;
                        continue;
                    }
                    unset($rule['invalid']);
                }
                self::validateField($value, $rule,  $key);
                $params[$key] = $value;
            }
            catch(ValidateException $ex) {
                if($invalid_config) {
                    if($invalid_config['allow']) {
                        $params[$key] = isset($invalid_config['default']) ? $invalid_config['default'] : null;
                    }
                    else {
                        throw new ValidateException($ex->getMessage());
                    }
                }
                else {
                    throw new ValidateException($ex->getMessage());
                }
            }
        }
        return $params;
    }

    /**
     * 校验字段并进行必要到转换
     *
     * @author zhouhongwei
     * @param string $value
     * @param array $rules
     * @param string key
     * @return none
     * @throws ValidateException
     */
    private static function validateField(&$value, $rules,  $key='') {
        foreach($rules as $key=>$rule) {
			try {
				$validator_method =  $key . 'Validate';
				if(method_exists('Validator',$validator_method)) {
					self::$validator_method($rule, $value, $key);
				}
				else {
					throw new ValidateException("校验规则{$key}不存在");
				}
			}
			catch(ValidateException $ex) {
				$msg = isset($rule['error_msg']) ? $rule['error_msg'] : $ex->getMessage();
				throw new ValidateException($msg);
			}
        }
    }

    /**
     * 日期校验
     * 配置范例:  array('date'=>array('pattern'=>'Y-m-d H:i:s', 'min'=>xxx, 'max'=>xxx, 'to_time'=>true))
     *           to_time标志是否把时间转成unix时间戳
     *
     * @param array $rule
     * @param date $value
     * @param string $key
     * @throws ValidateException
     */
    public static function dateValidate($rule, &$value, $key) {
        $pattern = isset($rule['pattern']) ? $rule['pattern'] : 'Y-m-d';
        if (date($pattern, strtotime($value)) !== $value) {
             throw new ValidateException("{$key}的值{$value}不是一个有效的日期");
        }
        if(isset($rule['min'])) {
            if(!self::isInt($rule['min'])) {
                $rule['min'] = strtotime($rule['min']);
            }
            if(strtotime($value) < $rule['min']) {
                throw new ValidateException("{$key}的值{$value}要大于等于" . $rule['min']);
            }
        }

        if(isset($rule['max'])) {
            if(!self::isInt($rule['max'])) {
                $rule['max'] = strtotime($rule['max']);
            }
            if(strtotime($value) > $rule['max']) {
                throw new ValidateException("{$key}的值{$value}要小于等于" . $rule['max']);
            }
        }
        if(isset($rule['to_time']) && $rule['to_time']) {
            $value = strtotime($value);
        }
    }

    /**
     * 字符串验证
     * 配置范例:  array('string'=> array('minLength'=>1, 'maxLength'=>100))
     *
     * @param arrat $rule
     * @param string $value
     * @param string $key
     * @throws ValidateException
     */
    public static function stringValidate($rule , $value, $key) {
        $trim = isset($rule['trim']) ? $rule['trim'] : true;
        if($trim) {
            $value = trim($value);
        }
        if(isset($rule['minLength'])) {
            if (mb_strlen($value, 'UTF-8') < $rule['minLength']) {
                throw new ValidateException("{$key}的值{$value}长度小于" . $rule['minLength']);
            }
        }
        if(isset($rule['maxLength'])) {
            if (mb_strlen($value, 'UTF-8') > $rule['maxLength']) {
                throw new ValidateException("{$key}的值{$value}长度大于" . $rule['maxLength']);
            }
        }
        if(isset($rule['is'])) {
            if($value !== $rule['is']) {
                throw new ValidateException("{$key}的值{$value}的值不等于" . $rule['is']);
            }
        }
        $value = strval($value);
    }

    /**
     * 判断输入是否是整数
     * 
     * @param int $value
     * @return boolean 
     */
    private static function isInt($value) {
         $integer_pattern='/^\s*[+-]?\d+\s*$/';
         if(!preg_match($integer_pattern, $value)) {
             return false;
         }
         return true;
    }

    /**
     * 数字校验
     * 配置范例:  array('number'=> array('min'=>1, 'max'=>100, 'type'=>'int'))
     *
     * @param array $rule
     * @param number $value
     * @param string $key
     * @throws ValidateException 
     */
    public static function numberValidate($rule, &$value, $key) {
        if(!isset($rule['type'])) {
            $rule['type'] = 'int';
        }
        if($rule['type'] === 'int') {
            if(!self::isInt($value)) {
                throw new ValidateException("{$key}的值{$value}不是一个整数");
            }
            $value = intval($value);
        }
        else {
            $number_pattern='/^\s*[-+]?[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?\s*$/';
            if(!preg_match($number_pattern, $value)) {
                throw new ValidateException("{$key}的值{$value}不是一个数字");
            }
            $value = floatval($value);
        }
        $range = array();
        if(isset($rule['min'])) {
            $range['min'] = $rule['min'];
        }
        if(isset($rule['max'])) {
            $range['max'] = $rule['max'];
        }
        self::rangeValidate($range, $value, $key);
    }

    /**
     * 正则校验
     * 配置范例:  array('regexp'=> array('pattern'=>/^\d+$/'))
     *
     * @param mixed $value
     * @param string $regexp
     * @param string $key
     * @throws ValidateException
     */
    public static function regexpValidate($rule , $value,  $key) {
        $trim = isset($rule['trim']) ? $rule['trim'] : true;
        if($trim) {
            $value = trim($value);
        }
        if(!preg_match($rule['pattern'], $value)) {
            throw new ValidateException("{$key}的值{$value} 不匹配正则{$rule['pattern']}");
        } 
    }

    /**
     * 范围验证
     *
     * @param array $range
     * @param mixed $value
     * @throws ValidateException
     */
    public static function rangeValidate($range, $value, $key) {
        if(isset($range['min'])) {
            if($value < $range['min']) {
                throw new ValidateException("{$key}的值{$value}必须大于等于" . $range['min']);
            }
        }

        if(isset($range['max'])) {
            if($value > $range['max']) {
                throw new ValidateException("{$key}的值{$value}必须小于等于" . $range['max']);
            }
        }
    }

    /**
     * 通过callback验证
	 * array('udv'=>$callback)
     *
     * @param mixed  $rule
     * @param mixed $value
     * @param string $key
     * @throws ValidateException
     */
    public static function udvValidate($callback, &$value, $key) {
        if(!is_callable($callback)) {
            throw new ValidateException("{$key}的值{$value}校验功能不可用");
        }

        if(false === call_user_func_array($callback , array(&$value))) {
            throw new ValidateException("{$key}的值{$value}没有通过用户校验");
        }
    }
}

/**
 * kv校验异常类
 * 
 * author: zhouhongwei 
 */
class ValidateException extends Exception {
    
}
