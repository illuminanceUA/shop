<?php


namespace core\admin\controller;


use core\base\controller\BaseMethods;

class CreateSiteMapController extends BaseAdmin
{
    use BaseMethods;

    protected $linkArr = [];
    protected $parsingLogFile = 'parsing_log.txt';
    protected $fileArr = ['jpg', 'png', 'jpeg', 'gif', 'xls', 'xlsx', 'pdf', 'mp4', 'mpeg', 'avi', 'mp3', 'move'];

    protected $filterArr = [
        'url' => ['order'],
        'get' => ['masha']
    ];

    protected function inputData(){

        if(!function_exists('curl_init')){

            $this->writeLog('Отсутствует библиотека CURL');
            $_SESSION['res']['answer'] = '<div class="error">Library CURL as absent. Creation of sitemap impossible</div>';
            $this->redirect();
        }

        if(!$this->userId) $this->execBase();

        if(!$this->checkParsingTable()) return false;

        set_time_limit(0);

        if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile));
            @unlink($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile);

            $this->parsing(SITE_URL);

            $this->createSiteMap();

            !$_SESSION['res']['answer'] && $_SESSION['res']['answer'] = '<div class="success">Sitemap is created</div>';

            $this->redirect();

    }

    protected function parsing($url, $index = 0){

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        curl_setopt($curl, CURLOPT_RANGE, 0 - 4194304);

        $out = curl_exec($curl);

        curl_close($curl);

        if(!preg_match('/Content-Type:\s+text\/html/ui', $out)){

             unset($this->linkArr[$index]);

             $this->linkArr = array_values($this->linkArr);

             return;
        }

        if(!preg_match('/HTTP\/\d\.?\d?\s+20\d/uis', $out)){

            $this->writeLog('Не корректная ссылка при парсинге- ' . $url, $this->parsingLogFile);

            unset($this->linkArr[$index]);

            $this->linkArr = array_values($this->linkArr);

            $_SESSION['res']['answer'] = '<div class="error">Incorrect link in parsing - ' . $url . '<br>Sitemap is created</div>';

            return;

        }

        preg_match_all('/<a\s*?[^>]*?href\s*?=(["\'])(.+?)\1[^>]*?>/ui', $out, $links);

        if($links[2]){

            foreach ($links[2] as $link){

                if($link === '/' || $link === SITE_URL . '/') continue;

                foreach ($this->fileArr as $ext){

                    if($ext){

                        $ext = addslashes($ext);
                        $ext = str_replace('.', '\.', $ext);

                        if(preg_match('/' . $ext . '\s*?$|\?[^\/]/ui', $link)){

                            continue 2;

                        }

                    }

                }

                if(strpos($link, '/') === 0){
                    $link = SITE_URL . $link;
                }

                if(!in_array($link, $this->linkArr) && $link !== '#' && strpos($link, SITE_URL) === 0){

                    if($this->filter($link)){
                        $this->linkArr[] = $link;
                        $this->parsing($link, count($this->linkArr) - 1);
                    }

                }

            }

        }

    }

    protected function filter($link){

        if($this->filterArr){

            foreach ($this->filterArr as $type => $values){

                if($values){

                    foreach ($values as $item){

                        $item = str_replace('/', '\/', addslashes($item));

                        if($type === 'url'){
                            if(preg_match('/^[^\?]*' . $item . '/ui', $link)){
                                return false;
                            }
                        }

                        if($type === 'get'){

                           if(preg_match('/(\?|&amp;|=|&)'. $item .'(=|&amp;|&|$)/ui', $link, $matches)){
                               return false;
                           }

                        }

                    }
                }
            }

        }

       return true;
    }

    protected function checkParsingTable(){

       $tables = $this->model->showTables();

          if(!in_array('parsing_data', $tables)){

              $query = 'CREATE TABLE parsing_data (all_links text, temp_links text)';

              if(!$this->model->query($query, 'c') || !$this->model->add('parsing_data', ['fields' => ['all_links' => '', 'temp_links' => '']])){
                  return false;
              }

          }

          return true;

    }

    protected function createSiteMap(){

    }
}