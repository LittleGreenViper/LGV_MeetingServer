<?php
/***************************************************************************************************************************/
/**
    This is a PDO abstraction class, derived from the Badger Hardened Baseline Database Component
    
    This uses transactions, and defaults to a standard localhost MySQL server (can be other types of servers).
    
    © <a href="https://github.com/RiftValleySoftware/badger/blob/master/db/co_pdo.class.php">Original Copyright 2021, The Great Rift Valley Software Company</a>
    © Copyright 2022, <a href="https://littlegreenviper.com">Little Green Viper Software Development LLC</a>
    
    LICENSE:
    
    MIT License
    
    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
    files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
    modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
    IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
    CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

    The Great Rift Valley Software Company: https://riftvalleysoftware.com
*/
defined( 'LGV_DB_CATCHER' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

/***************************************************************************************************************************/
/**
    \brief This class provides a genericized interface to the <a href="http://us.php.net/pdo">PHP PDO</a> toolkit.
 */
class LGV_MeetingServer_PDO {
	/// \brief Internal PDO object
	private $_pdo = NULL;
	/// \brief The type of PDO driver we are configured for.
	var $driver_type = NULL;
	/// \brief This holds the integer ID of the last AUTO_INCREMENT insert.
	var $last_insert = NULL;
    
	/// \brief Default fetch mode for internal PDOStatements
	private $fetchMode = PDO::FETCH_ASSOC;

    /***********************************************************************************************************************/
    /***********************/
	/**
		\brief Initializes connection param class members.
		
		Must be called BEFORE any attempts to connect to or query a database. This uses UTF8, as the charset.
		
		Will destroy previous connection (if one exists).
	*/
	public function __construct(    $database,			    ///< database name (required)
	                                $user = NULL,		    ///< user, optional
                                    $password = NULL,	    ///< password, optional
                                    $driver = 'mysql',	    ///< database server type (default is 'mysql')
                                    $host = '127.0.0.1',    ///< database server host (default is 127.0.0.1)
                                    $port = 3306 	        ///< database TCP port (default is 3306)
								) {
		$this->_pdo = NULL;
		$this->driver_type = $driver;
		$this->last_insert = NULL;
		
        $dsn = $driver . ':host=' . $host . ';dbname=' . $database . ';port=' . strval($port);
        
		try {
            $this->_pdo = new PDO($dsn, $user, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->_pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
            $this->_pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        } catch (PDOException $exception) {
			throw new Exception(__METHOD__ . '() ' . $exception->getMessage());
        }
	}

    /***********************/
	/**
		\brief Wrapper for preparing and executing a PDOStatement that does not return a resultset e.g. INSERT or UPDATE SQL statements

		See PDO documentation about prepared queries.
		
		If there isn't already a database connection, it will "lazy load" the connection.
		
		\throws Exception   thrown if internal PDO exception is thrown
		\returns            true if execution is successful.
	*/
	public function preparedExec(   $sql,				///< same as kind provided to PDO::prepare()
								    $params = array()	///< same as kind provided to PDO::prepare()
						        )
	{
		$this->last_insert = NULL;
		try {
			if ('pgsql' == $this->driver_type) {
			    if (strpos($sql, 'RETURNING id;')) {
			        $response = $this->preparedQuery($sql, $params);
                    $this->last_insert = intval($response[0]['id']);
			        return true;
			    }
			}
			
			$sql = str_replace(' RETURNING id', '', $sql);
            $this->_pdo->beginTransaction(); 
            $stmt = $this->_pdo->prepare($sql);
            $stmt->execute($params);
			if ('pgsql' != $this->driver_type) {
                $this->last_insert = $this->_pdo->lastInsertId();
            }
            $this->_pdo->commit();
		
            return true;
		} catch (PDOException $exception) {
		    $this->last_insert = NULL;
            $this->_pdo->rollback();
			throw new Exception(__METHOD__ . '() ' . $exception->getMessage());
		}
		
        return false;
	}

    /***********************/
	/**
		\brief Wrapper for preparing and executing a PDOStatement that returns a resultset e.g. SELECT SQL statements.

		Returns a multidimensional array depending on internal fetch mode setting ($this->fetchMode)
		See PDO documentation about prepared queries.

		Fetching key pairs- when $fetchKeyPair is set to true, it will force the returned
		array to be a one-dimensional array indexed on the first column in the query.
		Note- query may contain only two columns or an exception/error is thrown.
		See PDO::PDO::FETCH_KEY_PAIR for more details

		\throws Exception   thrown if internal PDO exception is thrown
		\returns            associative array of results.
	*/
	public function preparedQuery(  $sql,					///< same as kind provided to PDO::prepare()
									$params = array(),		///< same as kind provided to PDO::prepare()
									$fetchKeyPair = false   ///< See description in method documentation
								) {
		$this->last_insert = NULL;
		try {
            $this->_pdo->beginTransaction(); 
            $stmt = $this->_pdo->prepare($sql);
            $stmt->setFetchMode($this->fetchMode);
            $stmt->execute($params);
            $this->_pdo->commit();
            
            $ret = NULL;
            
            if ($fetchKeyPair) {
                $ret = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            } else {
                $ret = $stmt->fetchAll();
            }
            
            return $ret;
		} catch (PDOException $exception) {
		    $this->last_insert = NULL;
            $this->_pdo->rollback();
			throw new Exception(__METHOD__ . '() ' . $exception->getMessage());
		}
		
        return false;
	}
};
