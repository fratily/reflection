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
class DocCommentReader{

    const SUMMARY       = 1;
    const DESCRIPTION   = 2;
    const ANNOTATIONS   = 3;

    /**
     * @var string
     */
    private $summary;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string[]
     */
    private $annotations;

    /**
     * Constructor
     *
     * @param   \ReflectionClass|\ReflectionFunctionAbstract|\ReflectionClassConstant|\ReflectionProperty   $reflection
     */
    public function __construct(string $comment){
        $rows = explode(
            PHP_EOL,
            strtr(
                preg_replace(
                    [
                        "`\A/\*\*+\s*`",
                        "`\s*\*+/\z`"
                    ],
                    "",
                    $comment
                ),
                ["\r\n" => PHP_EOL, "\r" => PHP_EOL, "\n" => PHP_EOL]
            )
        );

        $this->parse(
            array_map(
                function($line){
                    return preg_replace("`\A\*+\s*`", "", trim($line));
                },
                $rows
            )
        );
    }

    /**
     * To string
     *
     * @return  string
     */
    public function __toString(){
        return implode(PHP_EOL, array_merge([
            $this->getSummary(),
            "",
            $this->getDescription(),
            "",

        ], $this->getAnnotations()));
    }

    /**
     * Summaryを取得する
     *
     * @return string
     */
    public function getSummary(){
        return $this->summary ?? "";
    }

    /**
     * Descriptionを取得する
     *
     * @return string
     */
    public function getDescription(){
        return $this->description ?? "";
    }

    /**
     * Annotationのリストを取得する
     *
     * @return string[]
     */
    public function getAnnotations(){
        return $this->annotations ?? [];
    }

    /**
     * コメントをSummary, Description, Annotationsに分ける
     *
     * @param   string[]    $rows
     *
     * @return  void
     */
    private function parse(array $rows){
        $annotation = "";
        $type       = self::SUMMARY;
        $in         = true;

        foreach($rows as $row){
            if(strpos($row, "@") === 0){
                $type   = self::ANNOTATIONS;
                $in     = true;

                if($annotation !== ""){
                    $this->annotations[]    = trim($annotation);
                }

                $annotation = $row;

                continue;
            }

            switch($type){
                case self::SUMMARY:
                    if($row !== ""){
                        if($in){
                            //  空行でなくSummary範囲内なのでこれはSummary
                            $this->summary  = $row;
                            $in             = false;
                        }else{
                            //  空行でなくSummary範囲外なのでこれはDescription
                            $this->description  = $row;
                            $type               = self::DESCRIPTION;
                            $in                 = true;
                        }
                    }else{
                        //  空行なのでSummary範囲外に突入
                        $in = false;
                    }

                    break;
                case self::DESCRIPTION:
                    $this->description  .= PHP_EOL . $row;
                    break;
                case self::ANNOTATIONS:
                    $annotation .= $row !== "" ? PHP_EOL . $row : "";
                    break;
            }
        }

        if("" !== $annotation){
            $this->annotations[]    = trim($annotation);
        }

        $this->description  = rtrim($this->description, PHP_EOL);
    }
}