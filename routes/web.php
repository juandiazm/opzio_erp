<?php

//Admin
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin_pages_controller;
use App\Http\Controllers\users_controller;
use App\Http\Controllers\clients_controller;
use App\Http\Controllers\client_ensamble_controller;
use App\Http\Controllers\providers_controller;
use App\Http\Controllers\employees_controller;
use App\Http\Controllers\eps_controller;
use App\Http\Controllers\arl_controller;
use App\Http\Controllers\afp_controller;
use App\Http\Controllers\country_controller;
use App\Http\Controllers\sector_controller;
use App\Http\Controllers\departments_controller;
use App\Http\Controllers\service_controller;
use App\Http\Controllers\licenses_controller;
use App\Http\Controllers\incomes_controller;
use App\Http\Controllers\income_advances_controller;
use App\Http\Controllers\outcomes_controller;
use App\Http\Controllers\payment_gateway_controller;
use App\Http\Controllers\dashboard_controller;
use App\Http\Controllers\pusher_controller;
use App\Http\Controllers\blog_controller;
use App\Http\Controllers\blog_email_subscriber_controller;
use App\Http\Controllers\instagram_controller;
use App\Http\Controllers\facebook_controller;
use App\Http\Controllers\linkedin_controller;
use App\Http\Controllers\twitter_controller;
use App\Http\Controllers\freepik_controller;
use App\Http\Controllers\report_controller;
//Client
use App\Http\Controllers\client_pages_controller;
use App\Http\Controllers\client_users_controller;
use App\Http\Controllers\income_payment_controller;
use App\Http\Controllers\payment_gateway_wompi_controller;
use App\Http\Controllers\payment_gateway_bold_controller;
//Opzio
use App\Http\Controllers\old_opzio_controller;
use App\Http\Controllers\client_chat_controller;
use App\Http\Controllers\open_ia_controller;
use App\Http\Controllers\ia_assistant_controller;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('', function () {
    return redirect('/admin');
});

