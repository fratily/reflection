<?php
/**
 * FratilyPHP Reflection
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      Kento Oka <kento-oka@kentoka.com>
 * @copyright   (c) Kento Oka
 * @license     MIT
 * @since       1.0.0
 */
namespace Fratily\Reflection;

/**
 * 
 */
class ReflectionCallable implements \Reflector{
    
    const TYPE_ERROR            = 0;
    const TYPE_INVOKABLE_OBJECT = 1;
    const TYPE_CLOSURE          = 2;
    const TYPE_FUNCTION         = 3;
    const TYPE_METHOD_STRING    = 4;
    const TYPE_METHOD_ARRAY     = 5;
    
    /**
     * @var \ReflectionFunction|\ReflectionMethod
     */
    private $reflection;
    
    /**
     * @var callable
     */
    private $value;
    
    /**
     * @var string|null
     */
    private $class;
    
    /**
     * @var int
     */
    private $type;
    
    /**
     * Exports
     * 
     * @return  void
     */
    public static function export(callable $callable){
        echo (string)(new static($callable));
    }
    
    public static function resolveType(callable $callable){
        if($callable instanceof \Closure){
            return self::TYPE_CLOSURE;
        }else if(is_object($callable)){
            return self::TYPE_INVOKABLE_OBJECT;
        }else if(is_string($callable)){
            if(($pos = strpos($callable, "::")) !== false){
                return self::TYPE_METHOD_STRING;
            }
            
            return self::TYPE_FUNCTION;
        }else if(is_array($callable)){
            return self::TYPE_METHOD_ARRAY;
        }
        
        return self::TYPE_ERROR;
    }
    
    /**
     * Constructor
     * 
     * @param   callable    $callable
     */
    public function __construct(callable $callable){
        $this->type     = self::resolveType($callable);
        $this->value    = $callable;
        
        if($this->type === self::TYPE_ERROR){
            throw new \LogicException;
        }
        
        switch($this->type){
            case self::TYPE_INVOKABLE_OBJECT:
                $this->reflection   = new \ReflectionMethod($callable, "__invoke");
                $this->class        = get_class($callable);
                break;
            
            case self::TYPE_CLOSURE:
            case self::TYPE_FUNCTION:
                $this->reflection   = new \ReflectionFunction($callable);
                break;
            
            case self::TYPE_METHOD_STRING:
                $pos        = strpos($callable, "::");
                $tmp        = $callable;
                $callable   = [];
                $callable[] = substr($tmp, 0, $pos);
                $callable[] = substr($tmp, $pos + 2);
                
            case self::TYPE_METHOD_ARRAY:
                $this->class        = !is_string($callable[0]) ? get_class($callable[0]) : $callable[0];
                $this->reflection   = new \ReflectionMethod($this->class, $callable[1]);
                
                if(is_string($callable[0]) && !$this->reflection->isStatic()){
                    throw new \InvalidArgumentException(
                        "Deprecated: non-static method called statically"
                    );
                }
                
                break;
            
            default:
                throw new \LogicException;
        }
    }
    
    /**
     * To string
     * 
     * @return  string
     */
    public function __toString(){
        return (string)$this->reflection;
    }
    
    /**
     * 
     * 
     * @return  int
     */
    public function getType(){
        return $this->type;
    }
    
    /**
     * 
     * 
     * @return  string|null
     */
    public function getClass(){
        return $this->class;
    }
    
    /**
     * 
     * 
     * @return  callable
     */
    public function getValue(){
        return $this->value;
    }
    
    /**
     * 
     * 
     * @return  \ReflectionFunction|\ReflectionMethod
     */
    public function getReflection(){
        return $this->reflection;
    }
    
    public function invoke($object = null, ...$args){
        if($this->reflection instanceof \ReflectionMethod){
            if(!$this->reflection->isStatic()){
                if(!is_a($object, $this->getClass())){
                    throw new \LogicException;
                }
                
                return $this->reflection->invokeArgs($object, $args);
            }
            
            return $this->reflection->invokeArgs(null, $args);
        }
        
        return $this->reflection->invokeArgs($args);
    }
    
    public function invokeArgs($object = null, array $args = []){
        array_unshift($args, $object);
        return call_user_func_array([$this, "invoke"], $args);
    }
    
    public function invokeMapedArgs($object = null, array $params = [], $default = null){
        $args   = [];
        
        foreach($this->reflection->getParameters() as $param){
            $value  = $default;

            if(isset($params[$param->getName()])){
                $value  = $params[$param->getName()];
            }else if($param->isDefaultValueAvailable()){
                $value  = $param->getDefaultValue();
            }

            $args[] = $value;
        }
        
        return $this->invokeArgs($object, $args);
    }
}