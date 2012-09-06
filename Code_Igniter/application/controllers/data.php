<?php

/**
 * Copyright 2012 Martijn van de Rijdt
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

//This controller for AJAX POSTS simply directs to a model and returns a response

class Data extends CI_Controller {

	function __construct() {
			parent::__construct();
			$this->load->model('Survey_model', '', TRUE);
			$this->load->helper(array('subdomain','url', 'string'));
		
	}

	public function index()
	{
		show_404();
	}
	
//	public function upload()
//	{
//		$subdomain = get_subdomain(); //from subdomain helper
//		$data_received = $this->input->post(); //returns FALSE if there is no POST data
//		
//		if ($data_received && $this->Survey_model->is_live_survey($subdomain))
//		//NOTE: the second condition prevents submission of data of existing but 'no-longer-live' surveys!
//		{
//			$response = $this->Survey_model->add_survey_data($data_received, $subdomain);			
//		}
//		else
//		{
//			$response = array('error'=>'no data received or survey not live');
//		}
//		
//		echo json_encode($response);
//	}
//	// could be used to allow sending of server data to client (for editing e.g.)
//	public function download()
//	{
//	}

	public function submission()
	{
		$message = '';
		$http_code = 0;
		$subdomain = get_subdomain();
		$submission_url = $this->Survey_model->get_form_submission_url();

		//extract data from the post
		extract($_POST);
		
		//$submission_url = 'http://formhub.org/martijnr/submission';
		//$url = 'https://jrosaforms.appspot.com/submission';

		if (!$submission_url){
			return $this->output->set_status_header(500, 'OpenRosa server submission url not set');
		}

		if(!isset($xml_submission_data) || $xml_submission_data == '')
		{
			return $this->output->set_status_header(500, 'Did not receive data (Enketo server)');
		}

		$xml_submission_filepath = "/tmp/".random_string('alpha', 10).".xml";//*/"/tmp/data_submission.xml";
		$xml_submission_file = fopen($xml_submission_filepath, 'w');
			
		if (!$xml_submission_file)
		{
			return $this->output->set_status_header(500, "Issue creating file from uploaded XML data (Enketo server)");
		}

		fwrite($xml_submission_file, $xml_submission_data);
		fclose($xml_submission_file);
		
		$fields = array('xml_submission_file'=>'@'.$xml_submission_filepath.';type=text/xml');   
		//log_message('debug','xml_submission_file to be sent: '.$xml_submission_file);

		//open connection
		$ch = curl_init();

		//set the url, number of POST vars, POST data
		curl_setopt($ch,CURLOPT_URL,$submission_url);

		//set custom HTTP headers
		curl_setopt($ch,CURLOPT_HTTPHEADER, array
			(
			'X-OpenRosa-Version: 1.0',
			'Date: '.$Date
			)
		);

		//debugging
		//curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		//add POST content
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
		
		//execute post
		$result = curl_exec($ch);
		
		//debugging:
		$response = '';
		foreach (curl_getinfo($ch) as $property=>$value) { 
			$response .= $property . " : " . $value . "<br />"; 
		}	  
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		//close connection
		curl_close($ch);

		$this->output->set_status_header($http_code, $response);
	}
}
?>

