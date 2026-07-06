<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\income;
use App\Models\income_license;

use App\traits\licenses_trait;
use App\traits\incomes_trait;

class create_month_incomes extends Command
{
    use 
    licenses_trait
    ,incomes_trait
    ;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:create_month_incomes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create daily incomes for licenses whose billing_day matches today, skipping those already billed this month.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //truncate incomes
        /*DB::statement('SET FOREIGN_KEY_CHECKS=0');
        income::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        return 0;*/
        $now = Carbon::now();
        $today = $now->day;
        $LicensesResponse = $this->Licenses_GetLicensesToBill($now->year, $now->month);
        if($LicensesResponse['status'] == 1) {
            $Licenses = $LicensesResponse['data']['licenses'];
            $Clients = $LicensesResponse['data']['clients'];

            // Only process licenses whose billing_day matches today
            $Licenses = $Licenses->filter(function($license) use ($today) {
                return (int)$license->billing_day === $today;
            });

            // Get license IDs that already have a non-rejected income this month
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();
            $alreadyBilledLicenseIds = income_license::whereHas('income', function($query) use ($startOfMonth, $endOfMonth) {
                $query->where('state', '!=', 1)
                      ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);
            })->pluck('license_id')->unique()->toArray();

            // Exclude licenses already billed this month
            $Licenses = $Licenses->filter(function($license) use ($alreadyBilledLicenseIds) {
                return !in_array($license->id, $alreadyBilledLicenseIds);
            });

            $clientIdsWithLicenses = $Licenses->pluck('client_id')->unique();

            foreach ($Clients as $Client) {
                if (!$clientIdsWithLicenses->contains($Client['id'])) {
                    continue;
                }
                $ClientLicenes = $Licenses->where('client_id', $Client['id']);
                try{
                    $BillLicenses = [];
                    $OriginalLicenses = [];
                    foreach ($ClientLicenes as $License) {
                        try{
                            $IncomeLicense = [
                                'license_id' =>  $License['id'],
                                'license_name' =>  $License['name'],
                                'timely_payment' =>  $License['last_billing_date'],
                                'service_id' =>  $License['service_id'],
                                'service_name' =>  $License['service']['name'],
                                'recurrence_months' =>  $License['recurrence_months'],
                                'value' =>  $License['value'],
                                'employee_id' =>  $License['employee_id'],
                                'employee_name' =>  $License['employee']==null?'':$License['employee']['name'],
                                'tax_id' =>  $License['service']['tax_id'],
                                'tax_value' =>  $License['service']['tax']==null?0:((float)$License['service']['tax']['value']),
                                'tax_name' =>  $License['service']['tax']==null?'':$License['service']['tax']['name'],
                                'comission' =>  $License['comission'],
                                'description' => '',
                                'hours' => 0,
                            ];
                            $IncomeLicense['total'] = $IncomeLicense['value'] * (1+(float)$IncomeLicense['tax_value']);
                            $BillLicenses[] = $IncomeLicense;
                            $OriginalLicenses[] = $License;
                        }catch(\Exception $e){
                            info('command:create_month_incomes Licenses error: '.$e->getMessage());
                        }
                        
                    }
                    if(count($BillLicenses) > 0){
                        //Get the farthest timely_payment on the future BiillLicenses
                        $farthestBill = collect($ClientLicenes)->sortByDesc('last_billing_date')->first();
                        $IncomeResponse = $this->Income_Createincome(
                            2,
                            $Client['id'],
                            $Client['identification'],
                            $Client['name'].($Client['last_name']==null?'':' '.$Client['last_name']),
                            $farthestBill['last_billing_date'],
                            $farthestBill['cutoff_date'],
                            '',
                            $BillLicenses
                        );
                        if($IncomeResponse['status'] == 1){
                            //Update licenses
                            foreach ($OriginalLicenses as $BillLicense) {
                                // Usar copy() para no mutar $now
                                $next_billing_date = $now->copy()->addMonths($BillLicense['recurrence_months']);
                                $last_billing_date = $now->copy();
                                $Res = $this->License_UpdateBillingData(
                                    $BillLicense['id']
                                    ,$next_billing_date
                                    ,$last_billing_date
                                );
                            }
                            
                            // Check if client has electronic invoice enabled and income data exists
                            if($Client['electronic_invoice'] == true && isset($IncomeResponse['data']['income'])){
                                try {
                                    // Refresh income to get clean object from database without temporary attributes
                                    $cleanIncome = income::find($IncomeResponse['data']['income']->id);
                                    $SiigoResponse = $this->Income_CreateSiigoInvoice($cleanIncome);
                                    if($SiigoResponse['status'] == 1){
                                        // Refresh income to get updated Siigo data
                                        $cleanIncome->refresh();
                                        info('command:create_month_incomes Siigo invoice created and linked successfully for client '.$Client['identification'].' - Income ID: '.$cleanIncome->id.' - Siigo Invoice ID: '.$cleanIncome->siigo_invoice_id.' - URL: '.$cleanIncome->siigo_invoice_url);
                                    } else {
                                        info('command:create_month_incomes Siigo invoice creation failed for client '.$Client['identification'].' - Income ID: '.$cleanIncome->id.': '.$SiigoResponse['message']);
                                    }
                                } catch(\Exception $e) {
                                    info('command:create_month_incomes Siigo invoice exception for client '.$Client['identification'].' - Income ID: '.$IncomeResponse['data']['income']->id.': '.$e->getMessage());
                                }
                            }
                            
                            // Check if income data exists and has required properties before sending email
                            if(isset($IncomeResponse['data']['income']) && 
                               isset($IncomeResponse['data']['income']['total']) && 
                               isset($IncomeResponse['data']['income']['id']) &&
                               $IncomeResponse['data']['income']['total']<500000){
                                //Send License to receptors
                                $NotificationResponse = $this->License_GetLicenseNotificationsByLicensesIds(collect($BillLicenses)->pluck('license_id')->toArray());
                                if($NotificationResponse['status'] == 1){
                                    $Receptors = $NotificationResponse['data'];
                                    $EmailResponse = $this->Income_SendIncome(
                                        $IncomeResponse['data']['income']['id']
                                        ,$Receptors
                                    );
                                }
                            }
                        }
                    }
                }catch(\Exception $e){
                    info('command:create_month_incomes Clients error: '.$e->getMessage());
                }
            }
        }
        
        return 0;
    }
}
