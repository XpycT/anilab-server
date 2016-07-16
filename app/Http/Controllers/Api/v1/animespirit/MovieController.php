<?php

namespace App\Http\Controllers\Api\v1\animespirit;

use App\Helpers\Parser;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Movie;
use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use Request;
use Underscore\Types\Arrays;
use Yangqi\Htmldom\Htmldom;

use App\Helpers;

class MovieController extends Controller
{
    const protection_key = 'cd4cc9bffcb72bca5f88b3d8862cab4a';

    /**
     * Display a listing of the resource.
     *
     * @param int $page
     * @return \Illuminate\Http\JsonResponse
     */
    public function page($page = 1)
    {
        // generate key
        $path = Request::input('path');
        $search_query = Request::input('q');

        $key = 'animespirit_info_page_' . $page; // ex. animespirit_page_1
        if (isset($path)) {
            $key = 'animespirit_info_' . str_replace('/', '_', $path) . '_page_' . $page; //ex. animespirit__anime-rus_tv-rus__page_1
        } else if (isset($search_query)) {
            $key = 'animespirit_info_' . md5($search_query) . '_page_' . $page; //ex. animespirit_34jhg234876sdfsjknk98_page_1
        }
        $items = [];
        // get page or cache
        try {
            if (isset($search_query)) {
                $cachedHtml = $this->getCachedSearch($key, $page, $search_query);
            } else {
                $cachedHtml = $this->getCachedPage($key, $page, $path);
            }

        } catch (ClientException $e) {
            $response = $e->getResponse();
            if ($response->getStatusCode() == 404) {
                return response()->json(array(
                    'status' => $response->getReasonPhrase(),
                    'page' => (int)$page,
                    'movies' => $items
                ), 200);
            }
        }
        $html = new Htmldom($cachedHtml);
        // parse html
        foreach ($html->find('#dle-content .content-block') as $element) {
            if ($element->find('.content-block-title', 0)) {
                // get id from title link
                //$id = mb_split('-', $element->find('.ratebox > div', 0)->id)[2];
                $idArray = mb_split('/', $element->find('.content-block-title a', 0)->href);
                $id = mb_split('-', end($idArray))[0];

                $title = $element->find('.content-block-title a', 0)->plaintext;

                $description = $element->find("table tr:eq(1) div#news-id-{$id}", 0)->plaintext;

                $date = mb_split('\|', $element->find('table tr:eq(0) td', 0)->plaintext);
                $date = (count($date) > 2) ? trim($date[1]) : '';

                $image_small = $image_original = '';
                if ($element->find('a[onclick^="return hs.expand(this"]', 0)) {
                    $image_small = $element->find('a[onclick^="return hs.expand(this"] img', 0)->src;
                    $image_original = $element->find('a[onclick^="return hs.expand(this"]', 0)->href;
                }
                // year
                $blockhtml = $element->find('center table tr:eq(1)', 0)->innertext;

                preg_match("/<b>Год выпуска:<\\/b>(.*)<br/iU", $blockhtml, $output_year);

                //production
                preg_match("/<b>Издатель:<\\/b>(.*)<br/iU", $blockhtml, $output_production);
                //type
                preg_match("/<b>Категория:<\\/b>\s<span id=\".*\">(.*)<\\/span>/iU", $blockhtml, $output_type);
                // gerne
                preg_match("/<b>Жанр:<\\/b>(.*)<br/iU", $blockhtml, $output_genres);
                $genres = [];
                if (isset($output_genres[1])) {
                    $genres = array_map('trim', explode(',', $output_genres[1]));
                }
                //aired
                preg_match("/<b>Год выпуска:<\\/b>(.*)<br/iU", $blockhtml, $output_aired);
                // producers
                preg_match("/<b>Режиссёр:<\\/b>(.*)<br/iU", $blockhtml, $output_producers);
                $producers = [];
                if (isset($output_producers[1])) {
                    $producers = array_map('trim', explode(',', $output_producers[1]));
                }
                // author
                preg_match("/<b>Автор оригинала:<\\/b>(.*)<br/iU", $blockhtml, $output_author);
                $authors = [];
                if (isset($output_author[1])) {
                    $authors = array_map('trim', explode(',', $output_author[1]));
                }
                // scenarist
                preg_match("/<b>Сценарий:<\\/b>(.*)<br/iU", $blockhtml, $output_scenarist);
                $scenarist = [];
                if (isset($output_scenarist[1])) {
                    $scenarist = array_map('trim', explode(',', $output_scenarist[1]));
                }
                //postscoring
                preg_match("/<b>Озвучено:<\\/b>\\s([a-zA-Zа-яА-Я].+)\\s/iU", $blockhtml, $output_postscoring);

                // get movie from db
                $movie = Movie::firstOrCreate(['movie_id' => $id]);
                $movie->movie_id = $id;
                $movie->title = trim($title);
                $movie->description = trim($description);
                $movie->service = 'animespirit';
                $info = array(
                    'published_at' => trim($date),
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
                    'postscoring' => (isset($output_postscoring[1])) ? array($output_postscoring[1]) : array(),
                    'studio' => '',
                    'online' => true,
                    'torrent' => false
                );
                $info['comments']['count'] = 0;
                // merge infos
                $movie->info = array_merge((array)$movie->info, $info);
                $movie->save();
                array_push($items, $movie);
            }
        }

        $html->clear();
        unset($html);

        return response()->json(array(
            'status' => 'success',
            'page' => (int)$page,
            'movies' => $items
        ), 200);
    }

