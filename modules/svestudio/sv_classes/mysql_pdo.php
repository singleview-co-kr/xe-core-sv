<?php
class svMysqlPdo
{
	var $_g_oDbInfo = null;
	var $_g_oConn = null;
	var $_g_aQueryType = array( 'select'=>'/^[g][e][t]\w+/', 'update'=>'/^[u][p][d][a][t][e]\w+/', 'insert'=>'/^[i][n][s][e][r][t]\w+/','delete'=>'/^[d][e][l][e][t][e]\w+/');
/**
 * @brief PDO on top, mysqli otherwise
 */
	public function __construct( $oDbInfo )
	{
		$this->_g_oDbInfo = $oDbInfo;

		$servername = $oDbInfo->master_db["db_hostname"];
		$username = $oDbInfo->master_db["db_userid"];
		$password = $oDbInfo->master_db["db_password"];
		$dbname = $oDbInfo->master_db["db_database"];

		if (!defined('PDO::MYSQL_ATTR_LOCAL_INFILE'))  // PDO unavailable
		{
			$this->_g_oConn = new mysqli($servername, $username, $password, $dbname);
			$this->_g_oConn->set_charset('utf8');
		}
		else
		{
			$this->_g_oConn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
			$this->_g_oConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->_g_oConn->exec('set names utf8');
		}
	}
	public function __destruct()
	{
		if (!defined('PDO::MYSQL_ATTR_LOCAL_INFILE'))  // PDO unavailable
			$this->_g_oConn->close();
		else
			$this->_g_oConn = null;
    }
/**
 * @brief 
 */
	private function __preProcSqlStmt($sSqlFilename, $sSql)
	{
		$sSql = str_replace('`', '', $sSql);
		foreach( $this->_g_aQueryType as $sQueryType => $sRegex )
		{
			preg_match($sRegex, $sSqlFilename, $matches, PREG_OFFSET_CAPTURE, 0);
			if( array_key_exists( 0, $matches) )
				break;
			$matches = null;
		}

		if( $sQueryType == 'select' || $sQueryType == 'delete' ) # 쿼리 종류 확인 SELECT FROM / DELETE FROM
			$sRegex = '/(?<=[fF][rR][oO][mM]\s)\w+/';
		elseif( $sQueryType == 'update' ) # 쿼리 종류 확인 UPDATE 
			$sRegex = '/(?<=[uU][pP][dD][aA][tT][eE]\s)\w+/';
		elseif( $sQueryType == 'insert' ) # 쿼리 종류 확인 INSERT INTO
			$sRegex = '/(?<=[iI][nN][tT][oO]\s)\w+/';
		
		preg_match($sRegex, $sSql, $matches, PREG_OFFSET_CAPTURE, 0);
		$sTableName = $matches[0][0];
		$sSql = str_replace($sTableName, $this->_g_oDbInfo->master_db['db_table_prefix'].$sTableName, $sSql);
		return array($sQueryType, $sSql);
	}
/**
 * @brief 
 */
	public function executeDynamicQuery($sSqlFilename, $aParam = null)
	{
		$sSqlFileAbsPath = _XE_PATH_.'modules/svestudio/sv_queries/'.$sSqlFilename.'.php';
		if(is_readable($sSqlFileAbsPath))
			require($sSqlFileAbsPath);
		// $sSql is in $sSqlFileAbsPath.php
		$aSqlRst = $this->__preProcSqlStmt($sSqlFilename, $sSql);
		$sQueryType = $aSqlRst[0];
		$sSql = $aSqlRst[1];
		unset($aSqlRst);
		
		$aRst = Array();
		if(!defined('PDO::MYSQL_ATTR_LOCAL_INFILE'))  // PDO unavailable
		{
			$oRst = $this->_g_oConn->query($sSql);
			if($oRst)
			{
				if( $sQueryType == 'insert' )
					;
				else if( $sQueryType == 'select' )
				{
					//var_dump( $oRst->num_rows );
					while ($row = $oRst->fetch_array(MYSQLI_ASSOC))
						$aRst[] = $row;
				}
				$oRst->close(); // free result set
			}
		}
		else // PDO available // https://phpdelusions.net/pdo_examples
		{
			try 
			{
				// LIMIT에 prepared statement 사용 시 숫자가 문자열 처리되는 문제 해결
				// https://stackoverflow.com/questions/10014147/limit-keyword-on-mysql-with-prepared-statement
				$this->_g_oConn->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
				//var_Dump($sSql);
				$stmt = $this->_g_oConn->prepare($sSql);
				$stmt->execute();
				
				if( $sQueryType == 'insert' )
					;
				else if( $sQueryType == 'select' )
				{
					// set the resulting array to associative
					$oRst = $stmt->fetchAll(PDO::FETCH_ASSOC);
					foreach( $oRst as $row )
						$aRst[] = $row;
				}
			}
			catch(PDOException $e) 
			{
				echo "Error: ".$e->getMessage();
			}
		}
		return $aRst;
	}
/**
 * @brief 
 */
	public function executeQuery($sSqlFilename, $aParam = null)
	{
		$sSqlFileAbsPath = _XE_PATH_.'modules/svestudio/sv_queries/'.$sSqlFilename.'.sql';
		$fp = fopen($sSqlFileAbsPath, 'r');
		$sSql = fread($fp,filesize($sSqlFileAbsPath));
		fclose($fp);

		$aSqlRst = $this->__preProcSqlStmt($sSqlFilename, $sSql);
		$sQueryType = $aSqlRst[0];
		$sSql = $aSqlRst[1];
		unset($aSqlRst);
		/*$sSql = str_replace('`', '', $sSql);
		$aQueryType = array( 'select'=>'/^[g][e][t]\w+/', 'update'=>'/^[u][p][d][a][t][e]\w+/', 'insert'=>'/^[i][n][s][e][r][t]\w+/','delete'=>'/^[d][e][l][e][t][e]\w+/');
		foreach( $aQueryType as $sQueryType => $sRegex )
		{
			preg_match($sRegex, $sSqlFilename, $matches, PREG_OFFSET_CAPTURE, 0);
			if( array_key_exists( 0, $matches) )
				break;
			$matches = null;
		}

		if( $sQueryType == 'select' || $sQueryType == 'delete' ) # 쿼리 종류 확인 SELECT FROM / DELETE FROM
			$sRegex = '/(?<=[fF][rR][oO][mM]\s)\w+/';
		elseif( $sQueryType == 'update' ) # 쿼리 종류 확인 UPDATE 
			$sRegex = '/(?<=[uU][pP][dD][aA][tT][eE]\s)\w+/';
		elseif( $sQueryType == 'insert' ) # 쿼리 종류 확인 INSERT INTO
			$sRegex = '/(?<=[iI][nN][tT][oO]\s)\w+/';
		
		preg_match($sRegex, $sSql, $matches, PREG_OFFSET_CAPTURE, 0);
		$sTableName = $matches[0][0];
		$sSql = str_replace($sTableName, $this->_g_oDbInfo->master_db['db_table_prefix'].$sTableName, $sSql);*/

		$aRst = Array();
		if(!defined('PDO::MYSQL_ATTR_LOCAL_INFILE'))  // PDO unavailable
		{
			if( is_null(  $aParam ) ) // non prepared statement
			{
				$oRst = $this->_g_oConn->query($sSql);
				if($oRst)
				{
					if( $sQueryType == 'insert' )
						;
					else if( $sQueryType == 'select' )
					{
						//var_dump( $oRst->num_rows );
						while ($row = $oRst->fetch_array(MYSQLI_ASSOC))
							$aRst[] = $row;
					}
					$oRst->close(); // free result set
				}
			}
			else // prepared statement
			{
				$stmt = $this->_g_oConn->stmt_init();
				$stmt = $this->_g_oConn->prepare($sSql);
				// This function binds the parameters to the SQL query and tells the database what the parameters are. 
				// The argument may be one of four types:
				// i - integer, d - double, s - string, b - BLOB
				// We must have one of these for each parameter.
				// By telling mysql what type of data to expect, we minimize the risk of SQL injections.
				$types = '';
				foreach( $aParam as $key=>$val )
				{
					switch( gettype($val) )
					{
						case 'integer':
							$types .= 'i';
							break;
						case 'string':
							$types .= 's';
							break;
					}
				}
				if($types && $aParam)
				{
					$bind_names[] = $types;
					for ($i=0; $i<count($aParam);$i++) 
					{
						$bind_name = 'bind' . $i;
						$$bind_name = $aParam[$i];
						$bind_names[] = &$$bind_name;
					}
					$return = @call_user_func_array(array($stmt,'bind_param'),$bind_names);
				}
				$stmt->execute();
				if( $sQueryType == 'insert' )
					;
				else if( $sQueryType == 'select' )
				{
					$result = $stmt->get_result();
					while($row = $result->fetch_assoc())
						$aRst[] = $row;
				}
				$stmt->close(); // explicit close recommended 
			}
		}
		else // PDO available // https://phpdelusions.net/pdo_examples
		{
			try 
			{
				// LIMIT에 prepared statement 사용 시 숫자가 문자열 처리되는 문제 해결
				// https://stackoverflow.com/questions/10014147/limit-keyword-on-mysql-with-prepared-statement
				$this->_g_oConn->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
				$stmt = $this->_g_oConn->prepare($sSql);
				$stmt->execute($aParam);
				
				if( $sQueryType == 'insert' )
					;
				else if( $sQueryType == 'select' )
				{
//					$oRst = $stmt->fetchAll();
//					// set the resulting array to associative
//					$result = $stmt->setFetchMode(PDO::FETCH_ASSOC); 
					
					// set the resulting array to associative
					$oRst = $stmt->fetchAll(PDO::FETCH_ASSOC);
					foreach( $oRst as $row )
						$aRst[] = $row;
				}
			}
			catch(PDOException $e) 
			{
				echo "Error: ".$e->getMessage();
			}
		}
		return $aRst;
	}
}
?>