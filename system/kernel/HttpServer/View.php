<?php


namespace system\kernel\HttpServer;

use Xiaoler\Blade\FileViewFinder;
use Xiaoler\Blade\Factory;
use Xiaoler\Blade\Compilers\BladeCompiler;
use Xiaoler\Blade\Engines\CompilerEngine;
use Xiaoler\Blade\Filesystem;
use Xiaoler\Blade\Engines\EngineResolver;

use system\helpers\Singleton;

class View
{
    use Singleton;

    protected Factory $factory;

    private function __construct()
    {
        $file = new Filesystem;
        $compiler = new BladeCompiler($file, VIEW_CACHE_PATH);
        $resolver = new EngineResolver;
        $resolver->register('blade', function () use ($compiler) {
            return new CompilerEngine($compiler);
        });
        $this->factory = new Factory($resolver, new FileViewFinder($file, [VIEW_PATH]));
    }

    public function make($view, $data = [])
    {
        return $this->factory->make($view, $data);
    }
}