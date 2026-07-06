<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

use Session;

class client_pages_controller extends Controller
{
    public function client_login_page(Request $request)
    {
        if(Session::has('client_user')){
            return redirect('/client/payments');
        }
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.login', ['header_menu_view' => $header_menu_view]);
    }
    public function client_register_page(Request $request)
    {
        if(Session::has('client_user')){
            return redirect('/client/payments');
        }
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.register', ['header_menu_view' => $header_menu_view]);
    }
    public function client_user_set_password_page(Request $request)
    {
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.user_set_password', ['header_menu_view' => $header_menu_view]);
    }
    public function client_dashboard_page(Request $request)
    {
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.dashboard', ['header_menu_view' => $header_menu_view]);
    }
    public function client_user_profile_page(Request $request)
    {
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.user_profile', ['header_menu_view' => $header_menu_view]);
    }
    public function client_companies_page(Request $request)
    {
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.companies', ['header_menu_view' => $header_menu_view]);
    }
    public function client_users_page(Request $request)
    {
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.users', ['header_menu_view' => $header_menu_view]);
    }
    public function client_pay_page_unlogged(Request $request)
    {
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.pay_unlogged', ['header_menu_view' => $header_menu_view, 'income_unique_id' => $request->unique_id]);
    }
    public function client_payment_response_page_unlogged(Request $request)
    {
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.payment_response_unlogged', ['header_menu_view' => $header_menu_view, 'unique_id' => $request->unique_id]);
    }
    public function licenses_page(Request $request){
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.licenses', ['header_menu_view' => $header_menu_view]);
    }
    public function incomes_page(Request $request){
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.incomes', ['header_menu_view' => $header_menu_view]);
    }
    public function traceability_page(Request $request){
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.traceability', ['header_menu_view' => $header_menu_view]);
    }
    function unsubscribe_blog(Request $request){
        $header_menu_view = View::make('client.layouts.menu')->render();
        return view('client.unsubscribe_blog', ['unique_id' => $request->unique_id, 'header_menu_view' => $header_menu_view]);
    }
}
