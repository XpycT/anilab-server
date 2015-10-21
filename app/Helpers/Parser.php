<?php

namespace app\Helpers;


use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use SimpleXMLElement;
use Yangqi\Htmldom\Htmldom;

class Parser
{

    /**
     * Get video service name from url
     *
     * @param string $original_link Url
     * @return mixed service name
     */
    public static function getVideoService($original_link)
    {
        $service_name = '';
        if (strrpos($original_link, "24video") !== false) {
            $service_name = '24video';
        } else if (strrpos($original_link, "vk.com") !== false || strrpos($original_link, "vkontakte.ru") !== false) {
            $service_name = 'vk';
        } else if (strrpos($original_link, "sibnet") !== false) {
            $service_name = 'sibnet';
        } else if (strrpos($original_link, "kivvi.kz") !== false || strrpos($original_link, "kiwi.kz") !== false) {
            $service_name = 'kivvi';
        } else if (strrpos($original_link, "myvi.ru") !== false || strrpos($original_link, "myvi.tv") !== false) {
            $service_name = 'myvi';
        } else if (strrpos($original_link, "rutube.ru") !== false) {
            $service_name = 'rutube';
        } else if (strrpos($original_link, "moonwalk.cc") !== false) {
            $service_name = 'moonwalk';
        } else if (strrpos($original_link, "player.adcdn.tv") !== false) {
            $service_name = 'anidub';
        }

        return $service_name;
    }

