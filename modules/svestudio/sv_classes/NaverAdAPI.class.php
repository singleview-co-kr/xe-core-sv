<?php
/**
 * NaverAdAPI
 *
 * @author singleview.co.kr
 * @copyright singleview.co.kr
 * <singleview.co.kr>
 *
 * @version 0.1
 *
 * This program is commercial software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
class NaverAdAPI {
	const API_URL='https://api.naver.com';
	const RAW_DATA_PATH=_XE_PATH_.'files/svestudio/';
	var $_g_oApi=null;
	var $_g_bDebug=false;
	var $_g_sRawDataPath=null;
	var $_g_sCurDatetime=null;
	var $_g_sAdNvrAdCustomerId=null;
/**
 * Default query parameters
 */
	protected $_g_aDefaultQueryParams = array();
/**
 * Constructor
 */
	public function __construct($bDebug=false) 
	{
		if( $bDebug )
			$this->_g_bDebug = true;

		$this->_g_sCurDatetime = date('Ymd');

		//if (!function_exists('curl_init')) 
		//	throw new Exception('The curl extension for PHP is required.');
		//$this->_g_aDefaultQueryParams = array
		//(
		//	'start-date' => date('Y-m-d', strtotime('-1 month')),
		//	'end-date' => date('Y-m-d'),
		//);
	}
/**
 * @brief
 */
	private function _getMidConfig()
	{
		$sMid = Context::get('mid');
		$oModuleModel = &getModel('module');
		return $oModuleModel->getModuleInfoByMid($sMid);
	}
/**
 * @brief
 * http://naver.github.io/searchad-apidoc/#/tags/MasterReport
 */
	public function retrieveNvAdStructure() 
	{
		$oModuleInfo = $this->_getMidConfig();
		$aAdAcctSetTitle = unserialize( $oModuleInfo->svestudio_acct_set_title );
		$aNvrAdApiKey = unserialize( $oModuleInfo->svestudio_nvrsearchad_api_key );
		$aNvrAdSecretKey = unserialize( $oModuleInfo->svestudio_nvrsearchad_secret_key );
		$aAdNvrAdCustomerId = unserialize( $oModuleInfo->svestudio_nvrsearchad_customer_id );
		
		// 'Account' report available for the acct owner only
		$aMasterReportType = array( 'BusinessChannel', 'Campaign', 'CampaignBudget', 'Adgroup', 'AdgroupBudget', 'Keyword', 'Ad', 'AdExtension', 'Qi');

		ini_set('default_socket_timeout', 30);
		require_once(_XE_PATH_.'modules/svestudio/restapi.php');
		$sResultStatementForPython = 'result=success';
		
		foreach( $aAdAcctSetTitle as $key=>$val)
		{
			$sNvrAdApiKey = $aNvrAdApiKey[$key];
			$sNvrAdSecretKey = $aNvrAdSecretKey[$key];
			$this->_g_sAdNvrAdCustomerId = $aAdNvrAdCustomerId[$key];
			$this->_setDebugFile();
			$this->_debug('=============start master report retrieveing process=============');

			$this->_g_sRawDataPath = self::RAW_DATA_PATH.$this->_g_sAdNvrAdCustomerId.'/';
			FileHandler::makeDir($this->_g_sRawDataPath);

			$this->_g_oApi = new RestApi(self::API_URL, $sNvrAdApiKey, $sNvrAdSecretKey, $this->_g_sAdNvrAdCustomerId);
			$oOutput = $this->_g_oApi->DELETE('/master-reports'); 
			if( !$oOutput->toBool() ) //request has expired 에러 나오면 서버 시간 동기화 refer to http://idchowto.com/?p=22457
			{
				$sResultStatementForPython = 'result=error';
				$aRespond = $oOutput->getVariables();

				foreach( $aRespond as $key => $val )
					$sResultStatementForPython .= '|@|'.$key.'='.$val;
			}
			else
			{
				foreach( $aMasterReportType as $key=>$val)
				{
					unset($oArgs);
					$oArgs->advertiser_id = $this->_g_sAdNvrAdCustomerId;
					$oArgs->report_type = $val;
					$output = executeQuery('svestudio.getNvrMasterReportLog', $oArgs);

					$this->_debug('dump '.$val.' report log status');
					$this->_debug($output->data);

					$nRecCnt = $output->total_count;
					if( $nRecCnt == 0)
						$sFromDatetime = false;
					else
						$sFromDatetime =  zdate($output->data[$nRecCnt]->sys_regdate,'Y-m-d h:i:s');

					$this->_debug('start retrieve '.$val.' report from NVR ad server since '.$sFromDatetime );
					$oOutput = $this->_gatherNvAdMasterReport($val, $sFromDatetime );
					if( !$oOutput->toBool() )
					{
						if( (int)($oOutput->get('status') / 200) != 1 ) // something wrong - stop
						{
							$sResultStatementForPython = 'result=error';
							$aRespond = $oOutput->getVariables();
							foreach( $aRespond as $key => $val )
								$sResultStatementForPython .= '|@|'.$key.'='.$val;

							$this->_debug('finish retrieve '.$val.' report from NVR ad server since '.$sFromDatetime.', job id '.$sJobId.' with weird error' );
							break;
						}
					}

					$this->_debug('finish retrieve '.$val.' report from NVR ad server since '.$sFromDatetime.', job id '.$sJobId );

					$oArgs->advertiser_id = $this->_g_sAdNvrAdCustomerId;
					$oArgs->report_type = $val;
					$oArgs->job_id = $oOutput->get('id');// $sJobId;
					$oArgs->status = $oOutput->get('status');
					$oArgs->fromdate = $sFromDatetime ? $output->data[$nRecCnt]->sys_regdate : '';
					$output = executeQueryArray('svestudio.insertNvrMasterReportLog', $oArgs);
					$this->_debug('dump each report log update result');
					$this->_debug($output);
				}
			}
			$this->_debug('=============finish master report retrieving process=============');
		}
		return $sResultStatementForPython;
	}
