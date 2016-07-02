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

                $descrDom = new Htmldom($element->find("table tr:eq(1) div#news-id-{$id}", 0)->outertext);
                $descrDom->find("div#news-id-{$id} div", 1)->outertext = '';
                $descrDom->find("div#news-id-{$id} div", 1)->innertext = '';
                $descrDom->save();
                
                $description = $descrDom->plaintext;

                $date = mb_split('\|', $element->find('table tr:eq(0) td', 0)->plaintext);
                $date = (count($date) > 2)?trim($date[1]):'';

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
        $description = $html->find('.fullstory .maincont .s_post_info_description', 0)->plaintext;
        $description = str_replace('Описание:', '', $description);
        $description = str_replace('Справка', '', $description);
        //screenshots
        $screenshots = array();

        //load movie from db
        $movie = Movie::firstOrCreate(['movie_id' => $movieId]);
        $movie->title = trim($html->find('.fullstory h1.heading', 0)->plaintext);
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
        $html = new Htmldom($cachedHtml);
        $title = trim(mb_split('/', $html->find('h1.heading', 0)->plaintext)[0]);
        //files
        $files = array();
        foreach ($html->find('.fullstory div.maincont a[onclick^=videoPlayer]') as $file) {
            //preg_match("/javascript:aniplay\\('(.*)','link(\\d+)'\\)/iU", $file->onclick, $output_file);
            preg_match("/videoPlayer\\('(.*)',\\s'(\\d+)',\\s'(.*)'\\)\\;return/iU", $file->onclick, $output_file);
            $part_title = $file->innertext;

            list($output_file, $id, $system, $url) = $output_file;

            $link = '';
            switch ($system) {
                case 1:
                    $link = sprintf('http://video.rutube.ru/%s', $url);
                    break;
                case 2:
                    $link = sprintf('http://www.youtube.com/embed/%s', $url);
                    break;
                case 3:
                    $link = sprintf('http://v.kiwi.kz/v/%s', $url);
                    break;
                case 4:
                    $link = sprintf('http://video.sibnet.ru/shell.swf?videoid=%s', $url);
                    break;
                case 5:
                    if (count($url) === 56 || count($url) === 45) {
                        $link = sprintf('http://myvi.ru/ru/flash/player/%s', $url);
                    } else {
                        $link = sprintf('http://myvi.ru/ru/flash/player/pre/%s', $url);
                    }
                    break;
                case 6:
                    $link = sprintf('%s', $url);
                    break;
            }

            $download_link = $link;
            if ($system != 4) {
                $download_link = Parser::createDownloadLink($link);
            }

            $file_item = array(
                'service' => Parser::getVideoService($link),
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
        // create comment url
        $latest_page = ($html->find('.basenavi .navigation a', -1) !== null) ? $html->find('.basenavi .navigation a', -1)->innertext : null;
        //clear html
        $html->clear();
        unset($html);

        //fetch all comments pages
        $n = $latest_page ? $latest_page : 1;
        $index = 0; // index for page count
        for ($i = 1; $i <= $n; $i++) {
            ++$index;
            if ($index > config('api.comment_page_limit')) continue;
            //http://animelend.info/engine/ajax/comments.php?cstart=2&news_id=5555&skin=animelend
            $url = sprintf('%s/engine/ajax/comments.php?cstart=%d&news_id=%d&skin=animelend', env('BASE_URL_ANIMESPIRIT'), $i, $movieId);

            $response_json = Cache::remember(md5($url), env('PAGE_CACHE_MIN'), function () use ($url) {
                $client = new Client();
                $response = $client->get($url);
                $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
                $response_json = json_decode($responseUtf8, true);
                return $response_json;
            });
            //parse comment page
            $html = new Htmldom($response_json['comments']);
            foreach ($html->find('div[id^=comment-id]') as $comment_item) {
                $tmpId = explode('-', $comment_item->id);
                $commentId = array_pop($tmpId);

                $body_text = $comment_item->find('div[id^=comm-id]', 0)->plaintext;
                $comment = array(
                    'comment_id' => $commentId,
                    'date' => $comment_item->find('.comhead ul>li.first', 0)->plaintext,
                    'author' => $comment_item->find('h3 a', 0)->plaintext,
                    'body' => trim($body_text),
                    'avatar' => $comment_item->find(".avatarbox > img", 0)->src
                );
                array_push($comments, $comment);
            }
        }

        //get movie from db
        $movie = Movie::firstOrCreate(['movie_id' => $movieId]);
        /*$info = is_object($movie->info) ? $movie->info : new \stdClass();
        $info->comments = isset($info->comments) ? $info->comments : new \stdClass();
        $info->comments->list = $comments;
        $movie->info = $info;
        $movie->save();*/

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
        $response = $client->get($url, ['cookies' => $jar,'headers'=> ['User-Agent' => config('api.userAgent')]]);
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
        $client = new Client(array(
            'base_uri' => env('BASE_URL_ANIMESPIRIT')
        ));
        $response = $client->get('/index.php?newsid=' . $movieId);
        $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
        unset($client);
        return $responseUtf8;
        //});
    }


}
