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
namespace Fratily\Reflection\Reflector;

/**
 *
 */
class ClassReflector{

    /**
     * @var \ReflectionClass[]
     */
    private $classes    = [];

    /**
     * @var \ReflectionProperty[]
     */
    private $props      = [];

    /**
     * @var \ReflectionParameter[][][]
     */
    private $params     = [];

    /**
     * @var string[][]
     */
    private $traits     = [];

    /**
     * クラスのリフレクションを取得する
     *
     * @param   string  $class
     *
     * @return  \ReflectionClass
     */
    public function getClass(string $class){
        if(!class_exists($class)){
            throw new \InvalidArgumentException();
        }

        if(!array_key_exists($class, $this->classes)){
            $this->classes[$class]  = new \ReflectionClass($class);
        }

        return $this->classes[$class];
    }

    /**
     * クラスのプロパティリストを取得する
     *
     * @param   string  $class
     *
     * @return  \ReflectionProperty[]
     */
    public function getProperties(string $class){
        if(!class_exists($class)){
            throw new \InvalidArgumentException();
        }

        if(!array_key_exists($class, $this->props)){
            $this->props[$class]    = $this->getClass($class)->getProperties();
        }

        return $this->props[$class];
    }

    /**
     * クラスメソッドのパラメータリストを取得する
     *
     * @param   string  $class
     * @param   string  $method
     *
     * @return  \ReflectionParameter[]
     */
    public function getParameters(string $class, string $method = null){
        if(!class_exists($class)){
            throw new \InvalidArgumentException();
        }

        if($method !== null && !method_exists($class, $method)){
            throw new \InvalidArgumentException();
        }

        if(!array_key_exists($class, $this->params)){
            $this->params[$class]   = [];
        }

        $method = $method === null ? "__construct" : $method;

        if(!array_key_exists($method, $this->params[$class])){
            if($method === "__construct" && !method_exists($class, $method)){
                $reflection = null;
            }else{
                $reflection = $this->getClass($class)->getMethod($method);
            }

            $this->params[$class][$method]  = $reflection === null
                ? []
                : $reflection->getParameters()
            ;
        }

        return $this->params[$class][$method];
    }

    /**
     * トレイトのリストを取得する
     *
     * 指定したクラスがuseしているトレイトだけを取得するので、
     * 継承しているクラスについても調べるのであれば
     * get_parent_class()と組み合わせて調べる
     *
     * @param   string  $class
     *
     * @return  string[]
     */
    public function getTraits(string $class){
        if(!class_exists($class)){
            throw new \InvalidArgumentException();
        }

        if(!array_key_exists($class, $this->traits)){
            $traits     = array_map(
                function($v){
                    return false;
                },
                array_flip(class_uses($class))
            );

            do{
                $continue   = false;
                $new        = [];

                foreach($traits as $trait => $flag){
                    if($flag === false){
                        $traits[$trait] = true;

                        foreach(class_uses($trait) as $use){
                            if(!array_key_exists($use, $traits)){
                                $continue   = true;
                                $new[$use]  = false;
                            }
                        }
                    }
                }

                $traits += $new;
            }while($continue === true);

            $this->traits[$class]   = array_keys($traits);
        }

        return $this->traits[$class];
    }

    /**
     * 指定したクラスが実装しているインターフェースのリストを取得する
     *
     * 指定したクラスが継承しているクラスが実装しているインターフェースは
     * 無視されます。
     *
     * @param   string  $class
     *  クラス名
     *
     * @return  string[]
     */
    public function getImplements(string $class){
        if(!class_exists($class)){
            throw new \InvalidArgumentException();
        }

        if(false === ($parent = get_parent_class($class))){
            return array_values(array_unique(class_implements($class)));
        }

        return array_values(
            array_unique(
                array_diff(
                    class_implements($class),
                    class_implements($parent)
                )
            )
        );
    }
}