/**
 * @brief
 * https://github.com/naver/searchad-apidoc/tree/master/php-sample
 * http://naver.github.io/searchad-apidoc/#/operations/POST/~2Fstat-reports
 */
	public function retrieveNvAdPerformance() 
	{
		$oModuleInfo = $this->_getMidConfig();
		$aAdAcctSetTitle = unserialize( $oModuleInfo->svestudio_acct_set_title );
		$aNvrAdApiKey = unserialize( $oModuleInfo->svestudio_nvrsearchad_api_key );
		$aNvrAdSecretKey = unserialize( $oModuleInfo->svestudio_nvrsearchad_secret_key );
		$aAdNvrAdCustomerId = unserialize( $oModuleInfo->svestudio_nvrsearchad_customer_id );
		
		// 'Account' report available for the acct owner only
		$aStatReportType = array( 'AD', 'AD_DETAIL', 'AD_CONVERSION', 'AD_CONVERSION_DETAIL', 'ADEXTENSION', 'ADEXTENSION_CONVERSION', 'NAVERPAY_CONVERSION', 'EXPKEYWORD');

		ini_set('default_socket_timeout', 30);
		require_once(_XE_PATH_.'modules/svestudio/restapi.php');

		foreach( $aAdAcctSetTitle as $key=>$val)
		{
			$sNvrAdApiKey = $aNvrAdApiKey[$key];
			$sNvrAdSecretKey = $aNvrAdSecretKey[$key];
			$this->_g_sAdNvrAdCustomerId = $aAdNvrAdCustomerId[$key];
			$this->_setDebugFile();
			$this->_debug('=============start stat report retrieveing process=============');

			$this->_g_sRawDataPath = self::RAW_DATA_PATH.$this->_g_sAdNvrAdCustomerId.'/';
			FileHandler::makeDir($this->_g_sRawDataPath);

			$this->_g_oApi = new RestApi(self::API_URL, $sNvrAdApiKey, $sNvrAdSecretKey, $this->_g_sAdNvrAdCustomerId);
			$aResponse = $this->_g_oApi->DELETE('/master-reports');
var_dump( $aResponse );
echo '<BR><BR>';
			foreach( $aStatReportType as $key=>$report)
			{
echo '<BR><BR>';
var_Dump( $report );
echo '<BR><BR>';
				$sLatestDate = Context::get('stat_start_date'); // 추출 시작일 명령어가 최우선
				if( !$sLatestDate )
				{
					unset($oArgs);
					$oArgs->advertiser_id = $this->_g_sAdNvrAdCustomerId;
					$oArgs->report_type = $report;
					$output = executeQuery('svestudio.getNvrStatReportLog', $oArgs);
//var_Dump( $output );
//echo '<BR><BR>';
					$this->_debug('dump '.$report.' report log status');
					$this->_debug($output->data);

					$nRecCnt = $output->total_count;
					if( $nRecCnt == 0) // 최종 갱신일 기록이 없다면 -1일부터
						$sLatestDate = date('Ymd',strtotime('-1 days'));
					else // 최종 갱신일 기록이 있다면 +1일부터
					{
						$sLatestDate = zdate($output->data[$nRecCnt]->sys_regdate,'Ymd');
						$dtLatest = new DateTime($sLatestDate);
						$dtLatest->modify('+1 day');
						$sLatestDate = $dtLatest->format('Ymd');
					}
				}
//var_Dump( $sLatestDate );
//echo '<BR><BR>';
				
				$sStatDt = $sLatestDate;

				$aStatReq = array(
					'reportTp' => $report,
					'statDt' => $sStatDt
				);
var_Dump( $aStatReq );
//echo '<BR><BR>';
//return;
				
				$aResponse = $this->_g_oApi->POST('/stat-reports', $aStatReq);
//var_dump( $aResponse );
//echo '<BR><BR>';
				$sReportjobId = $aResponse['reportJobId'];
				$sStatus = $aResponse['status'];

echo '<BR>registed : reportJobId = '.$sReportjobId.', status = '.$sStatus.'<BR>';
	
				while( $sStatus == 'REGIST' || $sStatus == 'RUNNING' || $sStatus == 'WAITING' )
				{
					$this->_debug('waiting a report '.$report.' with registed id='.$sReportjobId.', status='.$sStatus);
					sleep(2);
					$aResponse = $this->_g_oApi->GET('/stat-reports/'.$sReportjobId);
					$sStatus = $aResponse['status'];
					$this->_debug('received respond from NVR ad server for '.$report.' with registed id='.$sReportjobId.', status='.$sStatus);
echo '<BR>check : reportJobId = '.$sReportjobId.', status = '.$sStatus.'<BR>';
				}

				if($sStatus == 'BUILT')
				{
					//echo 'downloadUrl => '.$aResponse['downloadUrl'].'<BR>';
					//$oApi->DOWNLOAD($aResponse['downloadUrl'], _XE_PATH_.'files/svestudio/'.$aConfig['CUSTOMER_ID'].'_'.$report.'_'.$sStarDt.'_'.$sReportjobId.'.tsv');

					$sDownloadFile = $this->_g_sRawDataPath.$this->_g_sCurDatetime.'_'.$report.'_'.$sStatDt.'_'.$sReportjobId.'.tsv';
					$this->_debug('download '.$report.' report registed id='.$sReportjobId.' from '.$aResponse['downloadUrl'].' to '.$sDownloadFile );
					$sResp = $this->_g_oApi->DOWNLOAD($aResponse['downloadUrl'], $sDownloadFile);

// 이 단계에서 status 500 intenal server error 처리
echo '<BR><BR>';
var_dump( $sResp );
echo '<BR><BR>';
				}
				else if($sStatus == 'AGGREGATING')  // 처리가 오래 지연되면 어떻게 처리?
					echo '<BR>stat aggregation not yet finished<BR>';
				else //if($sStatus == 'ERROR' || $sStatus == 'NONE' || $sStatus == '400' )
				{
					$this->_debug('received '.$sStatus.' status... done' );
					$sRst = $sStatus; //'failed to build master report'; 'master has no data';
				}
				return $sRst;

				/*$myfile = fopen($sDownloadFile, 'r') or die('Unable to open file!');
				while(!feof($myfile)) 
				{
					echo fgets($myfile) . '<br>';
				}
				fclose($myfile);*/
				//unlink( '/home/singleview125/www/files/AD_DETAIL-20170124.tsv' );
return;
			}
		}
		
	}
