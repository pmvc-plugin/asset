<?php
namespace PMVC\PlugIn\asset;

use PMVC\TestCase;

class AssetTest extends TestCase
{
    private $_plug = 'asset';

    public function pmvc_setup()
    {
        \PMVC\unplug($this->_plug);
    } 

    function testAsset()
    {
        ob_start();
        print_r(\PMVC\plug($this->_plug));
        $output = ob_get_contents();
        ob_end_clean();
        $this->haveString($this->_plug, $output);
    }

    /**
     * @expectedException Exception
     */
    function testWebpackNotExist()
    {
        $fakeThemeFolder = 'fakeThemeFolder';
        $fakeWebpackStateFile = 'fakeWebpackStateFile';
        $view = \PMVC\plug('view', [
            _CLASS => '\PMVC\FakeView',
            'themeFolder' => $fakeThemeFolder,
        ]);
        $view->set('webpackStateFile', $fakeWebpackStateFile);
        $p = \PMVC\plug($this->_plug);
        $this->willThrow(function () use ($p) {
            $p->webpack('test', [], true);
        });
    }

    function testWebpack()
    {
        $fakeThemeFolder = __DIR__ . '/resources/fakeThemeFolder';
        $fakeWebpackStateFile = 'fakeWebpackStateFile.json';
        $view = \PMVC\plug('view', [
            _CLASS => '\PMVC\FakeView',
            'themeFolder' => $fakeThemeFolder,
        ]);
        $view->set('webpackStateFile', $fakeWebpackStateFile);
        $p = \PMVC\plug($this->_plug);
        $publicPath = $p->webpack('foo', [], true);
        $this->assertEquals($publicPath, "http://localhost:3000/assets/main.bundle.js?3f0df6887c483d197d4f");
        $this->assertEquals($p['assetsFolder'], $fakeThemeFolder);
        $this->assertEquals($p['webpackStateFile'], $fakeWebpackStateFile);
    }
}
