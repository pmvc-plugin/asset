<?php
namespace PMVC\PlugIn\asset;
use PHPUnit_Framework_TestCase;

class AssetTest extends PHPUnit_Framework_TestCase
{
    private $_plug = 'asset';

    function testAsset()
    {
        ob_start();
        print_r(\PMVC\plug($this->_plug));
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertContains($this->_plug, $output);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    function testWebPack()
    {
      $fakeThemeFolder = 'fakeThemeFolder';
      $fakeWebpackStateFile = 'fakeWebpackStateFile';
      $view = \PMVC\plug(
          'view', [
            _CLASS => '\PMVC\FakeView',
            'themeFolder' => $fakeThemeFolder
          ]
      );
      $view->set('webpackStateFile', $fakeWebpackStateFile);
      $p = \PMVC\plug($this->_plug);
      $p->webpack('test', [], true);
      $this->assertEquals($p['assetsFolder'], $fakeThemeFolder);
      $this->assertEquals($p['webpackStateFile'], $fakeWebpackStateFile);
    }
}