/**
 * @brief
 * http://naver.github.io/searchad-apidoc/#/tags/MasterReport
 * remark 동일 작업을 짧은 시간 후에 재요청하면 400 bad request 반환
 */
	private function _gatherNvAdMasterReport($sReportItem,$sFromDatetime=false) 
	{
		$this->_debug('====start _gatherNvAdMasterReport for '.$sReportItem.' report====');

		if( $sReportItem == 'Qi' ) // 품질지수 보고서는 1주일에 한번만
		{
			$nElapsedSecs = time() - strtotime($sFromDatetime);
			if( $nElapsedSecs <= 604800 ) // 60s X 60m X 24h X 7day
			{
				//return new BaseObject('Qi report minimum period is 7 days');
				$oOutput = new BaseObject();
				$oOutput->setMessage('Qi report minimum period is 7 days');
				return $oOutput;
			}
		}

		$aMasterReq['item'] = $sReportItem;
		if( $sFromDatetime )
		{
			$oDatetime = new DateTime($sFromDatetime);//('2017-11-01 23:21:46'); //new DateTime(); //get current
			$aMasterReq['fromTime'] = $oDatetime->format(DateTime::ATOM);//'2017-10-01T23:21:46+09:00';//"2017-10-07T00:00:00Z";
		}
		$oOutput = $this->_g_oApi->POST('/master-reports', $aMasterReq);
		$sId = $oOutput->get('id');//$aResponse['id'];
		$sStatus = $oOutput->get('status');//$aResponse['status'];
		$this->_debug('registed : id='.$sId.', status='.$sStatus);

		while($sStatus == 'REGIST' || $sStatus == 'RUNNING') 
		{
			$this->_debug('waiting a report '.$sReportItem.' with registed id='.$sId.', status='.$sStatus);
			sleep(2); // delay 2 secs
			$oOutput = $this->_g_oApi->GET('/master-reports/'.$sId);
			$sStatus = $oOutput->get('status');//$aResponse['status'];
			$this->_debug('received respond from NVR ad server for '.$sReportItem.' with registed id='.$sId.', status='.$sStatus);
		}

		if($sStatus == 'BUILT')
		{
			$sReportType = $sFromDatetime ? 'delta':'full';
			$sDownloadFile = $this->_g_sRawDataPath.$this->_g_sCurDatetime.'_'.$sReportItem.'_'.$sReportType.'_'.$sId.'.tsv';
			$this->_debug('download '.$sReportItem.' report registed id='.$sId.' from '.$oOutput->get['downloadUrl'].' to '.$sDownloadFile );
			//$this->_g_oApi->DOWNLOAD($aResponse['downloadUrl'], $sDownloadFile);
			$this->_g_oApi->DOWNLOAD($oOutput->get('downloadUrl'), $sDownloadFile);
			//$sRst = $sId;
		}
		else //if($sStatus == 'ERROR' || $sStatus == 'NONE' || $sStatus == '400' )
		{
			$this->_debug('received '.$sStatus.' status... done' );
			//$sRst = $sStatus; //'failed to build master report'; 'master has no data';
		}
		$this->_debug('====finish _gatherNvAdMasterReport for '.$sReportItem.'====');
		//return $sRst;
		return $oOutput;
	}
