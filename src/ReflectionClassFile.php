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

use PhpParser\{
    ParserFactory,
    Parser
};

/**
 *
 */
class ReflectionClassFile implements \Reflector{

    /**
     * @var Parser
     */
    private static $parser;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string[]
     */
    private $uses;

    /**
     * @var string
     */
    private $class;

    /**
     * Constructor
     *
     * @param   string|\SplFileInfo $file
     */
    public function __construct($file){
        if($file instanceof \SplFileInfo){
            $this->path = $file->getPath();
        }else if(is_string($file)){
            if(!is_file($file)){
                throw new \InvalidArgumentException();
            }

            $this->path = realpath($file);
        }else{
            throw new \InvalidArgumentException();
        }

        $this->namespace    = "";
        $this->uses         = [];

        $this->parse();

        if($this->class === null){
            throw new \InvalidArgumentException();
        }
    }

    /**
     * To string
     *
     * @return  string
     */
    public function __toString(){
        return "";
    }

    /**
     * This method is not supported.
     */
    public static function export(){
        throw new \BadMethodCallException("This method is not supported.");
    }

    /**
     * ファイルの解析を行う
     *
     * @todo    クラスファイルにネームスペース定義がない場合の扱い
     * @todo    スローする例外
     */
    private function parse(){
        if(self::$parser === null){
            self::$parser   = (new ParserFactory())
                ->create(\PhpParser\ParserFactory::ONLY_PHP7);
        }

        $stmt   = self::$parser->parse(file_get_contents($this->path));

        if(!is_array($stmt)){
            throw new \LogicException;
        }

        $stmt   = array_filter($stmt, function($v){
            return $v instanceof \PhpParser\Node\Stmt\Namespace_;
        });

        if(empty($stmt)){
            throw new \LogicException;
        }

        $this->namespace    = implode("\\", $stmt[0]->name->parts);

        foreach($stmt[0]->stmts as $sub_stmt){
            if($sub_stmt instanceof \PhpParser\Node\Stmt\Use_
                || $sub_stmt instanceof \PhpParser\Node\Stmt\GroupUse
            ){
                $prefix = "";

                if($sub_stmt instanceof \PhpParser\Node\Stmt\GroupUse){
                    $prefix = $sub_stmt->prefix;
                }

                foreach($sub_stmt->uses as $use){
                    $name   = implode("\\", $use->name->parts);
                    $alias  = $use->alias;

                    if($prefix !== ""){
                        $name   = "{$prefix}\\{$name}";
                    }

                    $this->uses[$name]  = $alias;
                }
            }else if($sub_stmt instanceof \PhpParser\Node\Stmt\Class_){
                $this->class    = $sub_stmt->name;
            }
        }
    }

    /**
     * ネームスペース名を返す
     *
     * @return  string
     */
    public function getNamespace(){
        return $this->namespace;
    }

    /**
     * useで読み込んでいるクラスの別名のリストを返す
     *
     * 返り値はクラスもしくはネームスペースの絶対パスをキーとした
     * エイリアスの連想配列
     *
     * @return  string[]
     */
    public function getUses(){
        return $this->uses;
    }

    /**
     * クラス名を返す
     *
     * @return  string
     */
    public function getClassName(){
        return $this->class;
    }

    /**
     * このクラスファイルが持つクラスのReflectionClassインスタンスを返す
     *
     * @return  \ReflectionClass
     */
    public function getClass(){
        return new \ReflectionClass($this->namespace . "\\" . $this->class);
    }
}