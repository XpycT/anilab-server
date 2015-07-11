<?php

namespace App\Http\Controllers\Api\v1\animeland;

use App\Helpers\Parser;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Movie;
use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Request;
use Underscore\Types\Arrays;
use Yangqi\Htmldom\Htmldom;

use app\Helpers;

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
        // generate key
        $path = Request::input('path');
        $key = isset($path) ? 'animeland_' . str_replace('/', '_', $path) . '_page_' . $page : 'animeland_page_' . $page;
        $items = [];
        // get page or cache
        try {
            $cachedHtml = $this->getCachedPage($key, $page, $path);
        } catch (ClientException $e) {
            $response = $e->getResponse();
            return response()->json(array(
                'status' => $response->getReasonPhrase(),
                'page' => (int)$page,
                'movies' => $items
            ), $response->getStatusCode());
        }
        $html = new Htmldom($cachedHtml);
        // parse html

        foreach ($html->find('#dle-content .base') as $element) {
            if ($element->find('.bheading', 0)) {
                $id = mb_split('-', $element->find('.ratebox > div', 0)->id)[2];
                $title = $element->find('h1.heading a', 0)->plaintext;
                $date = $element->find('.headinginfo .date a', 0)->plaintext;
                $comment_count = $element->find('.bmid .bmore .arg a', 0)->plaintext;

                if ($element->find('.maincont a[onclick="return hs.expand(this)"]', 0)) {
                    $image_small = env('BASE_URL_ANIMELAND') . $element->find('.maincont a[onclick="return hs.expand(this)"] img', 0)->src;
                    $image_original = $element->find('.maincont a[onclick="return hs.expand(this)"]', 0)->href;
                } else {
                    $image_small = env('BASE_URL_ANIMELAND') . $element->find('.maincont td[valign="top"] img', 0)->src;
                    $image_original = $image_small;
                }
                // year
                preg_match("/\\[<a.*>(\\d+)<\\/a>\\]/", $element->find('.maincont', 0)->innertext, $output_year);
                //production
                preg_match("/<b>Производство<\\/b>.*<img.*>(.*)<br/iU", $element->find('.maincont td', 1)->innertext, $output_production);
                //type
                preg_match("/<b>Тип<\\/b>:(.*)<br/iU", $element->find('.maincont td', 1)->innertext, $output_type);
                // gerne
                preg_match("/<b>Жанр<\\/b>(.*)<br/iU", $element->find('.maincont td', 1)->innertext, $output_genres);
                $genres = [];
                list($output_genres, $genres) = Parser::getTextFromLinks($output_genres, $genres);
                //aired
                preg_match("/<b>Выпуск<\\/b>:(.*)<br/iU", $element->find('.maincont td', 1)->innertext, $output_aired);
                // producers
                preg_match("/<b>Режиссёр<\\/b>(.*)<br/iU", $element->find('.maincont td', 1)->innertext, $output_producers);
                $producers = [];
                list($output_producers, $producers) = Parser::getTextFromLinks($output_producers, $producers);
                // author
                preg_match("/<b>Автор оригинала<\\/b>(.*)<br/iU", $element->find('.maincont td', 1)->innertext, $output_author);
                $authors = [];
                list($output_author, $authors) = Parser::getTextFromLinks($output_author, $authors);
                // scenarist
                preg_match("/<b>Сценарий<\\/b>(.*)<br/iU", $element->find('.maincont td', 1)->innertext, $output_scenarist);
                $scenarist = [];
                list($output_scenarist, $scenarist) = Parser::getTextFromLinks($output_scenarist, $scenarist);
                //postscoring
                preg_match("/<b>Озвучка<\\/b>:\\s([a-zA-Zа-яА-Я].+)\\s/iU", $element->find('.maincont td', 1)->innertext, $output_postscoring);
                //online
                preg_match("/<b>Онлайн<\\/b>:(.*)<br/iU", $element->find('.maincont td', 1)->innertext, $output_online);
                $online = (isset($output_online[1]) && trim($output_online[1]) == 'да') ? true : false;
                //torrent
                preg_match("/<b>Трекер<\\/b>.*&nbsp;(.*)&nbsp;/iU", $element->find('.maincont td', 1)->innertext, $output_torrent);
                $torrent = (isset($output_torrent[1]) && trim($output_torrent[1]) == 'да') ? true : false;
                // studio
                preg_match("/<b>Студия<\\/b>:(.*)<br/iU", $element->find('.maincont td', 1)->innertext, $output_studio);
                $studio = '';
                if (isset($output_studio[1])) {
                    $result_html = new Htmldom($output_studio[1]);
                    if ($result_html && $result_html->find('a', 0)) {
                        $studio_array = explode('/', $result_html->find('a', 0)->href);
                        array_pop($studio_array); // empty item
                        $studio = str_replace('+', ' ', array_pop($studio_array));
                        $studio = str_replace('A 1', 'A-1', $studio); // A-1 studio name fix
                        $studio = str_replace('J C', 'J.C.', $studio); // J.C. studio name fix
                    }

                }
                // get movie from db
                $movie = Movie::firstOrCreate(['movie_id' => $id]);
                $movie->movie_id = $id;
                $movie->title = $title;
                $movie->service = 'animeland';
                $info = array(
                    'published_at' => $date,
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
        $cachedHtml = $this->getCachedFullPage('animeland_show_' . $movieId, $movieId);
        $html = new Htmldom($cachedHtml);
        //description
        $description = $html->find('.fullstory .maincont div[style="text-align: justify;"]', 0)->plaintext;
        $description = str_replace('Описание:', '', $description);
        $description = str_replace('Справка', '', $description);
        //screenshots
        $screenshots = array();
        foreach ($html->find('.fullstory .maincont div[align="center"] a[onclick="return hs.expand(this)"]') as $screen) {
            $screen_item = array(
                'thumbnail' => env('BASE_URL_ANIMELAND') . $screen->find('img', 0)->src,
                'original' => $screen->href
            );
            array_push($screenshots, $screen_item);
        }
        //files
        $files = array();
        foreach ($html->find('.fullstory div.maincont a[href*=aniplay]') as $file) {
            preg_match("/javascript:aniplay\\('(.*)','link(\\d+)'\\)/iU", $file->href, $output_file);
            $part = $output_file[2];
            $link = $output_file[1];

            $file_item = array(
                'service' => Parser::getVideoService($link),
                'part' => $part,
                'original_link' => $link,
                'download_link' => Parser::createDownloadLink($link)
            );
            array_push($files, $file_item);
        }
        $grouped_files_ = Arrays::group($files, function ($value) {
            return $value['part'];
        });
        $grouped_files = Arrays::values($grouped_files_);

        //load movie from db
        $movie = Movie::firstOrCreate(['movie_id' => $movieId]);
        $movie->title = trim($html->find('h1.heading #news-title', 0)->plaintext);
        $movie->description = trim(nl2br($description));

        $info = is_object($movie->info) ? $movie->info : new \stdClass();
        $info->screenshots = $screenshots;
        $info->files = $grouped_files;
        $movie->info = $info;
        $movie->save();

        $html->clear();
        unset($html);

        return response()->json(array(
            'status' => 'success',
            'movie' => $movie,
        ), 200);
    }

    public function comments($movieId)
    {
        $comments = array();

        $cachedHtml = $this->getCachedFullPage('animeland_show_' . $movieId, $movieId);
        $html = new Htmldom($cachedHtml);
        // create comment url
        $latest_page = ($html->find('.basenavi .navigation a', -1) !== null) ? $html->find('.basenavi .navigation a', -1)->innertext : null;
        //clear html
        $html->clear();
        unset($html);

        //fetch all comments pages
        $n = $latest_page ? $latest_page : 1;
        for ($i = 1; $i <= $n; $i++) {
            $url = sprintf('http://animeland.su/engine/ajax/comments.php?cstart=%d&news_id=%d&skin=AnimeLand', $i, $movieId);

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

                $body = $comment_item->find('div[id^=comm-id]', 0)->innertext;
                $body_text = $comment_item->find('div[id^=comm-id]', 0)->plaintext;
                $comment = array(
                    'comment_id' => $commentId,
                    'date' => $comment_item->find('.comhead ul>li.first', 0)->plaintext,
                    'auhor' => $comment_item->find('h3 a', 0)->plaintext,
                    'body' => [
                        'plain' => $body_text,
                        'html' => $body
                    ],
                    'avatar' => $comment_item->find(".avatarbox > img", 0)->src
                );
                array_push($comments, $comment);
            }
        }

        //get movie from db
        $movie = Movie::firstOrCreate(['movie_id' => $movieId]);
        $info = is_object($movie->info) ? $movie->info : new \stdClass();
        $info->comments = isset($info->comments) ? $info->comments : new \stdClass();
        $info->comments->list = $comments;
        $movie->info = $info;
        $movie->save();

        return response()->json(array(
            'status' => 'success',
            'movie' => $movie,
        ), 200);
    }


    /**
     * Get cached page
     *
     * @param string $cache_key Unique key for cache
     * @param integer $page Page to parse
     * @return mixed response
     */
    private function getCachedPage($cache_key, $page, $path)
    {
        return Cache::remember($cache_key, env('PAGE_CACHE_MIN'), function () use ($page, $path) {
            $url = isset($path) ? $path . 'page/' . $page . '/' : '/page/' . $page . '/';
            $client = new Client(array(
                'base_uri' => env('BASE_URL_ANIMELAND')
            ));
            $response = $client->get($url);
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
        return Cache::remember($cache_key, env('PAGE_CACHE_MIN'), function () use ($movieId) {
            $client = new Client(array(
                'base_uri' => env('BASE_URL_ANIMELAND')
            ));
            $response = $client->get('/index.php?newsid=' . $movieId);
            $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
            unset($client);
            return $responseUtf8;
        });
    }


}
