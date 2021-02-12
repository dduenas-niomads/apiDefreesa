
	public static function sendFirebaseGroup(array $data) {
		# code...
		try {
			$json_object                    = new \stdClass();
			$json_object->to                = '/orders/' . $data['subsidiary_id'] . '-' . $data['customer_id'] . '-' . $data['delivery_id'];
			$json_objectNotification        = new \stdClass();
			$json_objectNotification->sound = 'default';
			$json_objectNotification->body  = $data['message'];
			$json_objectNotification->title = 'Defreesa!!! - Órden nº: ' . $data['order_id'];
			$json_object->notification      = $json_objectNotification;
			$data_send                      = json_encode($json_object);

			$request = [
				'headers' => ['Authorization: key=AIzaSyBXv39MS-Oe-4izxyAt9DfmoZPjCkKUBIc'],
				'url'     => 'https://fcm.googleapis.com/fcm/send',
				'params'  => $data_send
			];

			$response = HttpClient::post($request);
			$status   = $response->statusCode();

			var_dump("Firebase : ".$status);
			exit();
		} catch (\Exception $e) {
			echo $e->getMessage();
			exit();
		}
	}