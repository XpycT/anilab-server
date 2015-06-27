<?php

namespace app\Helpers;


use Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
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
        } else if (strrpos($original_link, "myvi.ru") !== false) {
            $service_name = 'myvi';
        } else if (strrpos($original_link, "rutube.ru") !== false) {
            $service_name = 'rutube';
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
            case 'rutube':
                $download_link = false;
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
                $download_link = Cache::remember($original_link, env('PAGE_CACHE_MIN'), function () use ($original_link) {
                    $client = new Client();
                    $response = $client->get($original_link);
                    $body = $response->getBody(true);
                    preg_match("/var vars = ({.*})/i", $body, $output_html);
                    if (isset($output_html[1])) {
                        $jsonArray = json_decode($output_html[1], true);
                        $download_link = array(
                            '240' => isset($jsonArray['url240']) ? $jsonArray['url240'] : false,
                            '360' => isset($jsonArray['url360']) ? $jsonArray['url360'] : false,
                            '480' => isset($jsonArray['url480']) ? $jsonArray['url480'] : false,
                            '720' => isset($jsonArray['url720']) ? $jsonArray['url720'] : false
                        );
                    } else {
                        $download_link = false;
                    }

                    unset($client);
                    return $download_link;
                });

                break;

            case 'sibnet':
                $download_link = Cache::remember($original_link, env('PAGE_CACHE_MIN'), function () use ($original_link) {
                    try {
                        $client = new Client();
                        $response = $client->get($original_link);
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