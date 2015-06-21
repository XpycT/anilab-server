<?php

namespace App\Http\Controllers\Api\v1\animeland;

use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Yangqi\Htmldom\Htmldom;
use Yangqi\Htmldom\Htmldomnode;

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

        $html = Cache::remember('animeland_page_' . $page, env('PAGE_CACHE_MIN'), function () use ($page) {
            $client = new Client(array(
                'base_uri' => env('BASE_URL_ANIMELAND')
            ));
            $response = $client->get('/page/' . $page);
            $responseUtf8 = mb_convert_encoding($response->getBody(true), 'utf-8', 'cp1251');
            return $responseUtf8;
        });

        $html = new Htmldom($html);
        $items = [];
        foreach ($html->find('#dle-content .base') as $element){
           if($element->find('.bheading',0)){
               $id = mb_split('-',$element->find('.ratebox > div',0)->id)[2];
               $title = $element->find('h1.heading a',0)->plaintext;
               $date = $element->find('.headinginfo .date a',0)->plaintext;
               $comment_count = $element->find('.bmid .bmore .arg a',0)->plaintext;
               $image_small = $element->find('.maincont a[onclick="return hs.expand(this)"] img',0)->src;
               $image_original = $element->find('.maincont a[onclick="return hs.expand(this)"]',0)->href;

               $item = array(
                   'id'=>$id,
                   'title'=>$title,
                   'date'=>$date,
                   'comments'=>$comment_count,
                   'images'=>array(
                       'small'=>$image_small,
                       'original'=>$image_original
                   )
               );
                array_push($items,$item);
           }
        }
        return response()->json(array(
            'status' => 'success',
            'page'=>$page,
            'items'=>$items
        ),
            200
        );//->setTtl(30);
    }

}
