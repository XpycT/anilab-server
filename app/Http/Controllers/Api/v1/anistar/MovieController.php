<?php

namespace App\Http\Controllers\Api\v1\anistar;

use App\Helpers;
use App\Helpers\Parser;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Movie;
use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use Request;
use Underscore\Types\Arrays;
use URL;
use Yangqi\Htmldom\Htmldom;

class MovieController extends Controller
{
    //test
    //const protection_key = '441c2c6310c27452cf6947cfc51709e8';
    //live
    const protection_key = '6a4a23c9d5d96ef39504827537966d12';

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

        $key = 'anistar_page_' . $page; // ex. anistar_page_1
        if (isset($path)) {
            $key = 'anistar_' . str_replace('/', '_', $path) . '_page_' . $page; //ex. anistar__anime-rus_tv-rus__page_1
        } else if (isset($search_query)) {
            $key = 'anistar_' . md5($search_query) . '_page_' . $page; //ex. anistar_34jhg234876sdfsjknk98_page_1
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

        foreach ($html->find('#dle-content .news') as $element) {
            if ($element->find('a[href^="/filter/year/"]', 0)) {
                // get id from title link
                $idArray = mb_split('/', $element->find('.news_header .title_left > a', 0)->href);
                $id = mb_split('-', end($idArray))[0];

                $title = $element->find('.news_header .title_left > a', 0)->plaintext;
                $date = ($element->find('.date-icon', 0)) ? $element->find('.date-icon', 0)->plaintext : '';
                //comment count
                $comment_count = 0;

                $url = URL::to('/') . '/api/v1/anistar/image?image=';
                $image_small = $url . $element->find('.news_avatar img', 0)->src;
                $image_original = str_replace('/thumb/', '/', $image_small);

                $description = $element->find('div.descripts', 0)->plaintext;

                // year
                $year = $element->find('a[href^="/filter/year/"]', 0)->plaintext;
                //production
                $output_production = array();
                //type
                $output_type = array();
                // gerne
                $genres = [];
                foreach ($element->find('a[href^="/filter/janrs/"]') as $genre) {
                    $genres[] = $genre->innertext;
                }
                //aired
                $output_aired = array();
                // producers
                $producers = [];
                foreach ($element->find('a[href^="/filter/director/"]') as $producer) {
                    $producers[] = $producer->innertext;
                }
                // author
                $authors = [];
                foreach ($element->find('a[href^="/filter/author/"]') as $author) {
                    $authors[] = $author->innertext;
                }
                // scenarist
                $scenarist = [];
                //postscoring
                $output_postscoring = [];
                //online
                $online = true;
                //torrent
                $torrent = true;
                // studio
                $studio = '';
                // get movie from db
                $movie = Movie::firstOrCreate(['movie_id' => $id]);
                $movie->movie_id = $id;
                $movie->title = trim($title);
                $movie->description = trim($description);
                $movie->service = 'anistar';
                $info = array(
                    'published_at' => trim($date),
                    'images' => array(
                        'thumbnail' => $image_small,
                        'original' => $image_original
                    ),
                    'year' => (isset($year)) ? $year : '',
                    'production' => (isset($output_production[1])) ? trim($output_production[1]) : '',
                    'genres' => $genres,
                    'aired' => (isset($output_aired[1])) ? trim($output_aired[1]) : '',
                    'producers' => $producers,
                    'authors' => $authors,
                    'scenarist' => $scenarist,
                    'postscoring' => (isset($output_postscoring[1])) ? array($output_postscoring[1]) : array(),
                    'studio' => $studio,
                    'online' => $online,
                    'torrent' => $torrent
                );
                $info['comments']['count'] = $comment_count;
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
        $cachedHtml = $this->getCachedFullPage('anistar_show_' . $movieId, $movieId);
        $html = new Htmldom($cachedHtml);
        //description
        $description = $html->find('#dle-content .news .descripts', 0)->plaintext;
        //screenshots
        $screenshots = array();

        //load movie from db
        $movie = Movie::firstOrCreate(['movie_id' => $movieId]);
        $movie->title = trim($html->find('.title_left h1', 0)->plaintext);
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
        $cachedHtml = $this->getCachedFullPage('anistar_show_' . $movieId, $movieId);
        $html = new Htmldom($cachedHtml);
        $title = trim(mb_split('/', $html->find('.title_left h1', 0)->plaintext)[0]);
        //files
        $grouped_files = array();

        if ($html->find('.video_as iframe', 0)) {
            $frame_url = $html->find('.video_as iframe', 0)->src;
            $frameResponse = $this->getVideoIframe($frame_url);
            $html_frame = new Htmldom($frameResponse);
            $files = array();
            foreach ($html_frame->find('#PlayList span#link') as $file) {
                preg_match("/.*\\('(.*)'\\s,.*\\)/iU", $file->outertext, $output_file);
                $part = mb_split('\\[', $file->innertext)[0];
                $link = $output_file[1];
                $part_title = $title . ' - ' . trim($part);

                $download_link = $link;
                $videoService = Parser::getVideoService($link);

                if($videoService !== 'sibnet'){
                    $download_link = Parser::createDownloadLink($link);
                }

                $file_item = array(
                    'service' => $videoService,
                    'part' => $part_title,
                    'original_link' => $link,
                    'download_link' => $download_link
                );
                array_push($files, $file_item);
            }
            $grouped_files_ = Arrays::group($files, function ($value) {
                return $value['part'];
            });
            $grouped_files = Arrays::values($grouped_files_);
        }
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

        //get movie from db
        $movie = Movie::firstOrCreate(['movie_id' => $movieId]);
        return response()->json(array(
            'status' => 'success',
            'count' => $movie->info->comments->count,
            'list' => $comments,
        ), 200);
    }

    public function image()
    {
        $image = Request::input('image');
        $client = new Client(array(
            'base_uri' => env('BASE_URL_ANISTAR')
        ));
        $jar = CookieJar::fromArray(['blazingfast-layer7-protection' => self::protection_key], 'anistar.ru');
        $response = $client->get($image, ['cookies' => $jar,'headers'=> ['User-Agent' => config('api.userAgent')]]);
        try {
            $response = $client->get($image, ['cookies' => $jar,'headers'=> ['User-Agent' => config('api.userAgent')]]);
            $responseUtf8 = response($response->getBody(true), 200, ['Content-Type' => 'image/jpeg']);
        }
        catch (ServerException $e) {
            if ($e->hasResponse()) {
                $m = $e->getResponse();
                $responseUtf8 = response($m->getBody(true), 200, ['Content-Type' => 'image/jpeg']);
            }
        }
        unset($client);
        return $responseUtf8;
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
        return Cache::remember($cache_key, env('PAGE_CACHE_MIN'), function () use ($page, $path) {
        $pos = strpos($path, 'year');
        if ($pos === false) {
            $url = '/anime' . (isset($path) ? urldecode($path) . 'page/' . $page . '/' : '/page/' . $page . '/');
            if ($page == 1) {
                if ($pos === false) {
                    $url = '/anime/';
                    if (isset($path)) {
                        $url = '/anime' . urldecode($path);
                    }
                } else {
                    $url = urldecode($path);
                }
            }
        }else{
            $url = urldecode($path) . 'page/' . $page . '/';
            if ($page == 1) {
                $url = urldecode($path);
            }
        }

        $client = new Client(array(
            'base_uri' => env('BASE_URL_ANISTAR')
        ));
        $jar = CookieJar::fromArray(['blazingfast-layer7-protection' => self::protection_key], 'anistar.ru');

        try {
            $response = $client->get($url, ['cookies' => $jar,'headers'=> ['User-Agent' => config('api.userAgent')]]);
            $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'windows-1251');
        }
        catch (ServerException $e) {
            if ($e->hasResponse()) {
                $m = $e->getResponse();
                $responseUtf8 = mb_convert_encoding($m->getBody(true), 'utf-8', 'windows-1251');
            }
        }
        unset($client);
        return $responseUtf8;
        });
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
                'base_uri' => env('BASE_URL_ANISTAR')
            ));
            $result_from = ((int)$page * 10 - 10) + 1;
            $jar = CookieJar::fromArray(['blazingfast-layer7-protection' => self::protection_key], 'anistar.ru');

