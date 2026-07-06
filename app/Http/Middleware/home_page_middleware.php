<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class home_page_middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        //check if base url is .env APP_HOME_PAGE_URL url
        $url = $request->header('origin');
        if(strpos($url, '//ridder.com.co') !== false || strpos($url, '//www.ridder.com.co') !== false || strpos($url, 'localhost') !== false )
        {
            return $next($request);
        }
        //return unauthorized
        return response()->json(['message' => 'Unauthorized'], 401);
    }
}