    /**
     * Get description page
     *
     * @param $movieId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($movieId)
    {
        // get page from cache
        $cachedHtml = $this->getCachedFullPage('animespirit_info_show_' . $movieId, $movieId);
        $html = new Htmldom($cachedHtml);
        //description
        $description = $html->find('#dle-content .content-block table tr:eq(1)', 0)->innertext;

        preg_match("/<b>Описание:<\\/b>(.*)<div/iU", $description, $output_description);

        $description = (count($output_description) > 1) ? strip_tags($output_description[1]) : $description;
        //screenshots
        $screenshots = array();
        foreach ($html->find('fieldset a[onclick^="return hs.expand(this"]') as $screen) {
            $screen_item = array(
                'thumbnail' => $screen->find('img', 0)->src,
                'original' => $screen->href
            );
            array_push($screenshots, $screen_item);
        }


        //load movie from db
        $movie = Movie::firstOrCreate(['movie_id' => $movieId]);
        $movie->title = trim($html->find('.content-block-title h2 a', 0)->plaintext);
        $movie->description = trim(nl2br($description));

        $info = is_object($movie->info) ? $movie->info : new \stdClass();
        $info->screenshots = $screenshots;
        $movie->info = $info;
        $movie->save();

        $html->clear();
        unset($html);

        return response()->json(array(
            'status' => 'success',
            'movie' => $movie,
        ), 200);
    }

    /**
     * Show files list
     *
     * @param $movieId
     * @return \Illuminate\Http\JsonResponse
     */
    public function files($movieId)
    {
        // get page from cache
        $cachedHtml = $this->getCachedFullPage('animespirit_info_show_' . $movieId, $movieId);
        // избавление от ВВ кодов
        $cachedHtml = $this->clearBBCodes($cachedHtml);

        $html = new Htmldom($cachedHtml);
        //files
        $files = array();

        

        foreach ($html->find('h3[id^=top_div_]') as $file) {
            foreach ($html->find('div.spoiler_holder') as $spoiler) {
                dd($spoiler->innertext);
            }
        }
        /*foreach ($html->find('h3[onclick^="upAnime("]') as $file) {
            //preg_match("/javascript:aniplay\\('(.*)','link(\\d+)'\\)/iU", $file->onclick, $output_file);
            preg_match("/upAnime\\((\d+)\\)/iU", $file->onclick, $output_file);

            $part_title = $file->plaintext;
            if (strpos($part_title, '[hide]') === false) {
                list($output_file, $id) = $output_file;

                $link = $html->find("p#an_ul{$id}",0)->plaintext;
                $download_link = $link;

                /!*if ($system != 4) {
                    $download_link = Parser::createDownloadLink($link);
                }*!/

                $file_item = array(
                    'service' => Parser::getVideoService($link),
                    'part' => $part_title,
                    'original_link' => $link,
                    'download_link' => $download_link
                );
                array_push($files, $file_item);
            }
        }*/


        $grouped_files_ = Arrays::group($files, function ($value) {
            return $value['part'];
        });
        $grouped_files = Arrays::values($grouped_files_);

        return response()->json($grouped_files, 200);
    }

