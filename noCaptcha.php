<?php
	/*
		noCaptcha reCpatcha from google, see: https://www.google.com/recaptcha/intro/index.html
		
		This hook is needed to verify and validate the user submitted answer
		
		usage: [[!FormIt? &hooks=`noCaptcha`]]
	*/
	
	//initial variables
	$secret_key = $modx->getOption('formit.recaptcha_private_key', null, '');
	$user_ip = $_SERVER['REMOTE_ADDR'];
	$response_string = $hook->getValue('g-recaptcha-response');
	$result;
	
	
	//checks for errors
	if(empty($secret_key)){
		$hook->addError('nocaptcha','Нет секретного или приватного ключа в system-setting formit.recaptcha_private_key.');
		return false;
	}
	if(empty($response_string)){
		$hook->addError('nocaptcha','Ниодно значение небыло представлено на капче.');
		return false;
	}
	
	//urlencode vars
	$secret_key = urlencode($secret_key);
	$user_ip = urlencode($user_ip);
	$response_string = urlencode($response_string);
	
	//check for validation via cURL
	$curl = curl_init();
	curl_setopt_array($curl, array(
	    CURLOPT_RETURNTRANSFER => 1,
	    CURLOPT_URL => 'https://www.google.com/recaptcha/api/siteverify?secret='.$secret_key.'&response='.$response_string.'&remoteip='.$user_ip
	));
	
	//get results
	$result = curl_exec($curl);
	
	//close request
	curl_close($curl);
	
	//if $result is not false
	if($result){
		$resultObject = json_decode($result);
		if($resultObject->success){
			//success
			return true;
		}else{
			//errorCode to error handeling
			foreach($resultObject->error-codes as $errorCode){
				switch($errorCode){
					case 'missing-input-secret':
						$hook->addError('nocaptcha','Секретный параметр отсутствует.');
						break;
					case 'invalid-input-secret':
						$hook->addError('nocaptcha','Секретный параметр является недействительным или неправильным.');
						break;
					case 'missing-input-response':
						$hook->addError('nocaptcha','Параметр ответа отсутствует.');
						break;
					case 'invalid-input-response':
						$hook->addError('nocaptcha','Параметр ответа неверен или неправильный.');
						break;
					default:
						$hook->addError('nocaptcha','Неизвестная ошибка: '.$errorCode);
						break;
				}
				return false;
			}
		}
	}else{
		$hook->addError('nocaptcha','Возникла ошибка при выполнении вашего запроса');
		return false;
	}
	
?>
