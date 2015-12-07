<?php

define('APP_VERSION', '1.0.0');
define('DS', DIRECTORY_SEPARATOR);
define('STATE', 'development');

define('DB_HOST', '');
define('DB_USER', '');
define('DB_NAME', '');
define('DB_PASS', '');
define('EXT', '.php');

define('ROOT', dirname(__FILE__));


if ( ! function_exists('is_php'))
{
	function is_php($version = '5.0.0')
	{
		static $_is_php;
		$version = (string)$version;

		if ( ! isset($_is_php[$version]))
		{
			$_is_php[$version] = (version_compare(PHP_VERSION, $version) < 0) ? FALSE : TRUE;
		}

		return $_is_php[$version];
	}
}


/**
 * langsung keluarin error jika php tidak mendukung
 */
is_php('5.5.0') or die('php tidak mendukung');

$errorCode = [
	503 => "Service Unavailable",
	404 => "Not Found",
	200 => "OK",
	500 => "Internal Server Error",
];

$contentType = [
	'json' => "application/json",
	'html' => "text/html",
	'js' => "text/javascript",
];

if ( ! function_exists('resp')) {
	function resp($status = 503, $content = 'json', $error = false)
	{
		global $contentType;

		global $errorCode;

		$message = $errorCode[$status];

		$type = $contentType[$content];

		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		header('Content-Type: ' . $type);
		header( "HTTP/1.1 $status $message", true, $status );
		
		if ($error === true ) {
			echo json_encode(["error"=>true, "message"=> $message]);
			exit;
		}
	}
}

if (! function_exists('ambilFile')) {
	function ambilFile($nama ='')
	{
		if (file_exists(ROOT . $nama . EXT)) {
			include ROOT . $nama . EXT;
		} else {
			resp(503,'json',true);
		}
	}
}

/**
 * @author https://github.com/nikic/DB 
 */
class DB
{
    protected static $instance = null;

    final private function __construct() {}
    final private function __clone() {}


    /**
     * @return PDO
     */
    public static function instance() {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
                    DB_USER,
                    DB_PASS
                );
                self::$instance->setAttribute(
                        PDO::ATTR_ERRMODE, 
                        PDO::ERRMODE_EXCEPTION
                );
            } catch (PDOException $e) {
                die('Database connection could not be established.');
            }
        }

        return self::$instance;
    }

    /**
     * @return PDOStatement
     */
    public static function q($query) {
        if (func_num_args() == 1) {
            return self::instance()->query($query);
        }

        $args = func_get_args();
        return self::instance()->query(self::autoQuote(array_shift($args), $args));
    }

    public static function x($query) {
        if (func_num_args() == 1) {
            return self::instance()->exec($query);
        }

        $args = func_get_args();
        return self::instance()->exec(self::autoQuote(array_shift($args), $args));
    }

    public static function autoQuote($query, array $args) {
        $i = strlen($query) - 1;
        $c = count($args);

        while ($i--) {
            if ('?' === $query[$i] && false !== $type = strpos('sia', $query[$i + 1])) {
                if (--$c < 0) {
                    throw new InvalidArgumentException('Too little parameters.');
                }

                if (0 === $type) {
                    $replace = self::instance()->quote($args[$c]);
                } elseif (1 === $type) {
                    $replace = intval($args[$c]);
                } elseif (2 === $type) {
                    foreach ($args[$c] as &$value) {
                        $value = self::instance()->quote($value);
                    }
                    $replace = '(' . implode(',', $args[$c]) . ')';
                }

                $query = substr_replace($query, $replace, $i, 2);
            }
        }

        if ($c > 0) {
            throw new InvalidArgumentException('Too many parameters.');
        }

        return $query;
    }

    public static function __callStatic($method, $args) {
        return call_user_func_array(array(self::instance(), $method), $args);
    }
}