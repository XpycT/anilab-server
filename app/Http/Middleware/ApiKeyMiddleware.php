<?php

namespace App\Http\Middleware;

use Closure;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * com.xpyct.apps.anilab
         * 893c35e9d980d872836b118577b69e9d
         */
        if($request->header('key') != env('API_KEY')){
            return response()->json([
                'status'=>'пум пурум пум пум пурум пум....'
            ],401);
        }
        return $next($request);
    }
}
