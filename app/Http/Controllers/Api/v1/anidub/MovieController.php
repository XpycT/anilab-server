<?php

namespace App\Http\Controllers\Api\v1\anidub;

use app\Helpers;
use App\Helpers\Parser;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Movie;
use Cache;
use GuzzleHttp\Client;
use Request;
use Underscore\Types\Arrays;
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
        // generate key
        $path = Request::input('path');
        $key = isset($path) ? 'anidub_' . str_replace('/', '_', $path) . '_page_' . $page : 'anidub_page_' . $page;
        // get page or cache
//        dd($key);
        $cachedHtml = $this->getCachedPage($key, $page, $path);
        $html = new Htmldom($cachedHtml);
        // parse html
        $items = [];
        foreach ($html->find('#dle-content .news_short') as $element) {
            if ($element->find('.maincont ul li span a', 0)) {
                $id = mb_split('-', $element->find('div[id^=news-id]', 0)->id)[2];
                $data_original = 'data-original';
                $title = $element->find('.poster_img img', 0)->alt;
                $date = '';//$element->find('.headinginfo .date a', 0)->plaintext;
                $comment_count = trim(mb_split(':', $element->find('.newsfoot li a', 0)->plaintext)[1]);
                $image_small = $element->find('.poster_img img', 0)->$data_original;
                $image_original = $element->find('.poster_img img', 0)->$data_original;

                $description = $element->find('div[id^=news-id]', 0)->plaintext;

                // year
                $year = $element->find('.maincont ul li span a', 0)->plaintext;
                //production
                preg_match("/<b>Страна: <\\/b><span>(.*)<\\/span>/iU", $element->find('.maincont ul', 0)->innertext, $output_production);
                // series count
                preg_match("/<b>Количество серий: <\\/b><span>(.*)<\\/span>/iU", $element->find('.maincont ul', 0)->innertext, $output_series);
                // gerne
                $genres = [];
                foreach ($element->find('span[itemprop="genre"] a') as $item) {
                    $genres[] = $item->plaintext;
                }
                //aired
                preg_match("/<b>Дата выпуска: <\\/b><span>(.*)<\\/span>/iU", $element->find('.maincont ul', 0)->innertext, $output_aired);
                // producers
                preg_match("/<b>Режиссёр<\\/b>(.*)<br/iU", $element->find('.maincont ul', 0)->innertext, $output_producers);
                $producers = [];
                foreach ($element->find('li[itemprop="director"] span a') as $item) {
                    $producers[] = $item->plaintext;
                }
                // author
                $authors = [];
                foreach ($element->find('li[itemprop="author"] span a') as $item) {
                    $authors[] = $item->plaintext;
                }
                //postscoring
                preg_match("/<b>Озвучивание: <\\/b><span>(.*)<\\/span>/iU", $element->find('.maincont ul', 0)->innertext, $output_postscoring_tmp);
                preg_match_all("/<a.*>(.*)<\\/a>/iU", $output_postscoring_tmp[1], $output_postscoring);
                // studio
                $studio = $element->find('.video_info a img', 0) ? $element->find('.video_info a img', 0)->alt : false;
                // get movie from db
                $movie = Movie::firstOrCreate(['movie_id' => $id]);
                $movie->movie_id = $id;
                $movie->description = $description;
                $movie->title = $title;
                $movie->service = 'anidub';
                $info = array(
                    'published_at' => $date,
                    'images' => array(
                        'thumbnail' => $image_small,
                        'original' => $image_original
                    ),
                    'year' => $year,
                    'production' => (isset($output_production[1])) ? trim($output_production[1]) : '',
                    'genres' => $genres,
                    'series' => (isset($output_series[1])) ? trim($output_series[1]) : '',
                    'aired' => (isset($output_aired[1])) ? trim($output_aired[1]) : '',
                    'producers' => $producers,
                    'authors' => $authors,
                    'postscoring' => (isset($output_postscoring[1])) ? $output_postscoring[1] : '',
                    'studio' => $studio,
                    'online' => true,
                    'torrent' => false
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
        $cachedHtml = $this->getCachedFullPage('anidub_show_' . $movieId, $movieId);
        $html = new Htmldom($cachedHtml);
        //description
        $html->find('div[itemprop="description"] div[id^="news-id-"]', 0)->outertext = '';
        $html->find('div[itemprop="description"] div[id^="news-id-"]', 0)->innertext = '';
        $html->save();
        $description = $html->find('div[itemprop="description"]', 0)->plaintext;
        $description = str_replace('Описание:', '', $description);
        $description = str_replace('Справка', '', $description);

        //screenshots
        $screenshots = array();
        foreach ($html->find('.screens a[onclick="return hs.expand(this)"]') as $screen) {
            $screen_item = array(
                'thumbnail' => $screen->find('img', 0)->src,
                'original' => $screen->href
            );
            array_push($screenshots, $screen_item);
        }
        //files
        $files = array();
        foreach ($html->find('select[id^=sel] option') as $file) {

            $part = $file->plaintext;
            $link = $file->value;
            //fix vk link
            $link = explode('|', $link)[0];
            $link = str_replace('pp.anidub-online.ru/video_ext.php', 'vk.com/video_ext.php', $link);


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
        $movie->title = trim($html->find('h1.titlfull', 0)->plaintext);
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

        $cachedHtml = $this->getCachedFullPage('anidub_show_' . $movieId, $movieId);
        $html = new Htmldom($cachedHtml);
        // create comment url
        $latest_page = ($html->find('.dle-comments-navigation .navigation a', -1) !== null) ? $html->find('.dle-comments-navigation .navigation a', -1)->innertext : null;
        //clear html
        $html->clear();
        unset($html);

        //fetch all comments pages
        $n = $latest_page ? $latest_page : 1;
//        for ($i = 1; $i <= $n; $i++) {
        for ($i = $n; $i > 0; $i--) {
            $url = sprintf('http://online.anidub.com/engine/ajax/comments.php?cstart=%d&news_id=%d&skin=Anidub_online', $i, $movieId);

            $response_json = Cache::remember(md5($url), env('PAGE_CACHE_MIN'), function () use ($url) {
                $client = new Client();
                $response = $client->get($url);
                $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'auto');
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
                $body_text = str_replace('&nbsp;Комментарий скрыт в связи с низким рейтингом', '', $body_text);
                $comment = array(
                    'comment_id' => $commentId,
                    'date' => $comment_item->find('.comm_inf ul>li', 0)->plaintext,
                    'auhor' => $comment_item->find('.comm_title a', 0)->plaintext,
                    'body' => [
                        'plain' => $body_text,
                        'html' => $body
                    ],
                    'avatar' => $comment_item->find(".commcont center > img", 0)->src
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
                'base_uri' => env('BASE_URL_ANIDUB')
            ));
            $response = $client->get($url);
            $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'auto');
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
                'base_uri' => env('BASE_URL_ANIDUB')
            ));
            $response = $client->get('/index.php?newsid=' . $movieId);
            $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'auto');
            unset($client);
            return $responseUtf8;
        });
    }


}
