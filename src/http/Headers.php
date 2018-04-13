<?php
/**
 * Created by PhpStorm.
 * User: PC
 * Date: 2018/4/13
 * Time: 13:18
 */
namespace moon\http;

use moon\Collection;
use moon\http\interfaces\HeadersInterface;

class Headers extends Collection implements HeadersInterface {

    /**
     * Special HTTP headers that do not have the "HTTP_" prefix
     *
     * @var array
     */
    protected static $special = [
        'CONTENT_TYPE' => 1,
        'CONTENT_LENGTH' => 1,
        'PHP_AUTH_USER' => 1,
        'PHP_AUTH_PW' => 1,
        'PHP_AUTH_DIGEST' => 1,
        'AUTH_TYPE' => 1,
    ];


    public function add($key, $value)
    {
        // TODO: Implement add() method.
    }

    public function normalizeKey($key)
    {
        // TODO: Implement normalizeKey() method.
    }

    /**
     * If HTTP_AUTHORIZATION does not exist tries to get it from
     * getallheaders() when available.
     *
     * @param Env $env The Slim application Environment
     *
     * @return Env
     */

    public static function determineAuthorization(Env $env)
    {
        $authorization = $env->get('HTTP_AUTHORIZATION');

        if (empty($authorization) && is_callable('getallheaders')) {
            $headers = getallheaders();
            $headers = array_change_key_case($headers, CASE_LOWER);
            if (isset($headers['authorization'])) {
                $env->set('HTTP_AUTHORIZATION', $headers['authorization']);
            }
        }

        return $env;
    }

    /**
     * Create new headers collection with data extracted from
     * the application Environment object
     *
     * @param Env $env The Slim application Environment
     *
     * @return $this
     */
    public static function createFromEnvironment(Env $env)
    {
        $data = [];
        $env = self::determineAuthorization($env);
        foreach ($env as $key => $value) {
            $key = strtoupper($key);
            if (isset(static::$special[$key]) || strpos($key, 'HTTP_') === 0) {
                if ($key !== 'HTTP_CONTENT_LENGTH') {
                    $data[$key] =  $value;
                }
            }
        }

        return new static($data);
    }

}