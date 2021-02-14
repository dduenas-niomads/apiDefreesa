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

	public static function sendFcmTo($device_token = null, $title = "APP", $body = "APP BODY", $icon = null, $data = []) {
		# code...
		$notification = [
			'title' => $title,
			'body' => $body,
			'icon' => $icon,
		];
		$notification = array_filter($notification, function($value) {
			return $value !== null;
		});
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields = array (
			'registration_ids' => [$device_token],
			'notification' => $notification,
			'data' => [ 'app' => $data ]
		);
		$fields = json_encode ( $fields );
		$headers = array (
			'Authorization: key=' . env('FCM_SERVER_KEY'),
			'Content-Type: application/json'
		);
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
		$result = curl_exec ( $ch );
		curl_close ( $ch );
		return $result;
	}

	public function sendFcm(Request $request)
	{
		# code...
		$params = $request->all();
		$notification = [
			'title' => isset($params['title']) ? $params['title'] : "APP",
			'body' => isset($params['body']) ? $params['body'] : "APP",
			'icon' => null,
		];
		$notification = array_filter($notification, function($value) {
			return $value !== null;
		});
		$url = 'https://fcm.googleapis.com/fcm/send';
		$fields = array (
			'registration_ids' => [Auth::user()->firebase_token],
			'notification' => $notification,
			'data' => [ 'app' => [] ]
		);
		$fields = json_encode ( $fields );
		$headers = array (
			'Authorization: key=' . env('FCM_SERVER_KEY'),
			'Content-Type: application/json'
		);
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_POST, true );
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
		$result = curl_exec ( $ch );
		curl_close ( $ch );
		return $result;
	}
}
