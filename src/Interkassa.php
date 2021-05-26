<?php

namespace Gimmo\Interkassa;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

class Interkassa
{
    const HANDLE_PARAMS = [
        'ik_inv_id',   // номер в интеркассе
        'ik_pw_via',   // способ оплаты
        'ik_inv_crt',  // Время создания платежа.
        'ik_inv_prc',  // время проведения  
        'ik_co_id',    // наш ID  
        'ik_pm_no',    // номер транзакции (наш)  
        'ik_am',       // скики  
        'ik_cur',      // валюта    
        'ik_desc',     // описание  
        'ik_inv_st'    // success; fail
    ];

    const REQUEST_PARAMS = [
        'ik_co_id',    // наш ID  
        'ik_pm_no',    // номер транзакции (наш)
        'ik_am',       // скики 
        'ik_cur',      // валюта
        'ik_desc'      // описание 
    ];

    const HANDLE_TYPE = 'handle';
    const REQUEST_TYPE = 'request';
    const TYPES = [ 
        self::HANDLE_TYPE => self::HANDLE_PARAMS,
        self::REQUEST_TYPE => self::REQUEST_PARAMS
    ];

    public function __construct()
    {
        //
    }

    public function validate(Request $request) 
    {
        $validator = Validator::make($request->all(), [
			'ik_inv_id'  => 'required',
			'ik_pw_via'  => 'required',
			'ik_inv_crt' => 'required',
			'ik_inv_prc' => 'required',
			'ik_co_id'   => 'required',
			'ik_pm_no'   => 'required',
			'ik_am'      => 'required',
			'ik_cur'     => 'required',
			'ik_desc'    => 'string|nullable',
			'ik_inv_st'  => 'required|in:success',
			'ik_sign'    => 'required'
        ]);

        if ($validator->fails()) {
            return false;
        }

        return true;
    }

    public function validateSignature(Request $request)
    {
        $sign = $this->getSignature(self::HANDLE_TYPE, $request);
        if (!$sign) return false;
        return $request->get('ik_sign') == $sign;
    }

    public function allowIP($ip)
    {
        if ($ip == '127.0.0.1' && config('interkassa.locale') === true) {
            return true;
        }

        return in_array($ip, config('interkassa.allowed_ips'));
    }


    public function getSignature($type, Request $request)
    {
        $key = config('interkassa.secret_key');

        if (!array_key_exists($type, self::TYPES)) return null;

        $hash = $request->only(self::TYPES[$type]);

        $method = $type . 'TypeSignature';
        if (method_exists($this, $method))$this->$method($hash, $request);

        $hash[] = $key;
        return strtoupper(hash('sha256', implode(':', $hash)));
    }

    public function getRedirectPaymentUrl($ik_pm_no, $amount, $description = '', $curr = null)
    {
         
        $ik_am = number_format($amount, 2, '.', '');
        $ik_co_id = config('interkassa.merchant_id');
        $ik_cur = $curr ?? config('interkassa.currency');
        $ik_desc = base64_encode($description);
        $m_key = config('interkassa.secret_key');
        
        $params = collect(self::TYPES[self::REQUEST_TYPE]);
        ksort($params, SORT_STRING);
        $request = new \Illuminate\Http\Request(compact($params->all()));
        $ik_sign = $this->getSignature(self::REQUEST_TYPE, $request);
        return config('interkassa.url') . '?' . http_build_query(compact($params->except('m_key')->push('ik_sign')->toArray()));
    }

    public function handle(Request $request)
    {
        if (!$this->allowIP($request->ip())) return $this->responseError($request->get('ik_pm_no'));
        if (!$this->validate($request)) return $this->responseError($request->get('ik_pm_no'));
        if (!$this->validateSignature($request)) return $this->responseError($request->get('ik_pm_no'));

        $order = $this->callSearchOrder($request);
        if (!$order) return $this->responseError($request->get('ik_pm_no'));

        if (Str::lower($order['approved']) == 1 ) return $this->responseOK($request->get('ik_pm_no'));
        if (! $this->callPaidOrder($request, $order)) return $this->responseError($request->get('ik_pm_no'));
        return $this->responseOK($request->get('ik_pm_no'));
    }

    public function callSearchOrder(Request $request)
    {
        if (is_null(config('interkassa.searchOrder'))) {
            throw new Exception("Search order handler not found", 500);
        }

        return App::call(config('interkassa.searchOrder'), ['order_id' => $request->input('ik_pm_no')]);
    }

    public function callPaidOrder($request, $order)
    {
        if (is_null(config('interkassa.paidOrder'))) {
            throw new Exception("Paid order handler not found", 500);
        }

        return App::call(config('interkassa.paidOrder'), ['request' => $request, 'order' => $order]);
    }

    public function responseError($orderid)
    {
        return $orderid.'|error';
    }

    public function responseOK($orderid)
    {
        return $orderid.'|success';
    }
}