    /**
     * Create download link from video service
     *
     * @param string $original_link Url to video service
     * @return mixed download url
     */
    public static function createDownloadLink($original_link)
    {
        $download_link = '';
        switch (Parser::getVideoService($original_link)) {
            case 'myvi':
                //TODO можно сделать! ....
                // http://myvi.ru/player/embed/html/o234L90Q3B_isAKmDzm9K3u9fM4KlFjmSR9v9ep5PCyYIBzb3DA63fWcfLCGYBlqv0
                // http://myvi.ru/ru/flash/player/pre/ortLkmoLoXbrpAZca9OyMgvpB7XBC4L-YCoqPldXUWJEVRRHGSb2JhoZP4Ta_NE1l0
                $tmp_link = str_replace('.tv','.ru',$original_link);
                $tmp_link = str_replace('.tv','.ru',$original_link);
                $tmp_link = str_replace('/embed/html/','/player/api/Video/Get/',$tmp_link);
                $tmp_link = str_replace('/player/flash/','/player/api/Video/Get/',$tmp_link);
                $tmp_link = str_replace('/ru/flash/player/pre/','/player/api/Video/Get/',$tmp_link);
                $tmp_link = str_replace('/ru/','/',$tmp_link);
                $tmp_link = str_replace('/player/player/','/player/',$tmp_link);
                $tmp_link = str_replace('//','http://',$tmp_link);
                $tmp_link = str_replace('http:http','http',$tmp_link);
                //$tmp_link = $tmp_link.'?sig=1';
                /*$client = new Client();
                //get page with player
                $response = $client->get($tmp_link);
                $json = $response->getBody();
                $jsonResponse = json_decode($response->getBody(true));
                $download_link = $jsonResponse->sprutoData->playlist[0]->video[0]->url;*/
                $download_link = $tmp_link;
                break;
            case 'rutube':
                $download_link = "";
                $tmp_link = str_replace('//rutube.ru/play/embed/','',$original_link);
                $tmp_link = str_replace('http:','',$tmp_link);
                $tmp_link = str_replace('//video.rutube.ru/','',$tmp_link);
                // https://rutube.ru/api/play/trackinfo/7888798/?sqr4374_compat=1
                $download_link_page = sprintf('https://rutube.ru/api/play/trackinfo/%s/?sqr4374_compat=1', $tmp_link);
                try {
                    $client = new Client();
                    $response = $client->get($download_link_page);
                    $str = json_decode($response->getBody(true));
                    $download_link = $str->video_url;
                } catch (ClientException $e) {
                    $response = $e->getResponse();
                    if ($response->getStatusCode() == 404) {
                        $download_link = "";
                    }
                }
                break;
            case 'anidub':
                $download_link = Cache::remember(md5($original_link), env('PAGE_CACHE_MIN'), function () use ($original_link) {
                    $client = new Client();
                    //get page with player
                    $response = $client->get($original_link);
                    $html = new Htmldom($response->getBody(true));
                    preg_match("/file: '(.*.m3u8.*)',/iU", $html, $m3u8_array);
                    if (isset($m3u8_array[1])) {
                        return $m3u8_array[1];
                    } else {
                        return false;
                    }
                    $html->clear();
                    unset($html);
                });
                break;
            case 'moonwalk':
                // fix link
                $link = explode('|', $original_link)[0];
                $download_link = str_replace('iframe', 'index.m3u8?cd=1', $link);

                /*$download_link = Cache::remember(md5($link), env('PAGE_CACHE_MIN'), function () use ($link) {
                    $client = new Client();
                    //get page with player
                    $response = $client->get($link);
                    $html = new Htmldom($response->getBody(true));
                    preg_match("/video_token: '(.*)'/iU", $html, $token_array);
                    if (isset($token_array[1])) {
                        preg_match("/access_key: '(.*)'/iU", $html, $access_array);
                        if (isset($access_array[1])) {
                            $response = $client->post('http://moonwalk.cc/sessions/create_session', [
                                'form_params' => [
                                    'video_token' => $token_array[1],
                                    'access_key' => $access_array[1],
                                    'cd' => 1
                                ]]);
                            $jsonResponse = json_decode($response->getBody(true));
                            return $jsonResponse->manifest_m3u8;
                        }
                    }else{
                        return false;
                    }
                    $html->clear();
                    unset($html);
                });*/

                break;
            case 'kivvi':
                $parts = explode('/', $original_link);
                array_pop($parts);
                $videoId = array_pop($parts);
                $client = new Client();
                $response = $client->post('http://kivvi.kz/services/watch/download', [
                    'form_params' => [
                        'hash' => $videoId
                    ]]);
                $jsonResponse = json_decode($response->getBody(true));
                $download_link = $jsonResponse->resources->url;
                break;
            case '24video':
                //get url
                $parts = explode('/', $original_link);
                $videoId = array_pop($parts);
                //get download page
                $download_link_page = sprintf('http://www.24video.com/video/download2/%d?type=mp4', $videoId);
                // get file link (1day)
                $download_link = Cache::remember($download_link_page, env('PAGE_CACHE_MIN'), function () use ($download_link_page) {
                    $client = new Client();
                    $response = $client->get($download_link_page);
                    $html = new Htmldom($response->getBody(true));
                    $download_link = $html->find('a#link', 0)->href;
                    unset($html);
                    unset($client);
                    return $download_link;
                });
                break;
            case 'vk':
                /*$client = new Client();
                $response = $client->get($original_link);
                //$body = $response->getBody(true);
                $body = mb_convert_encoding($response->getBody(true), 'utf-8', 'auto');
                preg_match("/var vars = ({.*})/i", $body, $output_html);
                if (isset($output_html[1])) {
                    $jsonArray = json_decode($output_html[1], true);
                    // dd($output_html);
                    $download_link = array(
                        '240' => isset($jsonArray['url240']) ? $jsonArray['url240'] : false,
                        '360' => isset($jsonArray['url360']) ? $jsonArray['url360'] : false,
                        '480' => isset($jsonArray['url480']) ? $jsonArray['url480'] : false,
                        '720' => isset($jsonArray['url720']) ? $jsonArray['url720'] : false
                    );
                } else {
                    $download_link = false;
                }

                unset($client);*/
                $query_string = parse_url($original_link,PHP_URL_QUERY);
                parse_str($query_string, $get_array);
                if(isset($get_array['oid']) && isset($get_array['id'])){
                    $download_link = sprintf('http://vk.com/video.php?act=a_flash_vars&vid=%s_%s',$get_array['oid'],$get_array['id']);
                }else{
                    $download_link = $original_link;
                }

                break;

            case 'sibnet':
                // need http://video.sibnet.ru/video1844166 || http://video.sibnet.ru/shell.swf?videoid=1086484
                $fix_url = str_replace('shell.swf?videoid=','video',$original_link);
                $download_link = Cache::remember($fix_url, env('PAGE_CACHE_MIN'), function () use ($fix_url) {
                    try {
                        $client = new Client();
                        $response = $client->get($fix_url);
                        $body = $response->getBody(true);
                        preg_match("/'file' : '(.*)m3u8',/iU", $body, $output_html);
                        $download_link = (isset($output_html[1])) ? 'http://video.sibnet.ru' . $output_html[1] . 'mp4' : false;
                    } catch (ClientException $e) {
                        if ($e->getResponse()->getStatusCode() == 404) {
                            $download_link = false;
                        }
                    }
                    unset($client);
                    return $download_link;
                });

                break;
        }
        return $download_link;
    }

    /**
     * Get sublink from descriptions
     *
     * @param array $output_array html parsed object
     * @param array $result empty array for model
     * @return array result array
     */
    public static function getTextFromLinks($output_array, $result)
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