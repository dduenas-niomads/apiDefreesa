<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
	public static function sendFirebaseNotification($order, $user, $message) {
		# code...
		try {
			$json_object                    = new \stdClass();
			$json_object->to                = '/orders/';
			$json_objectNotification        = new \stdClass();
			$json_objectNotification->sound = 'default';
			$json_objectNotification->body  = $message;
			$json_objectNotification->title = '!! ' . env('APP_NAME') . ' !! - Órden nº: ' . $order->id;
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
