<?php
namespace PMVC\PlugIn\asset;
use PMVC as p;
use PMVC\Event;
${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\asset';

class asset extends p\PlugIn
{
    private $css=array();
    private $js=array();
    private $isEcho=array();
    private $server;

    public function init()
    {
        if (ob_get_length() === false) {
            ob_start();
        }
        \PMVC\callPlugin(
            'dispatcher',
            'attach',
            [ 
                $this,
                Event\B4_PROCESS_VIEW,
            ]
        );
        \PMVC\callPlugin(
            'dispatcher',
            'attach',
            [ 
                $this,
                Event\FINISH,
            ]
        );
    }

    public function flush($subject)
    {
        $subject->detach($this);
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    public function onB4ProcessView($subject)
    {
        $this->flush($subject);
    }

    public function onFinish($subject)
    {
        $this->flush($subject);
    }

    public function parseFile($v)
    {
        if (!isset($v['url'])) {
            $v = array(
                'url'=>$v
            );
        }
        if (p\exists('url', 'plugin')) {
            $v['url'] = p\plug('url')->toHttp($v['url']);
        }
        return $v;
    }

    public function importJs($file, $k=null)
    {
        if (is_null($k)) {
            $k = 'all';
        }
        $file = $this->parseFile($file);
        $this->js[$k][$file['url']] = array(
            'type'=>'file'
            ,'v'=>$file
        );
    }

    public function importCss($v, $k=null)
    {
        if (is_null($k)) {
            $k = 'all';
        }
        $this->css[$k][] = array(
            'type'=>'file'
            ,'v'=>$this->parseFile($v)
        );
    }

    public function js($str, $k=null)
    {
        if (is_null($k)) {
            $k = 'all';
        }
        if (strlen($str)) {
            $this->js[$k][]=array(
                'type'=>'string'
                ,'v'=>$str
            );
        }
    }

    public function css($str, $k=null)
    {
        if (is_null($k)) {
            $k = 'all';
        }
        if (strlen($str)) {
            $this->css[$k][]=array(
                'type'=>'string'
                ,'v'=>$str
            );
        }
    }

    public function echoJs($event=null)
    {
        if (is_null($event)) {
            $event='all';
        }
        if (is_array($this->js[$event])) {
            foreach ($this->js[$event] as $k=>$v) {
                switch ($v['type']) {
                    case 'file':
                        if (isset($this->isEcho[$k])) {
                            continue;
                        }
                        $this->isEcho[$k] = true;
                        echo '<script language="javascript" src="'.$v['v']['url'].'"></script>';
                        break;
                    case 'string':
                        echo '<script language="javascript">'.$v['v'].'</script>';
                        break;
                }
                echo "\n";
            }
            unset($this->js[$event]);
        }
    }
    
    public function echoCss($event=null)
    {
        if (is_null($event)) {
            $event='all';
        }
        if (isset($this->css[$event])) {
            foreach ($this->css[$event] as $k=>$v) {
                switch ($v['type']) {
                    case 'file':
                        if (isset($this->isEcho[$v['v']['url']])) {
                            continue;
                        }
                        $this->isEcho[$v['v']['url']] = true;
                        $v['v']['href'] = $v['v']['url'];
                        unset($v['v']['url']);
                        $p=array();
                        foreach ($v['v'] as $i=>$j) {
                            $p[$i]=$i.'="'.$j.'"';
                        }
                        echo '<link rel="stylesheet" type="text/css" '.join(' ', $p).' />';
                        break;
                    case'string':
                        echo '<style type="text/css">'.$v['v'].'</style>';
                        break;
                }
                echo "\n";
            }
            unset($this->css[$event]);
        }
    }

    public function img($url, $type=null)
    {
        if (preg_match('/^http/i', $url)) {
            return;
        }
        $m = _PMVC::getAppName().'/';
        if (is_null($type)) {
            if (is_file(ROOT_MODULES.$m.'images/'.$url)) {
                return ROOT_URL_MODULES.$m.'images/'.$url;
            } elseif (is_file(ROOT_IMAGES.$url)) {
                return ROOT_URL_IMAGES.$url;
            }
        }
        switch ($type) {
            case 'r':
                return ROOT_URL_IMAGES.$url;
            case 't':
                $tpl='';
                if (strlen(_PMVC::getOption(_TEMPLATE_DIR))) {
                    $tpl = _PMVC::getOption(_TEMPLATE_DIR).'/';
                }
                if (is_file(ROOT_THEMES.$tpl.'images/'.$url)) {
                    return ROOT_URL_THEMES.$tpl.'images/'.$url;
                } else {
                    return ROOT_URL_THEMES.'images/'.$url;
                }
                
            case 'm':
                return ROOT_URL_MODULES.$m.'images/'.$url;
        }
    }
} //end class;
