<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
	public static function sendFirebaseNotification(array $data) {
		# code...
		try {
			$json_object                    = new \stdClass();
			$json_object->to                = '/orders/' . $data['bs_suppliers_id'] . '-' . $data['customer_id'] . '-' . $data['bs_delivery_id'];
			$json_objectNotification        = new \stdClass();
			$json_objectNotification->sound = 'default';
			$json_objectNotification->body  = $data['message'];
			$json_objectNotification->title = 'Defreesa!!! - Órden nº: ' . $data['order_id'];
			$json_object->notification      = $json_objectNotification;
			$data_send                      = json_encode($json_object);

			$request = [
				'headers' => ['Authorization: key=AIzaSyBz6WWjiVFu3TeiftLUDvT3hiZyPU8NxG8'],
				'url'     => 'https://fcm.googleapis.com/fcm/send',
				'params'  => $data_send
			];

			$response = Http::post($request);
			$status   = $response->statusCode();

			var_dump("Firebase : ".$status);
			exit();
		} catch (\Exception $e) {
			echo $e->getMessage();
			exit();
		}
	}
}
