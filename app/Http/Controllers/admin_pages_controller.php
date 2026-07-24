<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;

class admin_pages_controller extends Controller
{
    public function login_page(Request $request)
    {
        if(Session::has('user')){
            return redirect('/admin/dashboard');
        }
        return view('erp.login');
    }
    public function dashboard_page(Request $request)
    {
        return view('erp.dashboard');
    }
    public function users_page(Request $request)
    {
        return view('erp.users');
    }
    public function clients_page(Request $request)
    {
        return view('erp.clients');
    }
    public function employees_page(Request $request)
    {
        return view('erp.employees');
    }
    public function providers_page(Request $request)
    {
        return view('erp.providers');
    }
    public function departments_page(Request $request)
    {
        return view('erp.departments');
    }
    public function licenses_page(Request $request)
    {
        return view('erp.licenses');
    }
    public function incomes_page(Request $request)
    {
        return view('erp.incomes');
    }
    public function panel_payment_gateway(Request $request)
    {
        return redirect('/admin/incomes');
    }
    public function outcomes_page(Request $request)
    {
        return view('erp.outcomes');
    }
    public function reports_page(Request $request)
    {
        return view('erp.reports');
    }
    public function web_page_page(Request $request)
    {
        return view('erp.web-pages');
    }
    public function my_profile_page(Request $request)
    {
        return view('erp.my-profile');
    }
    public function chat_page(Request $request)
    {
        return view('erp.chat');
    }
    public function approve_blog_page(Request $request)
    {
        $unique_id = $request->unique_id;
        return view('erp.approve_blog', compact('unique_id'));
    }
    public function approve_instagram_post_page(Request $request)
    {
        $unique_id = $request->unique_id;
        return view('erp.approve_instagram_post', compact('unique_id'));
    }
    public function approve_facebook_post_page(Request $request)
    {
        $unique_id = $request->unique_id;
        return view('erp.approve_facebook_post', compact('unique_id'));
    }
    public function approve_linkedin_post_page(Request $request)
    {
        $unique_id = $request->unique_id;
        return view('erp.approve_linkedin_post', compact('unique_id'));
    }
    public function approve_twitter_post_page(Request $request)
    {
        $unique_id = $request->unique_id;
        return view('erp.approve_twitter_post', compact('unique_id'));
    }
    function reset_password_panel(Request $request){
        return view('erp.reset_password_panel');
    }
    public function ia_assistant_page(Request $request)
    {
        return view('erp.ia_assistant');
    }
    public function ia_marketing_report_page(Request $request)
    {
        return view('erp.ia_marketing_report');
    }
}