/**
 * @brief
 */
	public function registerNvAdStructure() 
	{
		$oModuleInfo = $this->_getMidConfig();
		$aAdAcctSetTitle = unserialize( $oModuleInfo->svestudio_acct_set_title );
		$aAdNvrAdCustomerId = unserialize( $oModuleInfo->svestudio_nvrsearchad_customer_id );

		ini_set('default_socket_timeout', 30);
		
		foreach( $aAdAcctSetTitle as $key=>$val)
		{
			$this->_g_sAdNvrAdCustomerId = $aAdNvrAdCustomerId[$key];
			$this->_setDebugFile();
			$this->_debug('=============start master report registration process=============');
			$this->_g_sRawDataPath = self::RAW_DATA_PATH.$this->_g_sAdNvrAdCustomerId.'/';
			
			unset($oArgs);
			$oArgs->advertiser_id = $this->_g_sAdNvrAdCustomerId;
			$oArgs->is_registered = 'N';
			$output = executeQueryArray('svestudio.getNvrMasterReportUnregistered', $oArgs);
			
			if( count( $output->data ) )
			{
				foreach( $output->data as $key=>$val)
				{
					$nMasterReportSrl = (int)$val->master_report_srl;
					$sReportType = $val->report_type;
					$sJobId = $val->job_id;
					$sSysRegdate = zdate($val->sys_regdate,'Ymd');
					$isFullReport = strlen( $val->fromdate ) ? false : true;

					$this->_debug('start register '.$sReportType.' report, job id '.$sJobId );
					if( $sJobId != 'ERROR' && $sJobId != 'NONE' && $sJobId != '400')
					{
						unset($oArgs);
						$bSuccess = $this->_registerNvAdAdReport($sSysRegdate, $sReportType, $sJobId, $isFullReport );
					}
					if( $bSuccess )
					{
						unset($oArgs);
						$oArgs->master_report_srl = $nMasterReportSrl;
						$oArgs->is_registered = 'Y';
						$output = executeQueryArray('svestudio.updateNvrMasterReportRegistered', $oArgs);
						$this->_debug('dump each report log update result');
						$this->_debug($output);
						$this->_debug('succeed to register '.$sReportType.' report, job id '.$sJobId );
					}
					else
						$this->_debug('failed to register '.$sReportType.' report, job id '.$sJobId );
				}
			}
			$this->_debug('=============finish master report registration process=============');
		}
	}
