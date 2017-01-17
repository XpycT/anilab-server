<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Yangqi\Htmldom\Htmldom;

class CommonController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function parseLink(Request $request)
    {
        $link = $request->input('link');
        $download_link= Helpers\Parser::createDownloadLink($link);

        return response()->json([
            'original_link' => $link,
            'download_link'=> $download_link
        ],200);
    }

    public function parseVkLink(Request $request)
    {
        $link = $request->input('link');
        $client = new Client();
        $response = $client->get($link);
        $html = new Htmldom($response->getBody(true));
        preg_match( "/var\\svars\\s=(.*)[;]/iU", $html->innertext, $matches);

        $output = array();
        if(count($matches) > 1){
            preg_match("/\"url240\":\"(.+)\",/iU",$matches[1],$url_match);
            if(count($url_match) > 1) $output["url240"] = str_replace("\/","/",$url_match[1]);

            preg_match("/\"url360\":\"(.+)\",/iU",$matches[1],$url_match);
            if(count($url_match) > 1) $output["url360"] = str_replace("\/","/",$url_match[1]);

            preg_match("/\"url480\":\"(.+)\",/iU",$matches[1],$url_match);
            if(count($url_match) > 1) $output["url480"] = str_replace("\/","/",$url_match[1]);

            preg_match("/\"url720\":\"(.+)\",/iU",$matches[1],$url_match);
            if(count($url_match) > 1) $output["url720"] = str_replace("\/","/",$url_match[1]);

            preg_match("/\"url1080\":\"(.+)\",/iU",$matches[1],$url_match);
            if(count($url_match) > 1) $output["url1080"] = str_replace("\/","/",$url_match[1]);

        }

        return response()->json($output,200);
    }

    public function moonwalkUrl(Request $request){
        $link = $request->get('url');
        return Helpers\Parser::createDownloadLink($link);
    }
}
