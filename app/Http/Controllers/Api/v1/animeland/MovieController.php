<?php

namespace App\Http\Controllers\Api\v1\animeland;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Cache;
use GuzzleHttp\Client;
use Yangqi\Htmldom\Htmldom;

class MovieController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param int $page
     * @return Response
     */
    public function page($page = 1)
    {

        $html = $this->getCachedPage('animeland_page_' . $page,$page,env('BASE_URL_ANIMELAND'));

        $html = new Htmldom($html);
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
                preg_match("/\[<a.*>(\d+)<\/a>\]/", $element->find('.maincont',0)->innertext, $output_year);
                //production
                preg_match("/<b>Производство<\/b>.*<img.*>(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_production);
                //type
                preg_match("/<b>Тип<\/b>:(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_type);
                // gerne
                preg_match("/<b>Жанр<\/b>(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_genres);
                $genres = [];
                list($output_genres, $genres) = $this->get_sublink_text($output_genres, $genres);
                //aired
                preg_match("/<b>Выпуск<\/b>:(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_aired);
                // producers
                preg_match("/<b>Режиссёр<\/b>(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_producers);
                $producers = [];
                list($output_producers, $producers) = $this->get_sublink_text($output_producers, $producers);
                // scenarist
                preg_match("/<b>Автор оригинала<\/b>(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_scenarist);
                if(!isset($output_scenarist[1]))
                {
                    preg_match("/<b>Сценарий<\/b>(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_scenarist);
                }
                $scenarist = [];
                list($output_scenarist, $scenarist) = $this->get_sublink_text($output_scenarist, $scenarist);
                //postscoring
                preg_match("/<b>Озвучка<\/b>:\s([a-zA-Zа-яА-Я].+)\s/iU", $element->find('.maincont td',1)->innertext, $output_postscoring);
                //online
                preg_match("/<b>Онлайн<\/b>:(.*)<br/iU", $element->find('.maincont td',1)->innertext, $output_online);
                $online = (isset($output_online[1]) && trim($output_online[1]) == 'да')?true:false;

                //torrent
                preg_match("/<b>Трекер<\/b>.*&nbsp;(.*)&nbsp;/iU", $element->find('.maincont td',1)->innertext, $output_torrent);
                $torrent = (isset($output_torrent[1]) && trim($output_torrent[1]) == 'да')?true:false;

                $item = [
                    'id' => $id,
                    'title' => $title,
                    'published_at' => $date,
                    'comments' => [
                        'count' => $comment_count
                    ],
                    'images' => [
                        'small' => $image_small,
                        'original' => $image_original
                    ],
                    'info'=>[
                        'year'=>(isset($output_year[1]))?$output_year[1]:'',
                        'production'=>(isset($output_production[1]))?trim($output_production[1]):'',
                        'genres'=> $genres,
                        'type'=>(isset($output_type[1]))?trim($output_type[1]):'',
                        'aired'=>(isset($output_aired[1]))?trim($output_aired[1]):'',
                        'producers'=>$producers,
                        'scenarist'=>$scenarist,
                        'postscoring'=>(isset($output_postscoring[1]))?$output_postscoring[1]:'',
                        'online'=>$online,
                        'torrent'=>$torrent
                    ]
                ];
                array_push($items, $item);
            }
        }
        $html->clear();
        unset($html);

        return response()->json(array(
            'status' => 'success',
            'page' => $page,
            'items' => $items
        ),
            200
        );//->setTtl(30);
    }
    /**
     * Get cached page
     *
     * @param string $cache_key Unique key for cache
     * @param integer $page Page to parse
     * @param string $base_url base url
     * @return mixed response
     */
    public function getCachedPage($cache_key,$page,$base_url)
    {
        return Cache::remember($cache_key, env('PAGE_CACHE_MIN'), function () use ($page,$base_url) {
            $client = new Client(array(
                'base_uri' => $base_url
            ));
            $response = $client->get('/page/' . $page);
            $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
            return $responseUtf8;
        });
    }

    /**
     * @param $output_array
     * @param $result
     * @return array
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
