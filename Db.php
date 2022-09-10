<?php
namespace Utility;

class Db extends \mysqli
    {
        private static $instance;

        /**
         * You can set defaults for these parameters in case you have global constants holding the values
        */
        public static function Get($host, $userName, $password, $database): self
        {
            if (self::$instance === null) {
                self::$instance = new self($host, $userName, $password, $database);
                if (self::$instance->connect_error) {
                    \Lib\Skipper\StandardLogger::Log(new \Exception('could not connect to the database'));
                    throw new \Exception("DB connection failed: " . self::$instance->connect_error);
                }
                self::$instance->set_charset('latin1');
            }
            return self::$instance;
        }

        public static function CanConnect()
        {
            $Conn = new self(\R\SqlConfig::Host, \R\SqlConfig::User, \R\SqlConfig::Password, \R\SqlConfig::Database);
            if (!$Conn) {
                return false;
            }
            $Conn->close();
            return true;
        }

        public function PrepareAndExecute(string $Query, array $Args = null, bool $AsArrayObjects = false, string $TableClassName = null)
        {
            $stmt = $this->prepare($Query);
            if (!$stmt) {
                if(\R\Mode::$Value === 'DEBUG') {
                }
                throw new \Exception("Unable to prepare sql statement");
            }
            if ($Args !== null) {
                $params = [];
                $types = array_reduce($Args, function ($string, $arg) use (&$params) {
                    $params[] = &$arg;
                    if (is_float($arg)) {
                        $string .= 'd';
                    } elseif (is_integer($arg)) {
                        $string .= 'i';
                    } elseif (is_string($arg)) {
                        $string .= 's';
                    } else {
                        $string .= 'b';
                    }
                    return $string;
                });
                array_unshift($params, $types);
                if (!call_user_func_array([$stmt, 'bind_param'], $params)) {
                    throw new \Exception("Unable to bind params");
                }
            }

            $StmtResult = $stmt->execute();
            if (!$StmtResult) {
                throw new \Lib\Exceptions\DbError($stmt->error, $stmt->errno);
            }

            $result = $stmt->get_result();
            if (!($result instanceof \mysqli_result)) {
                if ($StmtResult) {
                    if ($stmt->insert_id > 0) {
                        return $stmt->insert_id;
                    } else if ($stmt->affected_rows > 0) {
                        return $stmt->affected_rows;
                    } else {
                        return $StmtResult;
                    }
                }
                return false;
            }

            if ($result->num_rows > 0) {
                $Table = [];
                if ($AsArrayObjects) {
                    while ($Row = $result->fetch_object($TableClassName)) {
                        $Table[] = $Row;
                    }
                } else {
                    while ($Table[] = $result->fetch_assoc()) ;
                    \array_pop($Table);
                }
                return $Table;
            } else if ($StmtResult) {
                return [];
            } else {
                return null;
            }
        }
    }
