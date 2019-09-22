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
class ReflectionCallable
{
    public const TYPE_INVOKABLE_OBJECT          = 0;
    public const TYPE_CLOSURE                   = 1;
    public const TYPE_STRING                    = 2;
    public const TYPE_CLASS_METHOD_STRING       = 3;
    public const TYPE_CLASS_METHOD_ARRAY        = 4;
    public const TYPE_CLASS_METHOD_ARRAY_STATIC = 5;

    /**
     * @var \ReflectionFunction|\ReflectionMethod
     */
    private $reflection;

    /**
     * @var callable
     */
    private $value;

    /**
     * @var mixed
     */
    private $type;

    /**
     * @var object|null
     */
    private $object;

    /**
     * Returns the callable type.
     *
     * @param callable $callable The callback value
     *
     * @return mixed
     */
    public static function resolveType(callable $callable)
    {
        if ($callable instanceof \Closure) {
            return self::TYPE_CLOSURE;
        }

        if (is_object($callable)) {
            return self::TYPE_INVOKABLE_OBJECT;
        }

        if (is_array($callable)) {
            if (is_string($callable[0])) {
                return self::TYPE_CLASS_METHOD_ARRAY_STATIC;
            }

            return self::TYPE_CLASS_METHOD_ARRAY;
        }

        if (!is_string($callable)) {
            throw new \LogicException();
        }

        if (false !== mb_strpos($callable, "::")) {
            return self::TYPE_CLASS_METHOD_STRING;
        }

        return self::TYPE_STRING;
    }

    /**
     * Constructor.
     *
     * @param callable $callable The callable value
     */
    public function __construct(callable $callable)
    {
        $this->type  = static::resolveType($callable);
        $this->value = $callable;

        switch ($this->type) {
            case self::TYPE_INVOKABLE_OBJECT:
                try {
                    $this->reflection = new \ReflectionMethod($callable, "__invoke");
                } catch (\ReflectionException $e) {
                    throw new \LogicException($e->getMessage(), $e->getCode(), $e);
                }

                $this->object = $callable;

                break;

            case self::TYPE_CLOSURE:
            case self::TYPE_STRING:
                try {
                    $this->reflection = new \ReflectionFunction($callable);
                } catch (\ReflectionException $e) {
                    throw new \LogicException($e->getMessage(), $e->getCode(), $e);
                }

                break;

            case self::TYPE_CLASS_METHOD_STRING:
                [$class, $method] = explode("::", $callable, 2);

                try {
                    $this->reflection = new \ReflectionMethod($class, $method);
                } catch (\ReflectionException $e) {
                    throw new \LogicException($e->getMessage(), $e->getCode(), $e);
                }

                break;

            case self::TYPE_CLASS_METHOD_ARRAY:
            case self::TYPE_CLASS_METHOD_ARRAY_STATIC:
                try {
                    $this->reflection = new \ReflectionMethod($callable[0], $callable[1]);
                } catch (\ReflectionException $e) {
                    throw new \LogicException($e->getMessage(), $e->getCode(), $e);
                }

                if (self::TYPE_CLASS_METHOD_ARRAY === $this->type) {
                    $this->object = $callable[0];
                }

                break;

            default:
                throw new \LogicException();
        }

        if (
            self::TYPE_CLASS_METHOD_STRING === $this->type
            || self::TYPE_CLASS_METHOD_ARRAY_STATIC === $this->type
        ) {
            $methodString = self::TYPE_CLASS_METHOD_STRING
                ? $this->value
                : "[{$this->value[0]}, {$this->value[1]}]"
            ;

            if (!$this->reflection->isStatic()) {
                trigger_error(
                    "Non-static method {$methodString} should not be called statically.",
                    E_USER_DEPRECATED
                );
            }
        }
    }

    /**
     * Returns the callable type.
     *
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the callable value.
     *
     * @return callable
     */
    public function getValue(): callable
    {
        return $this->value;
    }

    /**
     * Returns the callable value reflection instance.
     *
     * @return \ReflectionFunction|\ReflectionMethod
     */
    public function getReflection(): \ReflectionFunctionAbstract
    {
        return $this->reflection;
    }

    /**
     * Invoke callable value, and returns callable value response.
     *
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function invoke(...$args)
    {
        if (
            self::TYPE_CLASS_METHOD_STRING === $this->type
            || self::TYPE_CLASS_METHOD_ARRAY_STATIC === $this->type
        ) {
            return $this->reflection->invoke(null, ...$args);
        }

        if (
            self::TYPE_INVOKABLE_OBJECT === $this->type
            || self::TYPE_CLASS_METHOD_ARRAY === $this->type
        ) {
            return $this->reflection->invoke($this->object, ...$args);
        }

        return $this->reflection->invoke(...$args);
    }
}
