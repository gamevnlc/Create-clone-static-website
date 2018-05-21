<?php
require_once __DIR__ . '/vendor/autoload.php';

$url = 'http://www.aiduwen.com/';

//function error_handler($errno, $errstr, $errfile, $errline,
//                       array $errcontext) {
//// $errstr will contain something like this:
//// fopen(http://localhost.example/404): failed to open stream:
//// HTTP request failed! HTTP/1.0 404 Not Found
//    if ($httperr = strstr($errstr, ’HTTP/’)) {
//// $httperr will contain HTTP/1.0 404 Not Found in the case
//// of the above example, do something useful with that here
//    }
//}
include('simple_html_dom.php');


class CloneWebsite {
    const READ = 'r';
    const PARRENT = 'result';
    private $url;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function dumpValue($argv)
    {
        $data = func_get_args();
        echo '<pre>';
        if (is_array ($data)) {
            foreach ($data as $key => $value) {
                print_r($value);
                print_r('<br>');
            }
        } else {
            print_r($data);
        }
        
        echo '</pre>';
    }

    public function fileGetContent() {
        $response = file_get_contents($this->url);
        $this->dumpValue($http_response_header);
    }

    public function streamData() {

        $handle = fopen($this->url, self::READ);
        $response = stream_get_contents($handle);
        $meta = stream_get_meta_data($handle);
        $this->dumpValue($meta['wrapper_data']);
    }

    public function curlEx() {
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSER, true);
        $response = curl_exec($ch);
        curl_close($ch);
    }

    public function simpleHtmlDom() {
        $html = file_get_html($this->url);
        //Find all "A" tags and print their HREFs
        
        $folderPath = $this->createFolder($this->url);
        
        $this->getCss($html->find('link[href]'), $folderPath);
        $this->getJs($html->find('script[type="text/javascript"]'), $folderPath);
        
        // $myfile = fopen($folderPath.DIRECTORY_SEPARATOR.'index.html', "w") or die("Unable to open file!");
        file_put_contents($folderPath.DIRECTORY_SEPARATOR.'index.html', $html);
        // fclose($myfile);
        // var_dump($folderPath);
        
        $html->clear();
    }

    public function getJs($link, $parentPath)
    {
        foreach($link as $e) {
            if (!$e->attr) {
                continue;
            }

            if (!isset($e->attr['src'])) {
                continue;
            }
            if (filter_var($e->attr['src'], FILTER_VALIDATE_URL)) { 
                var_dump('url'); // not yet done
            } else {
                $list = explode('/', $e->attr['src']);
                $path = $parentPath;
                foreach ($list as $key => $value) {
                    if (!$value) {
                        continue;
                    }
                    if ($key === count($list) -1) {
                        continue;
                    }
                    $path .= DIRECTORY_SEPARATOR.$value;
                    $this->makeFolder($path);
                }

                $data = @file_get_contents($this->url.$e->attr['src']);
                if ($data) {
                    file_put_contents($parentPath.DIRECTORY_SEPARATOR.$e->attr['src'], $data);
                    $e->attr['src'] = ltrim($e->attr['src'], '/');
                } else {
                    var_dump('File not found '. $e->attr['src']);
                }
            }           
        }
    }

    public function getCss($link, $parentPath)
    {
        foreach($link as $e) {
            if (!$e->attr) {
                continue;
            }

            if (!isset($e->attr['href'])) {
                continue;
            }

            if (filter_var($e->attr['href'], FILTER_VALIDATE_URL)) { 
                var_dump('url'); // not yet done
            } else {
                $list = explode('/', $e->attr['href']);
                $path = $parentPath;
                $onlinePath = $this->url;
                foreach ($list as $key => $value) {
                    if (!$value) {
                        continue;
                    }

                    if ($key === count($list) -1) {
                        continue;
                    }

                    $onlinePath .= $value.'/';
                    $path .= DIRECTORY_SEPARATOR.$value;
                    $this->makeFolder($path);
                }

                $data = @file_get_contents($this->url.$e->attr['href']);

                if ($data) {
                    $currentPos = $path;
                    // write new file css
                    file_put_contents($parentPath.DIRECTORY_SEPARATOR.$e->attr['href'], $data);
                    $e->attr['href'] = ltrim($e->attr['href'], '/');
                    // get import url css
                    $this->getImportCss($data, $onlinePath, $path);
                    var_dump($currentPos);
                    $this->makeFolder($currentPos.DIRECTORY_SEPARATOR.'..\\images\\');    
                    if (preg_match_all('~\bbackground(-image)?\s*:(.*?)\(\s*(\'|")?(?<image>.*?)\3?\s*\)~i',$data,$matches)) {
                        if (isset($matches['image'])) {
                            $images = $matches['image'];
                            
                            foreach ($images as $key => $imagePath) {
                                if (filter_var($imagePath, FILTER_VALIDATE_URL)) { 
                                    var_dump('url'); // not yet done
                                } else {
                                    $list = explode('/', $imagePath);
                                    $pos = $currentPos.DIRECTORY_SEPARATOR;
                                    foreach ($list as $key => $value) {
                                        if (!$value) {
                                            continue;
                                        }
                    
                                        if ($key === count($list) -1) {
                                            continue;
                                        }
                                        
                                        if ($value === '..') {
                                            $pos .= $value;
                                            continue;
                                        }
                                        $onlinePath .= $value.'/';
                                        $path .= $pos.DIRECTORY_SEPARATOR.$value;
                                        var_dump($path);
                                        $this->makeFolder($path);
                                        die;
                                    }
                    
                                    print_r($list);
                                    die;        
                                }
                                
                            }
                        }
                    }
                    
                } else {
                    var_dump('File not exist '. $e->attr['href']);
                }
            }
        }
    }

    public function getImportCss($data, $onlinePath, $path)
    {
        if (preg_match_all('/@import (url\(\"?)?(url\()?(\")?(.*?)(?(1)\")+(?(2)\))+(?(3)\")/im', $data, $match)) {
            if (isset($match[4])) {
                foreach ($match[4] as $key => $url) {
                    if (filter_var($url, FILTER_VALIDATE_URL)) { 
                        var_dump('url'); // not yet done
                    } else {
                        $content = @file_get_contents($onlinePath.$url);
                        if ($content) {
                            file_put_contents($path.DIRECTORY_SEPARATOR.$url, $content);
                        }
                    }
                }
            }
        }
    }

    public function getFolderName($url) {
        $replace = [
            "http://" => "",
            "/" => ""
        ];
        
        return strtr($url, $replace);
    }

    public function createFolder($url)
    {
        $folder = $this->getFolderName($this->url);
        $folderPath = self::PARRENT.DIRECTORY_SEPARATOR.$folder;
        if (file_exists($folderPath)) {
            return $folderPath;
        }
        
        if (!mkdir($folderPath, 0777, true)) {
            die('Failed to created folders');
        }
        return $folderPath;
    }

    public function makeFolder($folderPath)
    {
        if (file_exists($folderPath)) {
            return $folderPath;
        }
        
        if (!mkdir($folderPath, 0777, true)) {
            die('Failed to created folders');
        }
        return $folderPath;
    }
}

echo '<pre>';
$test = new CloneWebsite($url);
$test->simpleHtmlDom();
echo '</pre>';
//$test->streamData();