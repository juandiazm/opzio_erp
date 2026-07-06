<?php 
namespace App\traits;

use Session;
use Aws\Sns\SnsClient;
use Aws\Exception\AwsException;
use Illuminate\Support\Facades\App;


#REGLOG 60
trait amazon_sns_trait
{
    private $DataInPusher = 1;
    public function AmazonSNS_PublishMessage($dial_code, $phone_number, $message){
        try{
            if($dial_code == null || $dial_code == 'null' || $dial_code == 'NULL' || $dial_code == 'Null'){
                $phone_number = str_replace(' ', '', $phone_number);
            }else{
                $phone_number = str_replace($dial_code,'',str_replace(' ', '', $phone_number));
                $phone_number = $dial_code.$phone_number;
            }
            if (App::environment() === 'production') {
                // Create an instance of the SnsClient
                $sns = new SnsClient([
                    'version' => 'latest',
                    'region' => env('AWS_DEFAULT_REGION'),
                    'credentials' => [
                        'key' => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ],
                ]);

                // Publish a message to the SNS topic
                $result = $sns->publish([
                    'Message' => 'RIDDER S.A.S: '.$message,
                    'PhoneNumber' => $phone_number,
                    'MessageAttributes' => [
                        'AWS.SNS.SMS.SMSType'  => [
                            'DataType'    => 'String',
                            'StringValue' => 'Transactional',
                        ]
                    ]
                    //'PhoneNumber' => '+573192996318'
                ]);
                
                return [
                    'status' => 1,
                    'message' => 'Sended'
                ];
            }else{
                return [
                    'status' => 1,
                    'message' => 'Sended'
                ];
            }
        }catch(\Exception $e){
            info('AmazonSNS_PublishMessage error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }
}