            try {
                $response = $client->post("/index.php?do=search", [
                    'form_params' => [
                        'do' => 'search',
                        'subaction' => 'search',
                        'full_search' => 0,
                        'search_start' => $page,
                        'result_from' => ($page == 1) ? 1 : $result_from,
                        'story' => mb_convert_encoding($search_query, 'cp1251', 'utf-8')
                    ],
                    'cookies' => $jar,
                    'headers'=> ['User-Agent' => config('api.userAgent')]
                ]);
                $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'windows-1251');
            }
            catch (ServerException $e) {
                if ($e->hasResponse()) {
                    $m = $e->getResponse();
                    $responseUtf8 = mb_convert_encoding($m->getBody(true), 'utf-8', 'windows-1251');
                }
            }
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
        return Cache::remember($cache_key, env('PAGE_CACHE_MIN'), function () use ($movieId) {
            $client = new Client(array(
                'base_uri' => env('BASE_URL_ANISTAR')
            ));
            $jar = CookieJar::fromArray(['blazingfast-layer7-protection' => self::protection_key], 'anistar.ru');
            try {
                $response = $client->get('/index.php?newsid=' . $movieId, ['cookies' => $jar,'headers'=> ['User-Agent' => config('api.userAgent')]]);
                $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
            }
            catch (ServerException $e) {
                if ($e->hasResponse()) {
                    $m = $e->getResponse();
                    $responseUtf8 = mb_convert_encoding($m->getBody(true), 'utf-8', 'windows-1251');
                }
            }
            unset($client);
            return $responseUtf8;
        });
    }

    private function getVideoIframe($url)
    {
        $client = new Client(array(
            'base_uri' => env('BASE_URL_ANISTAR')
        ));

        $jar = CookieJar::fromArray(['blazingfast-layer7-protection' => self::protection_key], 'anistar.ru');
        try {
            $response = $client->get($url, ['cookies' => $jar,'headers'=> ['User-Agent' => config('api.userAgent')]]);
            $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
        }
        catch (ServerException $e) {
            if ($e->hasResponse()) {
                $m = $e->getResponse();
                $responseUtf8 = mb_convert_encoding($m->getBody(true), 'utf-8', 'windows-1251');
            }
        }

        unset($client);
        return $responseUtf8;
    }

}
