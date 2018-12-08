<?php
namespace PMVC\PlugIn\asset;
use PMVC as p;
use PMVC\Event;
${_INIT_CONFIG}[_CLASS] = __NAMESPACE__.'\asset';

class asset extends p\PlugIn
{
    private $css=[];
    private $js=[];
    private $isEcho=[];
    private $_push=[];
    private $_webpackState=[];
    private $_preload = [];
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

    public function flush()
    {
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        flush();
    }

    /**
     * Dispatch event function
     */
    public function onB4ProcessView($subject)
    {
        $subject->detach($this);
        $this->flush();
    }

    /**
     * Dispatch event function
     */
    public function onFinish($subject)
    {
        $subject->detach($this);
        $this->flush();
    }

    private function _initWebpackState()
    {
        if (!$this['assetsFolder']) {
          $this['assetsFolder'] = \PMVC\plug('view')['themeFolder'];
        }
        if (!$this['webpackStateFile']) {
          $this['webpackStateFile'] = \PMVC\plug('view')->get('webpackStateFile');
        }
        $path = \PMVC\lastSlash($this['assetsFolder']).$this['webpackStateFile'];
        $realPath = \PMVC\realPath($path);
        if ($realPath) {
            $json = \PMVC\fromJson(file_get_contents($path));
            $this->_webpackState = \PMVC\get($json, 'chunks');
        } else {
            trigger_error('Not found webpack state file. ['.$path.']', E_USER_WARNING);
        }
    }

    public function webpack($key, array $att=[], $pathOnly=false)
    {
        if (empty($this->_webpackState)) {
            $this->_initWebpackState();
        }
        $js = \PMVC\value($this->_webpackState, [$key, 0, 'publicPath']);
        if ($pathOnly) {
            return $js;
        }
        $att[] = 'src="'.$js.'"';
        return $this->getJsTag($att);
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

    public function preload($url, $as, $type='preload')
    {
        $this->_preload[$url] = [
            'as'   => $as,
            'type' => $type
        ];
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

    public function getJsTag(array $att=[], $content='')
    {
        $sAtt = '';
        if (!empty($att)) {
            $sAtt = join(' ', $att);
        }
        $s = '<script type="text/javascript" '.$sAtt.'>'.$content.'</script>';
        return $s;
    }

    public function echoJs($event=null, array $att=[])
    {
        $this->flush();
        if (is_null($event)) {
            $event = self::DEF;
        }
        if (is_array($this->js[$event])) {
            foreach ($this->js[$event] as $k=>$v) {
                $thisAtt = $att;
                switch ($v['type']) {
                    case 'file':
                        if (isset($this->isEcho[$k])) {
                            continue;
                        }
                        $this->isEcho[$k] = true;
                        array_push(
                            $thisAtt,
                            'src="'.$v['v']['url'].'"'
                        );
                        echo $this->getJsTag($thisAtt);
                        break;
                    case 'string':
                        echo $this->getJsTag($thisAtt, $v['v']);
                        break;
                }
            }
            $this->js[$event] = null;
            unset($this->js[$event]);
        }
    }
    
    public function echoCss($event=null)
    {
        $this->flush();
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
                        $p=[];
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

    public function echoPreload()
    {
        $this->flush();
        foreach ($this->_preload as $k=>$v) {
            echo '<link rel="'.$v['type'].'" as="'.$v['as'].'" href="'.$k.'" />';
        }
    }

} //end class;
