<?php

namespace App\Http\Controllers\Api\v1\animeland;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Movie;
use Cache;
use GuzzleHttp\Client;
use Underscore\Types\Arrays;
use Underscore\Underscore;
use Yangqi\Htmldom\Htmldom;

class MovieController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param int $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function page($page = 1)
    {
        $cachedHtml = $this->getCachedPage('animeland_page_' . $page,$page);

        $html = new Htmldom($cachedHtml);
        $items = [];
        foreach ($html->find('#dle-content .base') as $element) {
            if ($element->find('.bheading', 0)) {
                $id = mb_split('-', $element->find('.ratebox > div', 0)->id)[2];
                $title = $element->find('h1.heading a', 0)->plaintext;
                $date = $element->find('.headinginfo .date a', 0)->plaintext;
                $comment_count = $element->find('.bmid .bmore .arg a', 0)->plaintext;
                $image_small = env('BASE_URL_ANIMELAND') . $element->find('.maincont a[onclick="return hs.expand(this)"] img', 0)->src;
                $image_original = $element->find('.maincont a[onclick="return hs.expand(this)"]', 0)->href;

                // year
                preg_match("/\\[<a.*>(\\d+)<\\/a>\\]/", $element->find('.maincont',0)->innertext, $output_year);
                //production
                preg_match("/<b>Производство<\\/b>.*<img.*>(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_production);
                //type
                preg_match("/<b>Тип<\\/b>:(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_type);
                // gerne
                preg_match("/<b>Жанр<\\/b>(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_genres);
                $genres = [];
                list($output_genres, $genres) = $this->get_sublink_text($output_genres, $genres);
                //aired
                preg_match("/<b>Выпуск<\\/b>:(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_aired);
                // producers
                preg_match("/<b>Режиссёр<\\/b>(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_producers);
                $producers = [];
                list($output_producers, $producers) = $this->get_sublink_text($output_producers, $producers);
                // author
                preg_match("/<b>Автор оригинала<\\/b>(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_author);
                $authors = [];
                list($output_author, $authors) = $this->get_sublink_text($output_author, $authors);
                // scenarist
                preg_match("/<b>Сценарий<\\/b>(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_scenarist);
                $scenarist = [];
                list($output_scenarist, $scenarist) = $this->get_sublink_text($output_scenarist, $scenarist);
                //postscoring
                preg_match("/<b>Озвучка<\\/b>:\\s([a-zA-Zа-яА-Я].+)\\s/iU", $element->find('.maincont td',1)->innertext, $output_postscoring);
                //online
                preg_match("/<b>Онлайн<\\/b>:(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_online);
                $online = (isset($output_online[1]) && trim($output_online[1]) == 'да')?true:false;

                //torrent
                preg_match("/<b>Трекер<\\/b>.*&nbsp;(.*)&nbsp;/iU", $element->find('.maincont td',1)->innertext, $output_torrent);
                $torrent = (isset($output_torrent[1]) && trim($output_torrent[1]) == 'да')?true:false;

                preg_match("/<b>Студия<\\/b>:(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_studio);
                $studio = '';
                if(isset($output_studio[1])){
                    $result_html = new Htmldom($output_studio[1]);
                    $studio_array = explode('/',$result_html->find('a',0)->href);
                    array_pop($studio_array); // empty item
                    $studio = str_replace('+',' ',array_pop($studio_array));
                    $studio = str_replace('A 1','A-1',$studio); // A-1 studio name fix
                    $studio = str_replace('J C','J.C.',$studio); // J.C. studio name fix
                }

                $movie = Movie::firstOrCreate(['movie_id' => $id]);
                $attributes = array(
                    'movie_id' => $id,
                    'title' => $title,
                    'service' => 'animeland',
                    'info' => array(
                        'published_at' => $date,
                        'comments' => array(
                            'count' => $comment_count
                        ),
                        'images' => array(
                            'thumbnail' => $image_small,
                            'original' => $image_original
                        ),
                        'year' => (isset($output_year[1])) ? $output_year[1] : '',
                        'production' => (isset($output_production[1])) ? trim($output_production[1]) : '',
                        'genres' => $genres,
                        'type' => (isset($output_type[1])) ? trim($output_type[1]) : '',
                        'aired' => (isset($output_aired[1])) ? trim($output_aired[1]) : '',
                        'producers' => $producers,
                        'authors' => $authors,
                        'scenarist' => $scenarist,
                        'postscoring' => (isset($output_postscoring[1])) ? $output_postscoring[1] : '',
                        'studio' => $studio,
                        'online' => $online,
                        'torrent' => $torrent
                    )

                );
                $mergeAttributes = $movie->attributesToArray()+$attributes;
                $movie->fill($mergeAttributes);
                $movie->save();
                array_push($items, $movie);
            }
        }
        $html->clear();
        unset($html);

        return response()->json(array(
            'status' => 'success',
            'page' => $page,
            'items' => $items
        ), 200);
    }

    /**
     * Get description page
     * @param $movieId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($movieId){
        $cachedHtml = $this->getCachedFullPage('animeland_show_' . $movieId, $movieId);
        $html = new Htmldom($cachedHtml);
        //description
        $description = $html->find('.fullstory .maincont div[style="text-align: justify;"]',0)->plaintext;
        $description = str_replace('Описание:','',$description);
        $description = str_replace('Справка','',$description);
        //screenshots
        $screenshots = array();
        foreach($html->find('.fullstory .maincont div[align="center"] a[onclick="return hs.expand(this)"]') as $screen){
            $screen_item = array(
                'thumbnail' =>env('BASE_URL_ANIMELAND') .$screen->find('img',0)->src,
                'original' => $screen->href
            );
            array_push($screenshots,$screen_item);
        }
        $files = array();
        foreach($html->find('.fullstory div.maincont a[href*=aniplay]') as $file){
            preg_match("/javascript:aniplay\\('(.*)','link(\\d+)'\\)/iU", $file->href, $output_file);
            $part = $output_file[2];
            $link = $output_file[1];

            $file_item = array(
                'service'=>$this->getVideoService($link),
                'part'  =>$part,
                'original_link'=>$link,
                'download_link'=>$this->createDownloadLink($link)
            );
            array_push($files,$file_item);
        }
        $grouped_files = Arrays::group($files,function($value){
            return $value['part'];
        });

        $movie = Movie::firstOrCreate(['movie_id' => $movieId]);

        $movie->description = trim(nl2br($description));
        $info = $movie->info;
        $info->screenshots = $screenshots;
        $info->files = $grouped_files;
        $movie->info = $info;
        $movie->save();

        $html->clear();
        unset($html);

        return response()->json(array(
            'status' => 'success',
            'item' => $movie,
        ), 200);
    }

    /**
     * Create download link from video service
     *
     * @param string $original_link Url to video service
     * @return mixed download url
     */
    private function createDownloadLink($original_link){
        $download_link = '';
        switch($this->getVideoService($original_link)){
            case '24video':
                //get url
                $parts = explode('/',$original_link);
                $videoId = array_pop($parts);
                //get download page
                $download_link_page = sprintf('http://www.24video.com/video/download2/%d?type=mp4',$videoId);
                // get file link (1day)
                $download_link = Cache::remember($download_link_page, env('PAGE_CACHE_MIN'), function () use ($download_link_page) {
                    $client = new Client();
                    $response = $client->get($download_link_page);
                    $html = new Htmldom($response->getBody(true));
                    $download_link = $html->find('a#link',0)->href;
                    unset($html);
                    unset($client);
                    return $download_link;
                });
                break;
            case 'vk':
                $download_link = Cache::remember($original_link, env('PAGE_CACHE_MIN'), function () use ($original_link) {
                    $client = new Client();
                    $response = $client->get($original_link);
                    $body = $response->getBody(true);
                    preg_match("/var vars = ({.*})/i", $body, $output_html);
                    $jsonArray = json_decode($output_html[1], true);
                    $download_link = array(
                        '240'=>isset($jsonArray['url240'])?$jsonArray['url240']:'',
                        '360'=>isset($jsonArray['url360'])?$jsonArray['url360']:'',
                        '480'=>isset($jsonArray['url480'])?$jsonArray['url480']:'',
                        '720'=>isset($jsonArray['url720'])?$jsonArray['url720']:'',
                    );
                    unset($client);
                    return $download_link;
                });

                break;
            case 'sibnet':
                $download_link = Cache::remember($original_link, env('PAGE_CACHE_MIN'), function () use ($original_link) {
                    $client = new Client();
                    $response = $client->get($original_link);
                    $body = $response->getBody(true);
                    preg_match("/'file' : '(.*)m3u8',/iU", $body, $output_html);
                    $download_link = 'http://video.sibnet.ru'.$output_html[1].'mp4';
                    unset($client);
                    return $download_link;
                });

                break;
        }
        return $download_link;
    }

    /**
     * Get video service name from url
     *
     * @param string $original_link Url
     * @return mixed service name
     */
    private function getVideoService($original_link)
    {
        $service_name = '';
        if(strrpos($original_link, "24video")){
            $service_name = '24video';
        }else if(strrpos($original_link, "vk.com") || strrpos($original_link, "vkontakte.ru")){
            $service_name = 'vk';
        }else if(strrpos($original_link, "sibnet")){
            $service_name = 'sibnet';
        }

        return $service_name;
    }

    /**
     * Get cached page/*
     *
     * @param string $cache_key Unique key for cache
     * @param integer $page Page to parse
     * @param string $base_url base url
     * @return mixed response
     */
    private function getCachedPage($cache_key,$page)
    {
        return Cache::remember($cache_key, env('PAGE_CACHE_MIN'), function () use ($page) {
            $client = new Client(array(
                'base_uri' => env('BASE_URL_ANIMELAND')
            ));
            $response = $client->get('/page/' . $page);
            $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
            unset($client);
            return $responseUtf8;
        });
    }

    private function getCachedFullPage($cache_key,$movieId)
    {
        return Cache::remember($cache_key, 0/*env('PAGE_CACHE_MIN')*/, function () use ($movieId) {
            $client = new Client(array(
                'base_uri' => env('BASE_URL_ANIMELAND')
            ));
            $response = $client->get('/index.php?newsid=' . $movieId);
            $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
            unset($client);
            return $responseUtf8;
        });
    }

    /**
     * Get sublink from descriptions
     *
     * @param array $output_array html parsed object
     * @param array $result empty array for model
     * @return array result array
     */
    private function get_sublink_text($output_array, $result)
    {
        if (isset($output_array[1])) {
            $result_html = new Htmldom($output_array[1]);
            foreach ($result_html->find('a') as $item) {
                $result[] = $item->plaintext;
            }
            $result_html->clear();
            unset($result_html);
            return array($output_array, $result);
        }
        return array($output_array, $result);
    }


}
