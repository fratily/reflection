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

    /**
     * @var string[]
     */
    private $docRows;

    /**
     * Docコメントを解析しやすいように整形する
     *
     * @param   string  $docComment
     *
     * @return  string[]
     */
    private static function normalizeDocComment(string $docComment){
        $docComment = preg_replace([
            "`\A/\*\*+\s*`",
            "`\s*\*+/\z`"
        ], "", $docComment);

        $rows   = array_map(function($line){
            return preg_replace("`\A\*+\s*`", "", trim($line));
        }, explode(PHP_EOL, strtr($docComment, [
            "\r\n"  => PHP_EOL,
            "\r"    => PHP_EOL,
            "\n"    => PHP_EOL,
        ])));

        return $rows;
    }

    /**
     * Constructor
     *
     * @param   \ReflectionClass|\ReflectionFunctionAbstract|\ReflectionClassConstant|\ReflectionProperty   $reflection
     */
    public function __construct($reflection){
        if(is_object($reflection)
            && (
                $reflection instanceof \ReflectionClass
                || $reflection instanceof \ReflectionFunctionAbstract
                || $reflection instanceof \ReflectionClassConstant
                || $reflection instanceof \ReflectionProperty
            )
        ){
            $this->docRows  = self::normalizeDocComment($reflection->getDocComment());

            return;
        }

        throw new \InvalidArgumentException();
    }

    /**
     * To string
     *
     * @return  string
     */
    public function __toString(){
        return implode(PHP_EOL, $this->docRows);
    }
}