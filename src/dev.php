<?php
declare(strict_types=1);

function xhprof(callable $callable, bool $dump = false, array $ignored = []): array
{
    if(extension_loaded('xhprof')){
        xhprof_enable(0, [
            'ignored_functions' => [
                    'Composer\Autoload\ClassLoader::findFileWithExtension',
                    'Composer\Autoload\ClassLoader::findFile',
                    'Composer\Autoload\ClassLoader::loadClass',
                    'Composer\Autoload\includeFile',
                    'spl_autoload_call'
                ] + $ignored
        ]);

        $callable();

        $x = xhprof_disable();
        $sort = [];
        $arr = [];
        foreach ($x as $k => $v){
            $sort[$k] = $v['wt'] / $v['ct'];
        }
        asort($sort);
        foreach ($sort as $k => $v){
            if(($total = number_format($x[$k]['wt'] / 1000)) === '0'){
                continue;
            }
            if(($avg = number_format($v / 1000)) === '0'){
                continue;
            }
            $arr[] = [
                '方法名称：' => $k,
                '调用次数：' => $x[$k]['ct'] . ' 次',
                '等待时长：' => $total . ' ms',
                '平均时长：' => $avg . ' ms'
            ];
        }
        if($dump){
            dump($arr);
        }
        return $arr;
    }

    $callable();
    return [];
}