<?php


namespace core\admin\controller;


use core\base\controller\BaseMethods;

class CreateSiteMapController extends BaseAdmin
{
    use BaseMethods;

    protected $allLinks = [];
    protected $tempLinks = [];

    protected $maxLinks = 5000;

    protected $parsingLogFile = 'parsing_log.txt';
    protected $fileArr = ['jpg', 'png', 'jpeg', 'gif', 'xls', 'xlsx', 'pdf', 'mp4', 'mpeg', 'avi', 'mp3', 'move'];

    protected $filterArr = [
        'url' => [],
        'get' => []
    ];

    protected function inputData($linksCounter = 1){

        if(!function_exists('curl_init')){
            $this->cancel(0, 'Library CURL as absent. Creation of sitemap impossible', '', true);
        }

        if(!$this->userId) $this->execBase();

        if(!$this->checkParsingTable()){
            $this->cancel(0, 'You have problem with database table parsing data', '', true);
        }

        set_time_limit(0);

        $reserve = $this->model->get('parsing_data')[0];

        foreach ($reserve as $name => $item){

            if($item) $this->$name = json_decode($item);
               else $this->$name = [SITE_URL];

        }

        $this->maxLinks = (int)$linksCounter > 1 ? ceil($this->maxLinks / $linksCounter) : $this->maxLinks;

        while ($this->tempLinks){

            $tempLinksCount = count($this->tempLinks);

            $links = $this->tempLinks;

            $this->tempLinks = [];

            if($tempLinksCount > $this->maxLinks){

                $links = array_chunk($links, ceil($tempLinksCount / $this->maxLinks));

                $countChunks = count($links);

                for($i = 0; $i < $countChunks; $i++){

                    $this->parsing($links[$i]);

                    unset($links[$i]);

                    if($links){

                        $this->model->edit('parsing_data', [
                            'fields' => [
                                'temp_links' => json_encode(array_merge(...$links)),
                                'all_links' => json_encode($this->allLinks)
                            ]
                        ]);
                    }

                }


            }else{
                $this->parsing($links);
            }

            $this->model->edit('parsing_data', [
                'fields' => [
                    'temp_links' => json_encode($this->tempLinks),
                    'all_links' => json_encode($this->allLinks)
                ]
            ]);

        }

       /* if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile));
            @unlink($_SERVER['DOCUMENT_ROOT'] . PATH . 'log/' . $this->parsingLogFile);*/

         //   $this->parsing(SITE_URL);

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

             unset($this->allLinks[$index]);

             $this->allLinks = array_values($this->allLinks);

             return;
        }

        if(!preg_match('/HTTP\/\d\.?\d?\s+20\d/uis', $out)){

            $this->writeLog('Не корректная ссылка при парсинге- ' . $url, $this->parsingLogFile);

            unset($this->allLinks[$index]);

            $this->allLinks = array_values($this->allLinks);

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

                if(!in_array($link, $this->allLinks) && $link !== '#' && strpos($link, SITE_URL) === 0){

                    if($this->filter($link)){
                        $this->allLinks[] = $link;
                        $this->parsing($link, count($this->allLinks) - 1);
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

    protected function cancel($success = 0, $message = '', $logMessage = '', $exit = false){

        $exitArr = [];

        $exitArr['success'] = $success;
        $exitArr['message'] = $message ? $message : 'ERROR PARSING';
        $logMessage = $logMessage ? $logMessage : $exitArr['message'];

        $class = 'success';

        if(!$exitArr['success']){

            $class = 'error';

            $this->writeLog($logMessage, 'parsing_log.txt');

        }

        if($exit){

            $exitArr['message'] = '<div class="'. $class .'">' . $exitArr['message'] . '</div>';
            exit(json_encode($exitArr));
        }

    }

    protected function createSiteMap(){

    }
}