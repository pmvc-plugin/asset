<?php
namespace PMVC\PlugIn\asset;
use PMVC as p;
use PMVC\Event;
${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\asset';

class asset extends p\PlugIn
{
    private $css=[];
    private $js=[];
    private $_push=[];
    private $isEcho=[];
    const DEF='default';

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
        flush();
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
            $v = [ 
                'url'=>$v
            ];
        }
        if (p\exists('url', 'plugin')) {
            $v['url'] = p\plug('url')->toHttp($v['url'], false);
        }
        return $v;
    }

    public function push($url, $type)
    {
        $this->_push[$url] = $type;
    }

    public function getPushHeaders()
    {
        if (empty($this->_push)) {
            return [];
        }
        $preloads = [];
        foreach ($this->_push as $url=>$type) {
            $preloads[] = '<'.$url.'>; rel=preload; as='.$type;
        }
        return [
            'Link: '.join(', ', $preloads) 
        ];
    }

    public function importJs($file, $k=null)
    {
        if (is_null($k)) {
            $k = self::DEF;
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
            $k = self::DEF;
        }
        $this->css[$k][] = array(
            'type'=>'file'
            ,'v'=>$this->parseFile($v)
        );
    }

    public function js($str, $k=null)
    {
        if (is_null($k)) {
            $k = self::DEF;
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
            $k = self::DEF;
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
            $event = self::DEF;
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
            }
            $this->js[$event] = null;
            unset($this->js[$event]);
        }
    }
    
    public function echoCss($event=null)
    {
        if (is_null($event)) {
            $event = self::DEF;
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
            }
            $this->css[$event] = null;
            unset($this->css[$event]);
        }
    }

} //end class;
