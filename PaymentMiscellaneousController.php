<?php

namespace app\modules\paytm\controllers;


use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\filters\AccessControl;
use app\modules\paytm\models\JIPayTempData;
use app\modules\payment\models\JIPayTransSent;
use app\modules\paytm\models\LibEncdecPaytm;
use app\modules\paytm\models\LibConfigPaytm;
use app\modules\paytm\models\JIUserPayment;
use app\modules\fee\models\FeePayment;
use app\modules\paytm\models\JIPaytm;


class PaymentMiscellaneousController extends Controller
{

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'response-from-pay' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Finds the FormSave model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return FormSave the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    //to find model

    public function beforeAction($action)
    {
        if ($this->action->id == 'response-from-pay') {
            Yii::$app->controller->enableCsrfValidation = false;
        }
//        if (Yii::$app->user->isGuest) {
//            $this->redirect(['/site/index']);
//            return false;
//        }
        return true;
    }

    /**
     * Redirects to payment Gateway of Paytm
     * @param type $id
     * @return type
     */
    public function actionTopaymentgateway($id, $pg)
    {

        $id = \Yii::$app->security->validateData($id, \Yii::$app->params['hashKey']);
        $model = FeePayment::find()->where(['id' => $id])->one();
        $response = JIPaytm::Statusquery($model->id);

        if (!empty($response) && $response["STATUS"] == "TXN_SUCCESS") {
            $this->StatusQueryResponse($response);
            return $this->redirect(['/site/index/']);
        }
        if (!empty($model)) {
            $transaction = new JIPayTransSent();
            $transaction->fee_payment_id = $id;
            $transaction->customerId = $model->id . "_" ;
            $transaction->mobile = $model->mobile;
            $transaction->email = $model->email;
            $transaction->amount = $model->amount;
            $transaction->response = '0';
            $transaction->paid = 0;
            $transaction->pg_code = $pg;
            if ($transaction->save()) {
                $Fee = $transaction->amount;
                return $this->renderPartial('@app/modules/paytm/views/payment/to-payment-gateway', ['trans' => $transaction,
                    'model' => $model, 'fee' => $Fee
                ]);
            } else {
                var_dump($transaction->getErrors());
                die;
            }
        }
        Yii::$app->session->setFlash('message', 'Kindly complete your form');
        return $this->redirect(['/site/index']);
    }

    public function actionStatusquery($id)
    {
        $id = \Yii::$app->security->validateData($id, \Yii::$app->params['hashKey']);
        $model = FeePayment::find()->where(['user_id' => Yii::$app->user->identity->id, 'id' => $id])->one();
        $response = JIPaytm::Statusquery($model->id);

        if (!empty($response) && $response["STATUS"] == "TXN_SUCCESS") {
            $this->StatusQueryResponse($response);
            return $this->redirect(['/site/index/']);
        } else {
            return FALSE;
        }

    }

    /**
     * Recieve to Pay from Paytm Gateway
     */
    public function actionResponseFromPay()
    {
        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");
        date_default_timezone_set('Asia/kolkata');
        $logpay = new JIPayTempData();
        $logpay->data = serialize($_POST);
        $logpay->save(false);

        $paytmChecksum = "";
        $paramList = array();
        $isValidChecksum = "FALSE";
        $paramList = $_POST;
        $paytmChecksum = isset($_POST["CHECKSUMHASH"]) ? $_POST["CHECKSUMHASH"] : "";
        $config = LibConfigPaytm::getData(1);


//Verify all parameters received from Paytm pg to your application. Like MID received from paytm pg is same as your application’s MID, TXN_AMOUNT and ORDER_ID are same as what was sent by you to Paytm PG for initiating transaction etc.
        $isValidChecksum = LibEncdecPaytm::verifychecksum_e($paramList, $config->key, $paytmChecksum); //will return TRUE or FALSE string.


        if ($isValidChecksum == "TRUE") {
            if ($_POST["STATUS"] == "TXN_SUCCESS") {
                $response = JIUserPayment::add($_POST);
                if ($response != 9) {
                    Yii::$app->session->setFlash('message', 'Unable to save Payment Details _');
                    return $this->redirect(['/site/index']);
                }else{
                    $user = JIUserPayment::getUser($_POST);
                    $hash = Yii::$app->security->hashData($user->id, Yii::$app->params['hashKey']);
                    return $this->redirect(['/fee/fee-payment-miscellaneous/my-payments', 'id' => $hash]);
                    "Response is ".$response;die;
                }
            } else {
                $trans = JIPayTransSent::find()->where(['id' => $_POST["ORDERID"]])->one();
                $trans->response = $_POST["RESPCODE"];
                $trans->save();
                Yii::$app->session->setFlash('message', 'Payment transaction failed._ _');
                return $this->redirect(['/site/index']);
            }

        }
        Yii::$app->session->setFlash('message', 'Payment transaction failed._ _ _');
        return $this->redirect(['/site/index']);
    }

    public function actionPaymenterror()
    {
        Yii::$app->session->setFlash('message', 'Payment not done.');
        return $this->redirect(['/site/index']);
    }


    //=============================================================================================================
    public function actionStatusQueryResponse($data)
    {
        $StatusQueryResponse = (array)json_decode($data);
        $this->StatusQueryResponse($StatusQueryResponse);
    }

    public function StatusQueryResponse($StatusQueryResponse)
    {
        header("Pragma: no-cache");
        header("Cache-Control: no-cache");
        header("Expires: 0");
        date_default_timezone_set('Asia/kolkata');

        $logpay = new JIPayTempData();
        $logpay->data = serialize($StatusQueryResponse);
        $logpay->save();
        if ($StatusQueryResponse['STATUS'] == "TXN_SUCCESS") {
            $response = JIUserPayment::add($StatusQueryResponse);
            if ($response == 9) {
                Yii::$app->session->setFlash('success', 'Payment Successful');
                return $this->redirect(['/site/index']);

            } else {
                Yii::$app->session->setFlash('message', 'Payment transaction failed._ _');
                return $this->redirect(['/site/index']);
            }
        } else {
            $trans = JIPayTransSent::find()->where(['id' => $_POST["ORDERID"]])->one();
            $trans->response = $_POST["RESPCODE"];
            $trans->save();
        }
        Yii::$app->session->setFlash('danger', 'Payment transaction failed._ _ _');
        return $this->redirect(['/site/dashboard/']);
    }


}
