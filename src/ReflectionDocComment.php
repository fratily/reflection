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
namespace Fratily\Reflection;

/**
 *
 */
class ReflectionDocComment implements \Reflector{

    /**
     * @var DocCommentReader|null
     */
    private $reader;

    /**
     * @var string[]|null
     */
    private $annotations;

    /**
     * Constructor
     *
     * @param   $reflection \ReflectionClass|\ReflectionFunctionAbstract|\ReflectionClassConstant|\ReflectionProperty
     */
    public function __construct($reflection){
        if(!($reflection instanceof \ReflectionClass)
            && !($reflection instanceof \ReflectionFunctionAbstract)
            && !($reflection instanceof \ReflectionClassConstant)
            && !($reflection instanceof \ReflectionProperty)
        ){
            throw new \InvalidArgumentException();
        }

        $comment    = $reflection->getDocComment();

        if($comment !== false){
            $this->reader   = new DocCommentReader($comment);
        }
    }

    /**
     * To string
     *
     * @return  string
     */
    public function __toString(){
        if($this->reader !== null){
            return (string)$this->reader;
        }

        return "";
    }

    /**
     * This method is not supported.
     */
    public static function export(){
        throw new \BadMethodCallException("This method is not supported.");
    }

    /**
     * Returns the summary from the doc comment.
     *
     * @return string|null
     */
    public function getSummary(){
        if($this->reader === null){
            return null;
        }

        return $this->reader->getSummary();
    }

    /**
     * Returns the summary from the doc comment.
     *
     * @return string|null
     */
    public function getDescription(){
        if($this->reader === null){
            return null;
        }

        return $this->reader->getDescription();
    }

    /**
     * Returns the annotations from the doc comment.
     *
     * @return  string[][]|null
     */
    public function getAnnotations(){
        if($this->reader === null){
            return null;
        }

        if($this->annotations === null){
            $this->annotations  = [];

            foreach($this->reader->getAnnotations() as $annotation){
                $key    = "";
                $value  = "";

                if(($pos = mb_strpos($annotation, " ")) === false){
                    $key    = mb_substr($annotation, 1);
                }else{
                    $key    = mb_substr($annotation, 1, $pos - 1);
                    $value  = ltrim(mb_substr($annotation, $pos));
                }

                if(!array_key_exists($key, $this->annotations)){
                    $this->annotations[$key]    = [];
                }

                $this->annotations[$key][]  = $value;
            }
        }

        return $this->annotations;
    }
}