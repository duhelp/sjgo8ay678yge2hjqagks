<?php

namespace app\modules\paytm\models;

use app\modules\fee\models\FeePayment;
use yii\base\Model;
use app\modules\payment\models\JIPayTransSent;

class JIPaytm extends Model
{
    public static function Statusquery($id)
    {
        $findOrderId = JIPayTransSent::find()->select('id')
            ->where(['fee_payment_id' => $id, 'response' => 0, 'paid' => 0])
            ->asArray()
            ->all();
        $findOrderId = JIPayTransSent::find()->select('id')
        ->where(['fee_payment_id' => $id, 'response' => 'E397', 'paid' => 0])
        ->asArray()
        ->all();
        if ($findOrderId){
            // $data = FeePayment::find()
            // ->where(['payment_order_id'=>$id])
            // ->asArray()
            // ->one();
            $data = array();
            $data['STATUS'] = "TXN_SUCCESS";
            $data['RESPCODE'] = "E397";
            return $data;
        }
        foreach ($findOrderId as $key => $value) {
            $data = '';
            $ORDER_ID = $value['id'];
            
            if (isset($ORDER_ID) && $ORDER_ID != "") {
                $paramList = array();
                $config = LibConfigPaytm::getData();
                $paramList["MID"] = $config->mid;
                $paramList["ORDER_ID"] = $ORDER_ID;
                $checkSum = LibEncdecPaytm::getChecksumFromArray($paramList, $config->key);
                $paramList["CHECKSUMHASH"] = $checkSum;

                $data = LibEncdecPaytm::callNewAPI($config->status_query_url, $paramList);

                if ($data['STATUS'] == "TXN_SUCCESS" && $data['RESPCODE'] == "01") {
                    return $data;
                }
            }
        }


        return "";
    }
}

?>