/**
 * @brief
 */
	private function _registerNvAdAdReport($sSysRegdate=null, $sReportTitle=null, $sJobId=null,$bFullReport=false)
	{
		if( !$sReportTitle )
		{
			echo '<BR>invalid report title<BR>';
			return false;
		}
		if( !$sJobId )
		{
			echo '<BR>invalid job id<BR>';
			return false;
		}

		if( !$sSysRegdate )
		{
			echo '<BR>invalid system registration date<BR>';
			return false;
		}

		require_once(_XE_PATH_.'modules/svestudio/conf/nvr_ad_master_report_'.$sReportTitle.'.php');
		
		if( $bFullReport )
			$sReportType = 'full';
		else
			$sReportType = 'delta';

		$sDataFile = $this->_g_sRawDataPath.$sSysRegdate.'_'.$sReportTitle.'_'.$sReportType.'_'.$sJobId.'.tsv';
		$this->_debug('====start _registerNvAdAdReport for '.$sReportTitle.' from raw data file '.$sDataFile.'====');

		$oRawDatafile = fopen($sDataFile, 'r'); //or die('Unable to open file!');
		if( $oRawDatafile )
		{
			while(!feof($oRawDatafile)) 
			{
				$sTempLine = fgets($oRawDatafile);
				$aTempLine = explode( "\t", $sTempLine );
				if( count( $aTempLine ) == 1 )
					return false;

				foreach( $aVariable as $key=>$val)
				{
					if( $val->type == 'datetime' )
						$oArgs->{$val->title}=strlen($aTempLine[$key])?date('Ymdhis', strtotime($aTempLine[$key])):'';
					else
						$oArgs->{$val->title} = $aTempLine[$key];
				}
				$output=executeQuery('svestudio.insertNvr'.$sQueryTitle,$oArgs);
				if( !$output->toBool() )
				{
					$this->_debug('error occured while registration dump arguments');
					$this->_debug($oArgs);
				}
			}
			fclose($oRawDatafile);
		}
		else
			$this->_debug('Unable to open file: '.$sDataFile);

		$this->_debug('====finish _registerNvAdAdReport for '.$sReportTitle.' from raw data file '.$sDataFile.'====');
	}
