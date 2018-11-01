<?php

namespace PMVC;

class FakeView extends PlugIn
{
    public $v = [];

    public function set($k, $v = null)
    {
        set($this->v, $k, $v);
    }

    public function get($k, $default = null)
    {
        return get($this->v, $k, $default);
    }

    public function append(array $arr)
    {
        $this->v = array_merge_recursive(
            $this->v,
            $arr
        );
    }

    public function setThemeFolder($v)
    {
    }

    public function setThemePath($v)
    {
    }

    public function process()
    {
        $this['v'] = $this->v;

        return $this['this'];
    }
}
