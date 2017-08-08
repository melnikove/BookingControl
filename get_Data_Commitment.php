<?php
	

	function getBookingDate($arr)
	{
		foreach ($arr as $key) {
			if ($key['id']=='57515') {
				  return $key['values'][0]['value'];
			}
			
		}
	}
    
	function getDataCommitmentList($i_date)
	{
		require_once(__DIR__ . '/vendor/autoload.php');

		// Configure API key authorization: api_key
		Introvert\Configuration::getDefaultConfiguration()->setApiKey('key', 'a68eb01d5aa7d40ae45af4825d8d713a');

		$api = new Introvert\ApiClient();
		$crm_user_id = array(); // int[] | фильтр по id ответственного
		$status = array(); // int[] | фильтр по id статуса
		$status[]=15175276; //статус принимают решение
		$status[]=15177601; //статус ТЗ
		$status[]=15224578; //статус разработка

		$id = array(); // int[] | фильтр по id
		$ifmodif = ""; // string | фильтр по дате изменения. timestamp или строка в формате 'D, j M Y H:i:s'
		$count = 10000; // int | Количество запрашиваемых элементов
		$offset = 0; // int | смещение, относительно которого нужно вернуть элементы

		try {
   			$result = $api->lead->getAll($crm_user_id, $status, $id, $ifmodif, $count, $offset);
    		//print_r($result);
		} 
		catch (Exception $e) {
    		echo 'Exception when calling LeadApi->getAll: ', $e->getMessage(), PHP_EOL;
		}
        
        $date=new DateTime();
        
        $date=clone $i_date;//начало отрезка времени из 30 дней
        
		$i30=new DateInterval('P30D');
        $date_buf=clone $date;
		$dateCurrent30=date_add($date_buf, $i30);//конец отрезка времени из 30 дней

		$rightLeads=array();
        $returnedDate=array();
		$leadsForProcessing=$result['result'];
		foreach ($leadsForProcessing as $leadForProcesing) { //обрабатываем каждую сделку

			$leadCustomFields=$leadForProcesing['custom_fields'];
			
			$tmpDate=getBookingDate($leadCustomFields);//получаем дату бронирования(пользовательское поле)
			
			if  ($tmpDate!='') {//если поле даты бронирования заполнено, то обрабатываем эту сделку
				
				$leadBookingDate = new DateTime($tmpDate);

				$interval1=date_diff($date, $leadBookingDate);

				if (($leadBookingDate>=$date) & ($leadBookingDate<=$dateCurrent30)) {//если дата бронирования находится между началом и концом 30дневного отрезка
					$day=(date_diff($date, $leadBookingDate)->d)+1;//вычисляем смещение в днях от начала
					$returnedDate[$day]++;//для этого дня увеличиваем количество сделок на 1
				}
			}
		}

		return $returnedDate;//таким образом, на выходе мы получаем массив из 30 элементов, каждый элемент которого
		//содержит количество сделок с датой бронирования на соответствующий день
	}


    header("Content-type: text/plain; charset=utf-8");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);

	while($msg = each ($_POST))
	{
		$myMsg=$msg['value'];
	}

    $myDate=new DateTime($myMsg);//на входе от фронтенда получаем дату(либо текущая, либо на которую кликнули)

    $commitedDatesArray=getDataCommitmentList($myDate);//получаем массив количеств сделок
    print_r($commitedDatesArray);//отправляем массив во фронтенд

?>