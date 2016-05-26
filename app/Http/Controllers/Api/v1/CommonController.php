<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

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
}
