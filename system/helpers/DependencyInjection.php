<?php


namespace system\helpers;


use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class DependencyInjection
{
    /**
     * 根据类方法或匿名函数，获取依赖注入后的参数列表
     * @param $class
     * @param null $function
     * @param array $other_params
     * @return array
     * @throws \ReflectionException
     */
    public static function getParams($class, $function = null, array $other_params = []): array
    {
        if (is_callable($class) && $function === null) {
            $ref_method = new ReflectionFunction($class);
        } else {
            if (!method_exists($class, $function)) return $other_params;
            $ref_method = new ReflectionMethod($class, $function);
        }
        $func_params = $ref_method->getParameters();
        foreach ($func_params as $key => $func_param) {
            $param_class = $func_param->getClass();
            if ($param_class->name == $class && $function == '__construct') { //死循环
                throw new \Exception("构造函数依赖注入不能注入类本身，class:{$class}，function:{$function}, param_index:{$key}");
            }
            $instance = self::getInjectInstance($param_class, $other_params);
            if (!is_null($instance)) {
                array_splice($other_params, $key, 0, [$instance]);
            } elseif (!isset($other_params[$key]) && $func_param->isDefaultValueAvailable()) {
                array_splice($other_params, $key, 0, [$func_param->getDefaultValue()]);
            }
        }
        return $other_params;
    }

    /**
     * 检查是否已存在该类的实例
     * @param $class
     * @param $params
     * @return bool
     */
    private static function haveInjectInstance($class, $params)
    {
        return !empty(array_filter($params, function ($value) use ($class) {
            return $value instanceof $class;
        }));
    }

    /**
     * 获取注入的实例
     * @param ReflectionClass|null $class
     * @param array $params
     * @return mixed
     */
    private static function getInjectInstance($class, array $params)
    {
        if ($class && !self::haveInjectInstance($class->name, $params)) {
            return app()->singleton($class->name, $class->name);
        }
    }
}