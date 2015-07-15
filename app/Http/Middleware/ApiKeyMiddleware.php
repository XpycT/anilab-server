<?php

namespace App\Http\Middleware;

use App\Token;
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
        if(!$request->header('x-api-key')){
            return response()->json([
                'status'=>'Unauthorized'
            ],401);
        }else{
            $token  = Token::hasPublicKey($request->header('x-api-key'))->first();
            if(!$token){
                return response()->json([
                    'status'=>'Forbidden'
                ],403);
            }
        }
        return $next($request);
    }
}
