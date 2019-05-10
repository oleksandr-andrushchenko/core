<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 10/3/17
 * Time: 4:37 PM
 */
namespace SNOWGIRL_CORE;

use SNOWGIRL_CORE\Entity;

/**
 * Class Tests
 * @package SNOWGIRL_CORE
 */
class Tests
{
    /** @var App */
    protected $app;
    protected $newLine;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->newLine = $this->app->request->isCli() ? PHP_EOL : '<br/>';
    }

    public function run()
    {
        $this->testEntityNormalizers();

        return true;
    }

    protected function testEntityNormalizers()
    {
        $this->output(__FUNCTION__);

        $data = [
            ['input' => 'qwe', 'func' => [Entity::class, 'normalizeText'], 'expected' => 'qwe'],
            ['input' => 'F.O.X', 'func' => [Entity::class, 'normalizeText'], 'expected' => 'F.O.X'],
            ['input' => ' F.O.X', 'func' => [Entity::class, 'normalizeText'], 'expected' => 'F.O.X'],
            ['input' => 'F.O.X ', 'func' => [Entity::class, 'normalizeText'], 'expected' => 'F.O.X'],
            ['input' => ' F.O.X ', 'func' => [Entity::class, 'normalizeText'], 'expected' => 'F.O.X'],
            ['input' => '{}', 'func' => [Entity::class, 'normalizeText'], 'expected' => ''],
            ['input' => '{П.Привет}', 'func' => [Entity::class, 'normalizeText'], 'expected' => 'П.Привет'],
            ['input' => null, 'null' => true, 'func' => [Entity::class, 'normalizeText'], 'expected' => null],
            ['input' => '', 'null' => true, 'func' => [Entity::class, 'normalizeText'], 'expected' => null],
            ['input' => null, 'null' => false, 'func' => [Entity::class, 'normalizeText'], 'expected' => ''],

            ['input' => 'qwe', 'func' => [Entity::class, 'normalizeUri'], 'expected' => 'qwe'],
            ['input' => 'F.O.X', 'func' => [Entity::class, 'normalizeUri'], 'expected' => 'f-o-x'],
            ['input' => 'q  w  e', 'func' => [Entity::class, 'normalizeUri'], 'expected' => 'q-w-e'],
            ['input' => 'привет', 'func' => [Entity::class, 'normalizeUri'], 'expected' => 'privet'],
            ['input' => null, 'null' => true, 'func' => [Entity::class, 'normalizeUri'], 'expected' => null],
            ['input' => '', 'null' => true, 'func' => [Entity::class, 'normalizeUri'], 'expected' => null],
            ['input' => null, 'null' => false, 'func' => [Entity::class, 'normalizeUri'], 'expected' => ''],

            ['input' => 'qwe', 'func' => [Entity::class, 'normalizeHash'], 'expected' => '76d80224611fc919a5d54f0ff9fba446'],
            ['input' => 123, 'func' => [Entity::class, 'normalizeHash'], 'expected' => '202cb962ac59075b964b07152d234b70'],
            ['input' => '123', 'func' => [Entity::class, 'normalizeHash'], 'expected' => '202cb962ac59075b964b07152d234b70'],
            ['input' => ' 123', 'func' => [Entity::class, 'normalizeHash'], 'expected' => '202cb962ac59075b964b07152d234b70'],
            ['input' => '123 ', 'func' => [Entity::class, 'normalizeHash'], 'expected' => '202cb962ac59075b964b07152d234b70'],
            ['input' => ' 123 ', 'func' => [Entity::class, 'normalizeHash'], 'expected' => '202cb962ac59075b964b07152d234b70'],
            ['input' => null, 'null' => true, 'func' => [Entity::class, 'normalizeHash'], 'expected' => null],
            ['input' => '', 'null' => true, 'func' => [Entity::class, 'normalizeHash'], 'expected' => null],
            ['input' => null, 'null' => false, 'func' => [Entity::class, 'normalizeHash'], 'expected' => ''],

            ['input' => ['k1' => 'v1', 'k2' => 'v2'], 'func' => [Entity::class, 'normalizeJson'], 'expected' => '{"k1":"v1","k2":"v2"}'],
            ['input' => ['k1' => 1, 'k2' => 2], 'func' => [Entity::class, 'normalizeJson'], 'expected' => '{"k1":1,"k2":2}'],
            ['input' => null, 'null' => true, 'func' => [Entity::class, 'normalizeJson'], 'expected' => null],
            ['input' => '', 'null' => true, 'func' => [Entity::class, 'normalizeJson'], 'expected' => null],
            ['input' => null, 'null' => false, 'func' => [Entity::class, 'normalizeJson'], 'expected' => ''],

            ['input' => '2018-04-10 14:07:55', 'func' => [Entity::class, 'normalizeTime'], 'expected' => '2018-04-10 14:07:55'],
            ['input' => 1523358475, 'func' => [Entity::class, 'normalizeTime'], 'expected' => '2018-04-10 14:07:55'],
            ['input' => null, 'null' => true, 'func' => [Entity::class, 'normalizeTime'], 'expected' => null],
            ['input' => '', 'null' => true, 'func' => [Entity::class, 'normalizeTime'], 'expected' => null],
            ['input' => null, 'null' => false, 'func' => [Entity::class, 'normalizeTime'], 'expected' => '']
        ];

        $this->runBulk($data, function (array $case) {
            return call_user_func($case['func'], $case['input'], $case['null'] ?? false);
        }, function (array $case) {
            return $case['expected'];
        });
    }

    public function runBulk(array $data, \Closure $res, \Closure $exp)
    {
        foreach ($data as $i => $case) {
            try {
                $res2 = $res($case);
                $exp2 = $exp($case);
                $isOk = $exp2 === $res2;
                $text = $isOk ? 'OK' : 'FAIL';
            } catch (\Exception $ex) {
                $isOk = false;
                $res2 = null;
                $exp2 = null;
                $text = 'EXC: ' . $ex->getMessage();
            }

            $this->output(implode(' ', [
                '#' . str_pad(++$i, 3) . ':',
                str_pad($text, 4),
                $isOk ? '' : $res2,
                $isOk ? '' : $exp2
            ]));
        }
    }

    protected function output($text)
    {
        echo $text;
        echo $this->newLine;
    }
}