/**
 * @brief
 */
	private function _setDebugFile()
	{
		$this->_g_sDebugFile = _XE_PATH_.'files/svestudio/'.$this->_g_sAdNvrAdCustomerId.'/'.$this->_g_sCurDatetime.'_job_log.php';
	}
/**
 * @brief
 */
	private function _debug($debug_output = NULL)
	{
		//if(!$this->_g_bDebug ||!$oMsgObj )
		//	return;

		$bt = debug_backtrace();
		if(is_array($bt))
		{
			$bt_debug_print = array_shift($bt);
			$bt_called_function = array_shift($bt);
		}
		$file_name = str_replace(_XE_PATH_, '', $bt_debug_print['file']);
		$line_num = $bt_debug_print['line'];
		$function = $bt_called_function['class'] . $bt_called_function['type'] . $bt_called_function['function'];

		$print = array();
		if(!file_exists($this->_g_sDebugFile)) $print[] = '<?php exit() ?>';

		$type = gettype($debug_output);
		if(!in_array($type, array('array', 'object', 'resource')))
			$print[] = 'log: ' . var_export($debug_output, TRUE);
		else
			$print[] = 'object : ' . trim(preg_replace('/\r?\n/', "\n" . '        ', print_r($debug_output, true)));

		$print[] = sprintf("[%s %s:%d] %s() - mem(%s)", date('Y-m-d H:i:s'), $file_name, $line_num, $function, FileHandler::filesize(memory_get_usage()));;
		//$print[] = str_repeat('=', 80);

		//$backtrace_args = defined('\DEBUG_BACKTRACE_IGNORE_ARGS') ? \DEBUG_BACKTRACE_IGNORE_ARGS : 0;
		//$backtrace = debug_backtrace($backtrace_args);

		//if(count($backtrace) > 1 && $backtrace[1]['function'] === 'debugPrint' && !$backtrace[1]['class'])
		//	array_shift($backtrace);
		
		//$print[] = '        - ' . $backtrace[0]['file'] . ' : ' . $backtrace[0]['line'];
		$print[] = PHP_EOL;
		@file_put_contents($this->_g_sDebugFile, implode(PHP_EOL, $print), FILE_APPEND|LOCK_EX);
	}
/**
 * @brief
 * Qi report는 delta를 지원하지 않아서 예외처리
 */
/*	private function _gatherNvAdStatReport($sReportItem) 
	{
		$this->_debug('====start _gatherNvAdStatReport for '.$sReportItem.' report====');
		//if( $sReportItem == 'Qi' )
		//{
		//	$nElapsedSecs = time() - strtotime($sFromDatetime);
		//	if( $nElapsedSecs > 604800 ) // 60s X 60m X 24h X 7day
		//		$sRst = $this->_requestNvrAdMasterReport($sReportItem,$sFromDatetime);
		//	else
		//		$sRst = 'NONE';
		//}
		//else
		//	$sRst = $this->_requestNvrAdMasterReport($sReportItem,$sFromDatetime);

		$sRst = $this->_requestNvrAdMasterReport($sReportItem,$sFromDatetime);

		$this->_debug('====finish _gatherNvAdStatReport for '.$sReportItem.'====');
		return $sRst;
	}*/
}