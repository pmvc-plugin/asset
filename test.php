<?php
PMVC\Load::plug();
PMVC\setPlugInFolder('../');
class AssetTest extends PHPUnit_Framework_TestCase
{
    function testAsset()
    {
        ob_start();
        $plug = 'asset';
        print_r(PMVC\plug($plug));
        $output = ob_get_contents();
        ob_end_clean();
        $this->assertContains($plug,$output);
    }
}