    /**
     * Get comments list with limit
     *
     * @param $movieId
     * @return \Illuminate\Http\JsonResponse
     */
    public function comments($movieId)
    {
        $comments = array();

        $cachedHtml = $this->getCachedFullPage('animespirit_info_show_' . $movieId, $movieId);
        $html = new Htmldom($cachedHtml);


        //fetch first comments page
        foreach ($html->find('table[width=100%]') as $comment_item) {
            if($comment_item->find('div[id^=comm-id]',0)
                && $comment_item->find('td.slink', 0)
            && $comment_item->find('img[src^="http://images.animespirit.ru/uploads/fotos/"]', 0)){

                $tmpId = explode('-', $comment_item->find('div[id^=comm-id]',0)->id);
                $commentId = array_pop($tmpId);

                $body_text = $comment_item->find('div[id^=comm-id]', 0)->plaintext;
                $comment = array(
                    'comment_id' => $commentId,
                    'date' => trim(mb_split('\|', $comment_item->find('td.slink', 0)->plaintext)[0]),
                    'author' => '%UserName%',//$comment_item->parent()->find('a[onclick^="return dropdownmenu(this, event, UserMenu("', 0)->plaintext,
                    'body' => trim($body_text),
                    'avatar' => $comment_item->find('img[src^="http://images.animespirit.ru/uploads/fotos/"]', 0)->src
                );
                array_push($comments, $comment);
            }

        }

        //get movie from db
        $movie = Movie::firstOrCreate(['movie_id' => $movieId]);
        //clear html
        $html->clear();
        unset($html);

        return response()->json(array(
            'status' => 'success',
            'count' => $movie->info->comments->count,
            'list' => $comments,
        ), 200);
    }


    /**
     * Get cached page
     *
     * @param string $cache_key Unique key for cache
     * @param integer $page Page to parse
     * @param string $path category path
     * @return mixed response
     */
    private function getCachedPage($cache_key, $page, $path)
    {
        //return Cache::remember($cache_key, env('PAGE_CACHE_MIN'), function () use ($page, $path) {
        $url = isset($path) ? urldecode($path) . 'page/' . $page . '/' : '/page/' . $page . '/';
        $client = new Client(array(
            'base_uri' => env('BASE_URL_ANIMESPIRIT')
        ));
        $jar = CookieJar::fromArray(['__DDOS_COOKIE' => self::protection_key], 'www.animespirit.ru');
        $response = $client->get($url, ['cookies' => $jar, 'headers' => ['User-Agent' => config('api.userAgent')]]);
        $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');

        unset($client);
        return $responseUtf8;
        //});
    }

    /**
     * Get cached search
     *
     * @param string $cache_key Unique key for cache
     * @param integer $page Page to parse
     * @param string $search_query search query
     * @return mixed response
     */
    private function getCachedSearch($cache_key, $page, $search_query)
    {
        return Cache::remember($cache_key, env('PAGE_CACHE_MIN'), function () use ($page, $search_query) {
            $client = new Client(array(
                'base_uri' => env('BASE_URL_ANIMESPIRIT')
            ));
            $result_from = ((int)$page * 7 - 7) + 1;
            $response = $client->post("/index.php?do=search", [
                'form_params' => [
                    'do' => 'search',
                    'subaction' => 'search',
                    'full_search' => 0,
                    'search_start' => $page,
                    'result_from' => ($page == 1) ? 1 : $result_from,
                    'story' => mb_convert_encoding($search_query, 'cp1251', 'utf-8')
                ]
            ]);
            $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
            unset($client);
            return $responseUtf8;
        });
    }

    /**
     * Get description page
     *
     * @param string $cache_key Unique key for cache
     * @param integer $movieId Page to parse
     * @return mixed response
     */
    private function getCachedFullPage($cache_key, $movieId)
    {
        //return Cache::remember($cache_key, env('PAGE_CACHE_MIN'), function () use ($movieId) {
        $url = '/index.php?newsid=' . $movieId;
        $client = new Client(array(
            'base_uri' => env('BASE_URL_ANIMESPIRIT')
        ));
        $jar = CookieJar::fromArray(['__DDOS_COOKIE' => self::protection_key], 'www.animespirit.ru');
        $response = $client->get($url, ['cookies' => $jar, 'headers' => ['User-Agent' => config('api.userAgent')]]);
        $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
        unset($client);
        return $responseUtf8;
        //});
    }

    /**
     * Clear BBCodes
     *
     * @param $html
     * @return mixed
     */
    private function clearBBCodes($html)
    {
        $output = $html;
        $output =str_replace('[hide]','-qwe-',$output);
        $output =preg_replace('/\[(bgc|sbgc)=#[a-z0-9].*\]/iU', '', $output);


        $output =preg_replace('/\[sss=.*colorstart-->(.*)<!--colorend.*\](.*)\[\/sss\]/iU',
            "<div class='spoiler_holder'><h3 id='ss5'>$1</h3><div id='spoiler_'><center>$2</center></div></div>",
            $output);

        $output =preg_replace('/\[sss=.*colorstart-->(.*)<!--colorend.*\](.*)\[\/sss\]/iU',
            "<div class='spoiler_holder'><h3 id='ss5'>$1</h3><div id='spoiler_'><center>$2</center></div></div>",
            $output);

        return $output;
    }


}