// PWA - Serve manifest.json and service-worker.js inline to avoid redirect issues
Route::get('manifest.json', function () {
    return response()->json([
        'name' => 'Opzio ERP',
        'short_name' => 'Opzio',
        'description' => 'Sistema de gestión empresarial Opzio ERP',
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => '#00057B',
        'orientation' => 'portrait-primary',
        'icons' => [
            [
                'src' => '/images/pwa/icon-192x192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/images/pwa/icon-512x512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
});
Route::get('service-worker.js', function () {
    $path = public_path('service-worker.js');
    if (!file_exists($path)) {
        abort(404, 'Service worker not found');
    }
    return response(file_get_contents($path), 200, [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
    ]);
});
//Admin routes
Route::get('Admin', function () {
    return redirect('/admin');
});

// Serve storage PDFs directly — fallback for php artisan serve (doesn't follow NTFS junctions)
// On Apache/WAMP this route is never reached because Apache serves the file from the symlink directly.
Route::get('storage/incomes/pdfs/{filename}', function (string $filename) {
    $filename = basename($filename); // prevent path traversal
    $path = storage_path('app/public/incomes/pdfs/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="' . $filename . '"',
        'Cache-Control'       => 'private, no-store',
    ]);
})->where('filename', '.+\.pdf');
Route::prefix('admin')->group(function () {
    Route::get('/', [admin_pages_controller::class, 'login_page']);
    Route::post('login', [users_controller::class, 'login_user']);
    Route::post('forgot-password', [users_controller::class, 'forgot_password']);
    Route::get('reset-password', [admin_pages_controller::class, 'reset_password_panel']);
    Route::post('reset-password', [users_controller::class, 'reset_password']);
    Route::group(['middleware' => 'admin_middleware'], function(){
        Route::prefix('dashboard')->group(function () {
            Route::get('', [admin_pages_controller::class, 'dashboard_page']);
            Route::post('get-income-outcome-values-by-month', [dashboard_controller::class, 'get_income_outcome_values_by_month']);
            Route::post('get-incomes-by-status', [dashboard_controller::class, 'get_incomes_by_status']);
            Route::post('get-active-clients-and-licenses', [dashboard_controller::class, 'get_active_clients_and_licenses']);
            Route::post('get-incomes-outcomes-by-month-range', [dashboard_controller::class, 'get_incomes_outcomes_by_month_range']);
            Route::post('get-client-licences-dues', [dashboard_controller::class, 'get_client_licences_dues']);
            Route::post('get-new-clients-by-date-range', [dashboard_controller::class, 'get_new_clients_by_date_range']);
            Route::post('get-sales-by-month-range', [dashboard_controller::class, 'get_sales_by_month_range']);
            Route::post('get-incomes-by-client-date-range', [dashboard_controller::class, 'get_incomes_by_client_date_range']);
        });
        Route::prefix('users')->group(function () {
            Route::get('', [admin_pages_controller::class, 'users_page']);
            Route::get('next-id', [users_controller::class, 'next_id']);
            Route::post('add', [users_controller::class, 'add_user']);
            Route::post('update', [users_controller::class, 'update_user']);
            Route::post('get-by-id', [users_controller::class, 'get_user_by_id']);
            Route::post('close-session', [users_controller::class, 'close_session']);
            Route::post('get-page', [users_controller::class, 'get_page']);
            Route::get('all', [users_controller::class, 'get_all_users']);
            Route::post('delete', [users_controller::class, 'delete_user']);
            Route::post('restore', [users_controller::class, 'restore_user']);
            Route::prefix('permissions')->group(function () {
                Route::get('', [users_controller::class, 'users_get_permissions']);
            });
            Route::prefix('traceability')->group(function () {
                Route::post('', [users_controller::class, 'users_get_traceability']);
            });
        });
        Route::prefix('my-profile')->group(function () {
            Route::get('', [admin_pages_controller::class, 'my_profile_page']);
            Route::post('/update', [users_controller::class, 'update_my_profile']);
        });
        Route::prefix('clients')->group(function(){
            Route::get('/', [admin_pages_controller::class, 'clients_page']);
            Route::post('/add', [clients_controller::class, 'add_client']);
            Route::post('/sincronize', [clients_controller::class, 'sincronize_with_siigo']);
            Route::post('/update', [clients_controller::class, 'update_client']);
            Route::post('/get-page', [clients_controller::class, 'get_page']);
            Route::post('/get-all', [clients_controller::class, 'get_all']);
            Route::prefix('users')->group(function(){
                Route::post('add', [client_users_controller::class, 'add_client_user']);
                Route::post('get', [client_users_controller::class, 'get_client_users']);
                Route::get('all', [client_users_controller::class, 'get_all_client_users']);
                Route::post('get-by-client-id', [client_users_controller::class, 'get_client_users_by_client_id']);
                Route::post('update', [client_users_controller::class, 'update_client_user']);
                Route::post('delete', [client_users_controller::class, 'delete_client_user']);
                Route::post('restore', [client_users_controller::class, 'restore_client_user']);
                Route::post('restore-password', [client_users_controller::class, 'restore_client_user_password']);
                Route::prefix('traceability')->group(function () {
                    Route::post('', [client_users_controller::class, 'client_users_get_traceability']);
                });
            });
            Route::prefix('documents')->group(function(){
                Route::post('add', [clients_controller::class, 'add_client_document']);
                Route::post('get', [clients_controller::class, 'get_client_documents']);
                Route::post('update', [clients_controller::class, 'update_client_document']);
                Route::post('delete', [clients_controller::class, 'delete_client_document']);
            });
            Route::prefix('licenses')->group(function(){
                Route::post('get-by-client-id', [licenses_controller::class, 'get_license_by_client_id']);
            });
            
        });
        Route::prefix('employees')->group(function(){
            Route::get('', [admin_pages_controller::class, 'employees_page']);
            Route::post('add', [employees_controller::class, 'add_employee']);
            Route::post('update', [employees_controller::class, 'update_employee']);
            Route::post('delete', [employees_controller::class, 'delete_employee']);
            Route::post('restore', [employees_controller::class, 'restore_employee']);
            Route::post('get-page', [employees_controller::class, 'get_page']);
            Route::post('get-all', [employees_controller::class, 'get_all']);
            Route::post('hiring/update', [employees_controller::class, 'update_employee_hiring']);
            Route::prefix('documents')->group(function(){
                Route::post('add', [employees_controller::class, 'add_employee_document']);
                Route::post('get', [employees_controller::class, 'get_employee_documents']);
                Route::post('update', [employees_controller::class, 'update_employee_document']);
                Route::post('delete', [employees_controller::class, 'delete_employee_document']);
            });
            Route::prefix('licenses')->group(function(){
                Route::post('get-by-employee-id', [licenses_controller::class, 'get_license_by_employee_id']);
                Route::post('update-comission', [licenses_controller::class, 'update_license_comission']);
            });
        });
        Route::prefix('providers')->group(function(){
            Route::get('', [admin_pages_controller::class, 'providers_page']);
            Route::post('add', [providers_controller::class, 'add_provider']);
            Route::post('update', [providers_controller::class, 'update_provider']);
            Route::post('delete', [providers_controller::class, 'delete_provider']);
            Route::post('restore', [providers_controller::class, 'restore_provider']);
            Route::post('get-page', [providers_controller::class, 'get_page']);
            Route::prefix('documents')->group(function(){
                Route::post('add', [providers_controller::class, 'add_provider_document']);
                Route::post('get', [providers_controller::class, 'get_provider_documents']);
                Route::post('update', [providers_controller::class, 'update_provider_document']);
                Route::post('delete', [providers_controller::class, 'delete_provider_document']);
            });
            Route::prefix('contacts')->group(function(){
                Route::post('add', [providers_controller::class, 'add_provider_contact']);
                Route::post('get', [providers_controller::class, 'get_provider_contacts']);
                Route::post('update', [providers_controller::class, 'update_provider_contact']);
                Route::post('delete', [providers_controller::class, 'delete_provider_contact']);
                Route::post('restore', [providers_controller::class, 'restore_provider_contact']);
            });
        });
        Route::prefix('departments')->group(function(){
            Route::get('/', [admin_pages_controller::class, 'departments_page']);
            Route::post('/add', [departments_controller::class, 'add_department']);
            Route::post('/update', [departments_controller::class, 'update_department']);
            Route::post('/get-page', [departments_controller::class, 'get_page']);
            Route::post('/get-all', [departments_controller::class, 'get_all']);
            Route::post('/delete', [departments_controller::class, 'delete_department']);
            Route::post('/restore', [departments_controller::class, 'restore_department']);
        });
        Route::prefix('licenses')->group(function(){
            Route::get('/', [admin_pages_controller::class, 'licenses_page']);
            Route::post('/add', [licenses_controller::class, 'add_license']);
            Route::post('/update', [licenses_controller::class, 'update_license']);
            Route::post('/update-details', [licenses_controller::class, 'update_license_details']);
            Route::post('/get-page', [licenses_controller::class, 'get_page']);
            Route::post('/get-all', [licenses_controller::class, 'get_all']);
            Route::post('/get-by-id', [licenses_controller::class, 'get_license_by_id']);
            Route::post('/delete', [licenses_controller::class, 'delete_license']);
            Route::post('/restore', [licenses_controller::class, 'restore_license']);
            Route::prefix('documents')->group(function(){
                Route::post('add', [licenses_controller::class, 'add_license_document']);
                Route::post('get', [licenses_controller::class, 'get_license_documents']);
                Route::post('update', [licenses_controller::class, 'update_license_document']);
                Route::post('delete', [licenses_controller::class, 'delete_license_document']);
            });
            Route::prefix('notifications')->group(function(){
                Route::post('add', [licenses_controller::class, 'add_license_notification']);
                Route::post('get', [licenses_controller::class, 'get_license_notifications']);
                Route::post('get-by-licenses-ids', [licenses_controller::class, 'get_license_notifications_by_license_ids']);
                Route::post('update', [licenses_controller::class, 'update_license_notification']);
                Route::post('delete', [licenses_controller::class, 'delete_license_notification']);
                Route::post('restore', [licenses_controller::class, 'restore_license_notification']);
                Route::post('change-position', [licenses_controller::class, 'change_license_notification_position']);
            });
        });
        Route::prefix('incomes')->group(function(){
            Route::get('/', [admin_pages_controller::class, 'incomes_page']);
            Route::post('create', [incomes_controller::class, 'create_income']);
            Route::get('download-template', [incomes_controller::class, 'download_template']);
            Route::post('import-massive-quotations', [incomes_controller::class, 'importMassiveQuotations']);
            Route::post('get-page', [incomes_controller::class, 'get_page']);
            Route::post('get-licenses', [incomes_controller::class, 'get_licenses']);
            Route::post('update', [incomes_controller::class, 'update_income']);
            Route::post('send', [incomes_controller::class, 'send_income']);
            Route::post('change-state', [incomes_controller::class, 'change_state']);
            Route::post('change-state-to-pay', [incomes_controller::class, 'change_state_to_pay']);
            Route::post('create-siigo-invoice', [incomes_controller::class, 'create_siigo_invoice']);
            Route::prefix('advances')->group(function () {
                Route::post('create', [income_advances_controller::class, 'create']);
                Route::get('get-by-income/{income_id}', [income_advances_controller::class, 'getByIncome']);
                Route::post('update/{advance_id}', [income_advances_controller::class, 'update']);
                Route::post('delete/{advance_id}', [income_advances_controller::class, 'delete']);
            });
            Route::prefix('payment-gateway')->group(function () {
                Route::get('/',[admin_pages_controller::class, 'panel_payment_gateway']);
                Route::post('get-all',[payment_gateway_controller::class, 'get_all_payment_gateways']);
                Route::post('update',[payment_gateway_controller::class, 'update_payment_gatewate_data']);
            });
        });
        Route::prefix('outcomes')->group(function(){
            Route::get('/', [admin_pages_controller::class, 'outcomes_page']);
            Route::post('import', [outcomes_controller::class, 'import_outcomes']);
            Route::post('get', [outcomes_controller::class, 'get_outcomes']);
            Route::post('delete', [outcomes_controller::class, 'delete_outcome']);
            Route::post('recover', [outcomes_controller::class, 'recover_outcome']);
        });
        Route::prefix('reports')->group(function(){
            Route::get('/', [admin_pages_controller::class, 'reports_page']);
            Route::post('export', [report_controller::class, 'export_report']);
            Route::get('download/{unique_id}', [report_controller::class, 'download_report']);
            Route::get('delete/{unique_id}', [report_controller::class, 'delete_report']);
            Route::prefix('users')->group(function(){
                Route::post('get-by-date-range', [users_controller::class, 'get_users_by_date_range_report']);
            });
            Route::prefix('clients')->group(function(){
                Route::post('get-by-date-range', [clients_controller::class, 'get_clients_by_date_range_report']);
            });
            Route::prefix('employees')->group(function(){
                Route::post('get-by-date-range', [employees_controller::class, 'get_employees_by_date_range_report']);
            });
            Route::prefix('licenses')->group(function(){
                Route::post('get-by-date-range', [licenses_controller::class, 'get_licenses_by_date_range_report']);
            });
            Route::prefix('incomes')->group(function(){
                Route::post('get-by-state-date-range', [incomes_controller::class, 'get_incomes_by_state_date_range_report']);
                Route::post('get-payed-by-date-range', [incomes_controller::class, 'get_incomes_payed_by_date_range_report']);
            });
            Route::prefix('outcomes')->group(function(){
                Route::post('get-by-date-range', [outcomes_controller::class, 'get_outcomes_by_date_range_report']);
            });
        });
        Route::prefix('web-pages')->group(function(){
            Route::get('/', [admin_pages_controller::class, 'web_page_page']);
        });
        Route::prefix('chat')->group(function(){
            Route::get('/', [admin_pages_controller::class, 'chat_page']);
            Route::post('get-client-chats', [client_chat_controller::class, 'get_client_chats']);
            Route::post('get-client-chats-page', [client_chat_controller::class, 'get_client_chats_page']);
            Route::post('get-chat-messages', [client_chat_controller::class, 'get_client_messages_by_client_id']);
            Route::post('send-message', [client_chat_controller::class, 'add_client_message_as_admin']);
            Route::post('change-ia-response', [client_chat_controller::class, 'change_ia_response']);
        });
        Route::prefix('ia-assistant')->group(function(){
            Route::get('/', [admin_pages_controller::class, 'ia_assistant_page']);
            Route::prefix('marketing-report')->group(function(){
                Route::get('/', [admin_pages_controller::class, 'ia_marketing_report_page']);
                Route::get('get-clients', [ia_assistant_controller::class, 'get_clients_list']);
                Route::get('get-conversations', [ia_assistant_controller::class, 'get_conversations_list']);
                Route::get('conversation/{conversation_id}', [ia_assistant_controller::class, 'get_conversation_history']);
                Route::get('preview-pdf/{conversation_id}', [ia_assistant_controller::class, 'preview_pdf']);
                Route::get('download-pdf/{conversation_id}', [ia_assistant_controller::class, 'download_pdf']);
                Route::post('generate', [ia_assistant_controller::class, 'generate_report']);
                Route::post('regenerate', [ia_assistant_controller::class, 'regenerate_report']);
                Route::post('send-email', [ia_assistant_controller::class, 'send_report_email']);
            });
        });
        Route::prefix('blog')->group(function () {
            //Route::get('', [admin_panel_controller::class, 'blog_panel']);
            //Route::get('view/{id}', [admin_panel_controller::class, 'view_blog_panel']);
            Route::post('add-blog-by-ia', [blog_controller::class, 'add_blog_by_ia']);
            Route::post('add', [blog_controller::class, 'add_blog']);
            Route::post('edit', [blog_controller::class, 'edit_blog']);
            Route::post('delete', [blog_controller::class, 'delete_blog']);
            Route::post('get-page', [blog_controller::class, 'get_page_blog']);
            Route::post('change-principal-image', [blog_controller::class, 'change_principal_image_blog']);
            Route::post('get-principal-information', [blog_controller::class, 'get_principal_information_blog']);
            Route::prefix('segment')->group(function () {
                Route::post('add', [blog_controller::class, 'add_segment_blog']);
                Route::post('edit', [blog_controller::class, 'edit_segment_blog']);
                Route::post('delete', [blog_controller::class, 'delete_segment_blog']);
                Route::post('get', [blog_controller::class, 'get_segment_blog']);
                Route::post('up-position', [blog_controller::class, 'up_position_segment_blog']);
                Route::post('down-position', [blog_controller::class, 'down_position_segment_blog']);
                
            });
            Route::prefix('email_subscriber')->group(function () {
                Route::post('set-bulk', [blog_email_subscriber_controller::class, 'set_bulk_blog_email_subscriber']);
            });
            Route::prefix('subject')->group(function () {
                Route::post('set', [blog_controller::class, 'set_subject_blogs']);
                Route::post('delete', [blog_controller::class, 'delete_subject_blog']);
                Route::post('get', [blog_controller::class, 'get_subject_blog']);
            });
        });
        Route::prefix('instagram')->group(function () {
            Route::post('add-ia-instagram-feed-post', [instagram_controller::class, 'add_ia_instagram_feed_post']);
            Route::prefix('subject')->group(function () {
                Route::post('set', [instagram_controller::class, 'set_subject_instagram']);
                Route::post('delete', [instagram_controller::class, 'delete_subject_instagram']);
                Route::post('get', [instagram_controller::class, 'get_subject_instagram']);
            });
        });
        Route::prefix('facebook')->group(function () {
            Route::post('add-ia-facebook-feed-post', [facebook_controller::class, 'add_ia_facebook_feed_post']);
            Route::prefix('subject')->group(function () {
                Route::post('set', [facebook_controller::class, 'set_subject_facebook']);
                Route::post('delete', [facebook_controller::class, 'delete_subject_facebook']);
                Route::post('get', [facebook_controller::class, 'get_subject_facebook']);
            });
        });
        Route::prefix('linkedin')->group(function () {
            Route::post('add-ia-linkedin-feed-post', [linkedin_controller::class, 'add_ia_linkedin_feed_post']);
            Route::prefix('subject')->group(function () {
                Route::post('set', [linkedin_controller::class, 'set_subject_linkedin']);
                Route::post('delete', [linkedin_controller::class, 'delete_subject_linkedin']);
                Route::post('get', [linkedin_controller::class, 'get_subject_linked']);
            });
        });
        Route::prefix('twitter')->group(function () {
            Route::post('add-ia-twitter-feed-post', [twitter_controller::class, 'add_ia_twitter_feed_post']);
            Route::prefix('subject')->group(function () {
                Route::post('set', [twitter_controller::class, 'set_subject_twitter']);
                Route::post('delete', [twitter_controller::class, 'delete_subject_twitter']);
                Route::post('get', [twitter_controller::class, 'get_subject_twitter']);
            });
        });
        Route::prefix('freepik')->group(function () {
            Route::post('generate-image', [freepik_controller::class, 'generate_image']);
            Route::post('add-ia-freepik-feed-post', [freepik_controller::class, 'add_ia_freepik_feed_post']);
            Route::prefix('subject')->group(function () {
                Route::post('set', [freepik_controller::class, 'set_subject_freepik']);
                Route::post('delete', [freepik_controller::class, 'delete_subject_freepik']);
                Route::post('get', [freepik_controller::class, 'get_subject_freepik']);
            });
        });
        //simple crud
        Route::prefix('pusher')->group(function () {
            Route::post('get',[pusher_controller::class, 'get_pusher_data']);
        });
        Route::prefix('eps')->group(function () {
            Route::post('get', [eps_controller::class, 'get_eps']);
            Route::post('add', [eps_controller::class, 'add_eps']);
            Route::post('update', [eps_controller::class, 'update_eps']);
            Route::post('delete', [eps_controller::class, 'delete_eps']);
        });
        Route::prefix('arl')->group(function () {
            Route::post('get', [arl_controller::class, 'get_arl']);
            Route::post('add', [arl_controller::class, 'add_arl']);
            Route::post('update', [arl_controller::class, 'update_arl']);
            Route::post('delete', [arl_controller::class, 'delete_arl']);
        });
        Route::prefix('afp')->group(function () {
            Route::post('get', [afp_controller::class, 'get_afp']);
            Route::post('add', [afp_controller::class, 'add_afp']);
            Route::post('update', [afp_controller::class, 'update_afp']);
            Route::post('delete', [afp_controller::class, 'delete_afp']);
        });
        Route::prefix('country')->group(function () {
            Route::post('get', [country_controller::class, 'get_country']);
            Route::post('add', [country_controller::class, 'add_country']);
            Route::post('update', [country_controller::class, 'update_country']);
            Route::post('delete', [country_controller::class, 'delete_country']);
        });
        Route::prefix('sector')->group(function () {
            Route::post('get', [sector_controller::class, 'get_sector']);
            Route::post('add', [sector_controller::class, 'add_sector']);
            Route::post('update', [sector_controller::class, 'update_sector']);
            Route::post('delete', [sector_controller::class, 'delete_sector']);
        });
        Route::prefix('service')->group(function () {
            Route::post('get', [service_controller::class, 'get_service']);
            Route::post('add', [service_controller::class, 'add_service']);
            Route::post('update', [service_controller::class, 'update_service']);
            Route::post('delete', [service_controller::class, 'delete_service']);
        });
    });
});
//Client routes
Route::get('Client', function () {
    return redirect('/client');
});
Route::prefix('client')->group(function () {
    Route::get('', [client_pages_controller::class, 'client_login_page']);
    Route::get('register', [client_pages_controller::class, 'client_register_page']);
    Route::post('register', [clients_controller::class, 'register_client']);
    Route::post('login', [client_users_controller::class, 'login_client_user']);
    Route::post('forgot-password', [client_users_controller::class, 'forgot_password']);
    Route::prefix('payments')->group(function () {
        Route::get('pay/{unique_id}', [client_pages_controller::class, 'client_pay_page_unlogged']);
        Route::get('response/{unique_id}', [client_pages_controller::class, 'client_payment_response_page_unlogged'])->name('payment_response');
        Route::post('get-income-data', [incomes_controller::class, 'get_income_data_for_payment_unlogged']);
        Route::post('get-income-payment-data', [income_payment_controller::class, 'get_income_payment_data']);
        Route::post('update-payment', [income_payment_controller::class, 'update_order_transaction']);
        Route::prefix('payment-gateway')->group(function () {
            Route::prefix('wompi')->group(function () {
                Route::post('create', [income_payment_controller::class, 'add_wompi_payment_unlogged']);
                Route::post('finished-payment', [income_payment_controller::class, 'finished_wompi_payment']);
            });
            Route::prefix('bold')->group(function () {
                Route::post('create', [income_payment_controller::class, 'add_bold_payment_unlogged']);
                Route::post('check-status', [payment_gateway_bold_controller::class, 'check_transaction_status']);
            });
        });
        Route::prefix('webhook')->group(function(){
            Route::prefix('wompi')->group(function(){
                Route::post('receive-post', [payment_gateway_wompi_controller::class, 'post_wompi_payment']);
            });
            Route::prefix('bold')->group(function(){
                Route::post('receive-post', [payment_gateway_bold_controller::class, 'post_bold_payment']);
            });
        });
    });
    Route::group(['middleware' => 'client_middleware'], function(){
        Route::get('dashboard', [client_pages_controller::class, 'client_dashboard_page']);
        Route::prefix('profile')->group(function () {
            Route::get('set-password', [client_pages_controller::class, 'client_user_set_password_page']);
            Route::post('set-password', [client_users_controller::class, 'set_client_user_password']);
            Route::get('', [client_pages_controller::class, 'client_user_profile_page']);
            Route::post('update', [client_users_controller::class, 'update_client_user_profile']);
            Route::post('close-session', [client_users_controller::class, 'close_session']);
        });
        Route::prefix('users')->group(function () {
            Route::get('', [client_pages_controller::class, 'client_users_page']);
            Route::get('permissions', [client_users_controller::class, 'get_all_permissions']);
            Route::post('permissions-by-user', [client_users_controller::class, 'get_permissions_by_user']);
            Route::post('add', [client_users_controller::class, 'session_add_client_user']);
            Route::post('get', [client_users_controller::class, 'get_client_users']);
            Route::post('get-page', [client_users_controller::class, 'get_client_users_page']);
            Route::get('all', [client_users_controller::class, 'get_all_client_users_by_client_id']);
            Route::post('update', [client_users_controller::class, 'session_update_client_user']);
            Route::post('delete', [client_users_controller::class, 'delete_client_user']);
            Route::post('restore-password', [client_users_controller::class, 'restore_client_user_password']);
            Route::prefix('traceability')->group(function () {
                Route::post('', [client_users_controller::class, 'client_users_get_traceability']);
            });
        });
        Route::prefix('my-companies')->group(function () {
            Route::get('', [client_pages_controller::class, 'client_companies_page']);
            Route::post('update', [clients_controller::class, 'my_company_update_client']);
            Route::prefix('documents')->group(function(){
                Route::post('add', [clients_controller::class, 'add_client_document']);
                Route::post('get', [clients_controller::class, 'get_client_documents']);
                Route::post('update', [clients_controller::class, 'update_client_document']);
                Route::post('delete', [clients_controller::class, 'delete_client_document']);
            });
        });
        Route::prefix('licenses')->group(function(){
            Route::get('', [client_pages_controller::class, 'licenses_page']);
            Route::post('update', [licenses_controller::class, 'update_license']);
            Route::post('get-page', [licenses_controller::class, 'get_page_by_client_id']);
            Route::post('get-by-id', [licenses_controller::class, 'get_license_by_id']);
            Route::prefix('documents')->group(function(){
                Route::post('add', [licenses_controller::class, 'add_license_document']);
                Route::post('get', [licenses_controller::class, 'get_license_documents']);
            });
            Route::prefix('notifications')->group(function(){
                Route::post('add', [licenses_controller::class, 'add_license_notification']);
                Route::post('get', [licenses_controller::class, 'get_license_notifications']);
                Route::post('get-by-licenses-ids', [licenses_controller::class, 'get_license_notifications_by_license_ids']);
                Route::post('update', [licenses_controller::class, 'update_license_notification']);
                Route::post('delete', [licenses_controller::class, 'delete_license_notification']);
                Route::post('restore', [licenses_controller::class, 'restore_license_notification']);
                Route::post('change-position', [licenses_controller::class, 'change_license_notification_position']);
            });
        });
        Route::prefix('payments')->group(function(){
            Route::get('', [client_pages_controller::class, 'incomes_page']);
            Route::post('get-page', [incomes_controller::class, 'get_page_by_client_id']);
            Route::post('send', [incomes_controller::class, 'send_income']);
            Route::post('get-licenses', [incomes_controller::class, 'get_licenses']);
        });
        Route::prefix('traceability')->group(function () {
            Route::get('', [client_pages_controller::class, 'traceability_page']);
        });
        //For just get information
        Route::prefix('getters')->group(function () {
            //simple crud
            Route::prefix('eps')->group(function () {
                Route::post('get', [eps_controller::class, 'get_eps']);
            });
            Route::prefix('arl')->group(function () {
                Route::post('get', [arl_controller::class, 'get_arl']);
            });
            Route::prefix('afp')->group(function () {
                Route::post('get', [afp_controller::class, 'get_afp']);
            });
            Route::prefix('country')->group(function () {
                Route::post('get', [country_controller::class, 'get_country']);
            });
            Route::prefix('sector')->group(function () {
                Route::post('get', [sector_controller::class, 'get_sector']);
            });
            Route::prefix('service')->group(function () {
                Route::post('get', [service_controller::class, 'get_service']);
            });
        });
    });
});
//API
Route::prefix('api')->group(function () {
    Route::get('check_conection', function () {
        return ['status' => 1];
    });
    Route::prefix('client')->group(function () {
        Route::prefix('licenses')->group(function () {
            Route::post('get-by-user-key', [licenses_controller::class, 'get_license_by_user_key']);
            Route::post('get-by-user-keys', [licenses_controller::class, 'get_license_by_user_keys']);
        });
        Route::group(['middleware' => 'home_page_middleware'], function(){
            Route::prefix('chat')->group(function () {
                Route::post('getClientChatMessages', [client_chat_controller::class, 'get_client_messages_by_client']);
                Route::post('addClientChatMessage', [client_chat_controller::class, 'add_client_message']);
                Route::post('getRunAssistant', [open_ia_controller::class, 'open_ia_get_run_assistant']);
                Route::post('saveNewThreadMessages', [client_chat_controller::class, 'save_new_thread_messages']);
            });
        });
        /*Route::prefix('ensamble')->group(function () {
            Route::prefix('certificate')->group(function () {
                Route::get('generate', [client_ensamble_controller::class, 'generate_certificates']);
                Route::get('get', [client_ensamble_controller::class, 'get_certificates']);
                Route::get('download/{identification}', [client_ensamble_controller::class, 'download_certificate']);       
            });
        });*/
    });
    Route::prefix('blog')->group(function () {
        Route::get('approve/{unique_id}', [admin_pages_controller::class, 'approve_blog_page']);
        Route::post('approve', [blog_controller::class, 'approve_blog']);
        Route::post('get-page', [blog_controller::class, 'get_page_blog']);
        Route::post('get-by-url', [blog_controller::class, 'get_blog_by_url']);
        Route::get('unsubscribe/{unique_id}', [client_pages_controller::class, 'unsubscribe_blog']);
        Route::post('unsubscribe', [blog_email_subscriber_controller::class, 'unsubscribe_blog']);
    });
    Route::prefix('instagram')->group(function () {
        Route::get('approve/{unique_id}', [admin_pages_controller::class, 'approve_instagram_post_page']);
        Route::post('approve', [instagram_controller::class, 'approve_instagram_post']);
    });
    Route::prefix('facebook')->group(function () {
        Route::get('approve/{unique_id}', [admin_pages_controller::class, 'approve_facebook_post_page']);
        Route::post('approve', [facebook_controller::class, 'approve_facebook_post']);
    });
    Route::prefix('linkedin')->group(function () {
        Route::get('approve/{unique_id}', [admin_pages_controller::class, 'approve_linkedin_post_page']);
        Route::post('approve', [linkedin_controller::class, 'approve_linkedin_post']);
    });
    Route::prefix('twitter')->group(function () {
        Route::get('approve/{unique_id}', [admin_pages_controller::class, 'approve_twitter_post_page']);
        Route::post('approve', [twitter_controller::class, 'approve_twitter_post']);
    });
    /*Route::prefix('old-opzio')->group(function () {
        Route::post('get-and-set-clients-licenses', [old_opzio_controller::class, 'get_and_set_client_and_licenses']);
        Route::post('set-last-payed-date-on-licenses', [old_opzio_controller::class, 'last_payed_date_on_licenses']);
        Route::post('set-incomes', [old_opzio_controller::class, 'set_incomes']);
        Route::post('set-outcomes', [old_opzio_controller::class, 'set_outcomes']);
    });*/
});
//CSRF Token
Route::get('get-csrf-token', function(){
    return ['access_token'=>csrf_token()];
});
//Test
Route::prefix('test')->group(function () {
    Route::get('test_documents', function () {
        $Data = [
            "income"=> [
            "unique_id"=> "47ab2fb4-cecf-4f29-a0f4-dae475608fe9",
            "state"=> "0",
            "client_id"=> "1",
            "client_identification"=> "56465465465",
            "client_name"=> "Opzio",
            "timely_payment"=> "2024-03-05",
            "cutoff_date"=> "2024-03-20",
            "description"=> null,
            "total"=> 509500000,
            "updated_at"=> "2024-03-06T02:37:32.000000Z",
            "created_at"=> "2024-03-06T02:37:32.000000Z",
            "id"=> 39,
            "url" => "https://erp.opzio.co/storage/incomes/pdfs/47ab2fb4-cecf-4f29-a0f4-dae475608fe9.pdf",
            "licenses"=> collect([
                [
                "income_id"=> 39,
                "license_id"=> "3",
                "license_name"=> "aaaaaaaa",
                "service_id"=> "4",
                "service_name"=> "Página Web",
                "recurrence_months"=> "6",
                "value"=> "50000000",
                "comission"=> "10",
                "employee_id"=> "1",
                "employee_name"=> "juan duaz",
                "tax_id"=> "1",
                "tax_name"=> "IVA",
                "tax_value"=> "0.00",
                "description"=> null,
                "total"=> "509500000",
                "updated_at"=> "2024-03-06T02:37:32.000000Z",
                "created_at"=> "2024-03-06T02:37:32.000000Z",
                "hours" => 0,
                "id"=> 39
                ]
            ])
            ],
            "client"=> [
            "id"=> 1,
            "unique_id"=> "509fe981-125a-4cd7-b041-e716e555de8f",
            "name"=> "Opzio",
            "lastname"=> null,
            "email"=> "465465@gmail.com",
            "identification_type"=> 0,
            "identification"=> "56465465465",
            "country"=> [
                "id"=> 3,
                "name"=> "Ecuador",
                "created_at"=> "2024-01-18T04:09:53.000000Z",
                "updated_at"=> "2024-01-18T04:09:53.000000Z"
            ],
            "phone"=> "65465465",
            "address"=> "654654",
            "sector"=> [
                "id"=> 2,
                "name"=> "Testing",
                "created_at"=> "2024-01-19T01:42:51.000000Z",
                "updated_at"=> "2024-01-19T01:42:51.000000Z"
            ],
            "description"=> null,
            "photo"=> "509fe981-125a-4cd7-b041-e716e555de8f.webp",
            "active"=> 1,
            "verified"=> 1,
            "created_at"=> "2024-01-13T17:58:44.000000Z",
            "updated_at"=> "2024-01-19T01:46:29.000000Z"
            ],
            "public_path"=> "https://erp.opzio.co/"
        ];
        return view('pdf.purchase_order',compact('Data'));
    });
    Route::prefix('mail')->group(function () {
        Route::get('purchase_order', function(){
            $Data = [
                "income"=> [
                "unique_id"=> "47ab2fb4-cecf-4f29-a0f4-dae475608fe9",
                "state"=> "0",
                "client_id"=> "1",
                "client_identification"=> "56465465465",
                "client_name"=> "Opzio",
                "timely_payment"=> "2024-03-05",
                "cutoff_date"=> "2024-03-20",
                "description"=> null,
                "total"=> 509500000,
                "updated_at"=> "2024-03-06T02:37:32.000000Z",
                "created_at"=> "2024-03-06T02:37:32.000000Z",
                "id"=> 39,
                "url" => "https://erp.opzio.co/storage/incomes/pdfs/47ab2fb4-cecf-4f29-a0f4-dae475608fe9.pdf",
                "licenses"=> [
                    [
                    "income_id"=> 39,
                    "license_id"=> "3",
                    "license_name"=> "aaaaaaaa",
                    "service_id"=> "4",
                    "service_name"=> "Página Web",
                    "recurrence_months"=> "6",
                    "value"=> "50000000",
                    "comission"=> "10",
                    "employee_id"=> "1",
                    "employee_name"=> "juan duaz",
                    "tax_id"=> "1",
                    "tax_name"=> "IVA",
                    "tax_value"=> "0.19",
                    "description"=> null,
                    "total"=> "509500000",
                    "updated_at"=> "2024-03-06T02:37:32.000000Z",
                    "created_at"=> "2024-03-06T02:37:32.000000Z",
                    "id"=> 39
                    ]
                ]
                ],
                "client"=> [
                "id"=> 1,
                "unique_id"=> "509fe981-125a-4cd7-b041-e716e555de8f",
                "name"=> "Opzio",
                "lastname"=> null,
                "email"=> "465465@gmail.com",
                "identification_type"=> 0,
                "identification"=> "56465465465",
                "country"=> [
                    "id"=> 3,
                    "name"=> "Ecuador",
                    "created_at"=> "2024-01-18T04:09:53.000000Z",
                    "updated_at"=> "2024-01-18T04:09:53.000000Z"
                ],
                "phone"=> "65465465",
                "address"=> "654654",
                "sector"=> [
                    "id"=> 2,
                    "name"=> "Testing",
                    "created_at"=> "2024-01-19T01:42:51.000000Z",
                    "updated_at"=> "2024-01-19T01:42:51.000000Z"
                ],
                "description"=> null,
                "photo"=> "509fe981-125a-4cd7-b041-e716e555de8f.webp",
                "active"=> 1,
                "verified"=> 1,
                "created_at"=> "2024-01-13T17:58:44.000000Z",
                "updated_at"=> "2024-01-19T01:46:29.000000Z"
                ],
                "public_path"=> "https://erp.opzio.co/",
                "ia_message" => "Tu tienda virtual está lista para triunfar en la web. ¿Quieres más? Descubre nuestros servicios adicionales y destaca en línea como nunca antes.",
            ];
            return view('mail.purchase_order', compact('Data'));
        });
        Route::get('client_user_restore_password', function(){
            $Data = [
                "name"=> "Opzio",
                "email"=> "juand@opzio.co",
                "restore-code" => "123456",
                "reset_password_date" => "2024-01-13T17:58:44.000000Z",
            ];
            return view('mail.client_user_restore_password', compact('Data'));
        });
        Route::get('income_payment_finished', function(){
            $Data = [
                'client' => [
                    'name' => 'Opzio',
                    'identification' => '56465465465',
                ],
                'income' => [
                    'unique_id' => '47ab2fb4-cecf-4f29-a0f4-dae475608fe9'
                ],
                'income_payment' => [
                    'unique_id' => '47ab2fb4-cecf-4f29-a0f4-dae475608fe9',
                    'total' => 509500000,
                ]
            ];
            return view('mail.income_payment_finished', compact('Data'));
        });
        Route::get('client_income_payment_finished', function(){
            $Data = [
                'client' => [
                    'name' => 'Opzio',
                    'identification' => '56465465465',
                ],
                'income' => [
                    'unique_id' => '47ab2fb4-cecf-4f29-a0f4-dae475608fe9',
                    
                ],
                'income_payment' => [
                    'unique_id' => '47ab2fb4-cecf-4f29-a0f4-dae475608fe9',
                    'payment_state' => 1,
                    'payment_state_string' => 'Aprobado',
                    'total' => 509500000,
                ]
            ];
            return view('mail.client_income_payment_finished', compact('Data'));
        });
        Route::get('approve_ia_blog', function(){
            $Data = [
                "blog" => [
                    "id" => 21,
                    "unique_id" => "3efa4c38-4be6-4141-9358-ac8325cd8f8b",
                    "title" => "Descubre el Futuro: Diseño UX/UI que Transforma la Experiencia del Usuario y el Éxito de tu Marca",
                    "reduce_title" => "Diseño UX/UI: El Futuro de tu Marca",
                    "approved" => 0,
                    "url" => "disen-futuro-marca",
                    "brief" => "Descubre cómo el diseño UX/UI puede transformar la experiencia de tus usuarios y el éxito de tu marca en la era digital. ¡Optimiza tu presencia en línea y diferénciate con un diseño innovador!",
                    "img" => "b2b6805b_d5a3_4a61_9661_4c3a94a5ea0e.webp",
                    "author" => "Opzio I.A.",
                    "type" => 0,
                    "language" => "es",
                    "keywords" => "Diseño UX/UI, Experiencia del Usuario, Marca, Tecnología, Innovación.",
                    "created_at" => "2024-05-06T20:25:44.000000Z",
                    "updated_at" => "2024-05-06T20:26:04.000000Z",
                    "created_at_humans" => "hace 44 minutos",
                    "updated_at_humans" => "hace 44 minutos",
                    "path" => "/blog/disen-futuro-marca",
                    "image_path" => "http://localhost:8000/images/blog/principal/b2b6805b_d5a3_4a61_9661_4c3a94a5ea0e.webp"
                ],
                "segment" => [
                    "blog_id" => 7,
                    "title" => "Impulsa tu Presencia Digital: Diseño de Páginas Web que Convierten Visitantes en Clientes Fieles",
                    "title_position" => 0,
                    "paragraph" => "    <h1>Impulsa tu Presencia Digital: Diseño de Páginas Web que Convierten Visitantes en Clientes Fieles</h1>        <p>En la era digital en la que vivimos, es crucial para cualquier negocio contar con una presencia online sólida y efectiva. Una de las herramientas fundamentales en este sentido es el diseño de páginas web que no solo atraigan a visitantes, sino que también los conviertan en clientes fieles.</p>    <h2>La importancia del diseño de páginas web</h2>    <p>El diseño de una página web es mucho más que simplemente hacerla visualmente atractiva. Debe ser funcional, fácil de navegar y, lo más importante, estar diseñada con un objetivo claro en mente: convertir visitantes en clientes.</p>    <h2>Elementos clave para el diseño de páginas web efectivas</h2>    <p>Para lograr que una página web convierta visitantes en clientes fieles, es importante tener en cuenta algunos elementos clave:</p>    <ul>        <li><strong>Usabilidad:</strong> La página web debe ser fácil de usar y navegar, con una estructura clara y sencilla.</li>        <li><strong>Llamadas a la acción (CTA):</strong> Incluir botones de Comprar ahora, Contactar u otros llamados a la acción que guíen al visitante hacia la conversión.</li>        <li><strong>Diseño responsive:</strong> La página debe verse bien y funcionar correctamente en dispositivos móviles, ya que cada vez más usuarios acceden a internet desde sus smartphones y tablets.</li>        <li><strong>Contenido relevante:</strong> El contenido de la página debe ser informativo y relevante para el público objetivo, mostrando los beneficios de los productos o servicios de la empresa.</li>        <li><strong>Velocidad de carga:</strong> Una página lenta puede hacer que los visitantes se vayan antes siquiera de verla. Es crucial optimizar la velocidad de carga para retener la atención del usuario.</li>    </ul>    <h2>El papel del diseño en la conversión de visitantes en clientes</h2>    <p>Un diseño web bien pensado y ejecutado puede marcar la diferencia en la conversión de visitantes en clientes fieles. Una página web atractiva y funcional crea confianza en la marca y facilita el proceso de compra o contacto, lo que aumenta las probabilidades de fidelización.</p>    <h2>Conclusiones</h2>    <p>En resumen, el diseño de páginas web que convierten visitantes en clientes fieles es un aspecto fundamental en la estrategia digital de cualquier negocio. Al enfocarse en la usabilidad, las llamadas a la acción, el diseño responsive, el contenido relevante y la velocidad de carga, las empresas pueden mejorar significativamente su presencia online y aumentar sus conversiones.</p>",
                    "img_position" => 0,
                    "position" => 1,
                    "updated_at" => "2024-05-02T01:48:45.000000Z",
                    "created_at" => "2024-05-02T01:48:45.000000Z",
                    "id" => 6
                ]
            ];
            return view('mail.approve_ia_blog', compact('Data'));
        });
        Route::get('blog_news', function(){
            $Data = [
                "subscriber" => [
                    "unique_id" => "509fe981-125a-4cd7-b041-e716e555de8f",
                    "email" => "juank._.94@hotmail.com",
                ],
                "blog" => [
                    "id" => 21,
                    "unique_id" => "3efa4c38-4be6-4141-9358-ac8325cd8f8b",
                    "title" => "Descubre el Futuro: Diseño UX/UI que Transforma la Experiencia del Usuario y el Éxito de tu Marca",
                    "reduce_title" => "Diseño UX/UI: El Futuro de tu Marca",
                    "approved" => 0,
                    "url" => "disen-futuro-marca",
                    "brief" => "Descubre cómo el diseño UX/UI puede transformar la experiencia de tus usuarios y el éxito de tu marca en la era digital. ¡Optimiza tu presencia en línea y diferénciate con un diseño innovador!",
                    "img" => "b2b6805b_d5a3_4a61_9661_4c3a94a5ea0e.webp",
                    "author" => "Opzio I.A.",
                    "type" => 0,
                    "language" => "es",
                    "keywords" => "Diseño UX/UI, Experiencia del Usuario, Marca, Tecnología, Innovación.",
                    "created_at" => "2024-05-06T20:25:44.000000Z",
                    "updated_at" => "2024-05-06T20:26:04.000000Z",
                    "created_at_humans" => "hace 44 minutos",
                    "updated_at_humans" => "hace 44 minutos",
                    "path" => "/blog/disen-futuro-marca",
                    "image_path" => "http://localhost:8000/images/blog/principal/b2b6805b_d5a3_4a61_9661_4c3a94a5ea0e.webp"
                ]
            ];
            return view('mail.blog_news', compact('Data'));
        });
        Route::get('pay_remaining', function(){
            $Data = [
                "income"=> [
                    "unique_id"=> "47ab2fb4-cecf-4f29-a0f4-dae475608fe9",
                    "state"=> "0",
                    "client_id"=> "1",
                    "client_identification"=> "56465465465",
                    "client_name"=> "Opzio",
                    "timely_payment"=> "2024-03-05",
                    "cutoff_date"=> "2024-03-20",
                    "description"=> null,
                    "total"=> 509500000,
                    "updated_at"=> "2024-03-06T02:37:32.000000Z",
                    "created_at"=> "2024-03-06T02:37:32.000000Z",
                    "id"=> 39,
                    "payment_link" => "http://localhost:8000/client/payments/pay/A1A2A45D-9015-4540-B705-CAD3A86117F1?external=true",
                ],
                "client"=> [
                "id"=> 1,
                "unique_id"=> "509fe981-125a-4cd7-b041-e716e555de8f",
                "name"=> "Opzio",
                "lastname"=> null,
                "email"=> "465465@gmail.com",
                "identification_type"=> 0,
                "identification"=> "56465465465",
                "country"=> [
                    "id"=> 3,
                    "name"=> "Ecuador",
                    "created_at"=> "2024-01-18T04:09:53.000000Z",
                    "updated_at"=> "2024-01-18T04:09:53.000000Z"
                ],
                "phone"=> "65465465",
                "address"=> "654654",
                "sector"=> [
                    "id"=> 2,
                    "name"=> "Testing",
                    "created_at"=> "2024-01-19T01:42:51.000000Z",
                    "updated_at"=> "2024-01-19T01:42:51.000000Z"
                ],
                "description"=> null,
                "photo"=> "509fe981-125a-4cd7-b041-e716e555de8f.webp",
                "active"=> 1,
                "verified"=> 1,
                "created_at"=> "2024-01-13T17:58:44.000000Z",
                "updated_at"=> "2024-01-19T01:46:29.000000Z"
                ],
                "ia_message" => "Tu tienda virtual está lista para triunfar en la web. ¿Quieres más? Descubre nuestros servicios adicionales y destaca en línea como nunca antes.",
            ];
            return view('mail.pay_remaining', compact('Data'));
        });
    });
    Route::post('add-blog-by-ia', [blog_controller::class, 'add_blog_by_ia']);
    Route::prefix('open-ia')->group(function () {
        Route::post('open-ia-generate-image', [open_ia_controller::class, 'open_ia_generate_image']);
    });
});
//Route::post('OpenIA/MakeQuestion', [open_ia_controller::class, 'open_ia_make_question']);