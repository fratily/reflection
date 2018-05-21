<?php
/**
 * FratilyPHP Reflection
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.
 * Redistributions of files must retain the above copyright notice.
 *
 * @author      Kento Oka <kento.oka@kentoka.com>
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
            $reflection = $this->getClass($class)->getMethod($method);

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
     * @param   string  $class
     *
     * @return  string[]
     */
    public function getTraits(string $class){
        if(!class_exists($class)){
            throw new \InvalidArgumentException();
        }

        $key    = $class;

        if(!array_key_exists($key, $this->traits)){
            $this->traits[$key] = [];
            do{
                $this->traits[$key]   = array_merge(
                    $this->traits[$key],
                    class_uses($class)
                );
            }while($class = get_parent_class($class));

            $traitsToSearch = $this->traits[$key];

            while(!empty($traitsToSearch)){
                $newTraits          = class_uses(array_pop($traitsToSearch));
                $this->traits[$key] += $newTraits;
                $traitsToSearch     += $newTraits;
            }

            foreach ($this->traits[$key] as $trait) {
                $this->traits[$key] += class_uses($trait);
            }

            $this->traits[$key] = array_unique($this->traits[$key]);
        }

        return $this->traits[$key];
    }
}
