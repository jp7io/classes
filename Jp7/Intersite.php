<?php

namespace Jp7;

use Illuminate\Support\Str;

/**
 * Configurations for a site.
 *
 * @version (2008/07/30)
 */
class Intersite
{
    const QA = 'QA';
    const PRODUCTION = 'Produção';
    const DEVELOPMENT = 'Desenvolvimento';

    /**
     * Array of servers for this site.
     *
     * @var array
     */
    public $servers = [];
    /**
     * Array of languages for this site.
     *
     * @var array
     */
    public $langs = [];
    /**
     * Current server.
     *
     * @var object
     */
    public $server;
    /**
     * Current Database.
     *
     * @var object
     */
    public $db;
    /**
     * Current Url.
     *
     * @var object
     */
    public $url;
    /**
     * Default language.
     *
     * @var string
     */
    public $lang_default = 'pt-br';
    /**
     * Partners credited beside the copyright in InterAdmin's menu, as ['name' => , 'url' => ].
     *
     * @var array
     */
    public $partners = [];

    protected static $instance = null;

    /**
     * Checks if the server type is PRODUCAO.
     *
     * @return bool
     */
    public function isProduction()
    {
        return $this->server->type === self::PRODUCTION;
    }
    /**
     * Checks if the server type is QA.
     *
     * @return bool
     */
    public function isQa()
    {
        return $this->server->type === self::QA;
    }

    /**
     * Checks if the server type is PRODUCAO.
     *
     * @return bool
     */
    public function isDevelopment()
    {
        return $this->server->type === self::DEVELOPMENT;
    }

    /**
     * Returns the first server which has a given type.
     *
     * @param string $type Type of the server, such as self::PRODUCAO, self::QA or self::DESENVOLVIMENTO.
     *
     * @return Jp7\Interadmin\Record
     */
    public function getFirstServerByType($type)
    {
        foreach ($this->servers as $server) {
            if ($server->type == $type) {
                return $server;
            }
        }
    }

    public static function config()
    {
        return self::$instance;
    }

    public static function setConfig(self $instance)
    {
        self::$instance = $instance;
    }

    public function setServer($server)
    {
        $this->server = $server;

        // Set variables that depend on the server
        $this->db = clone $this->server->db;
        $this->db->prefix = 'interadmin_'.$this->name_id;

        foreach ((array) $this->server->vars as $var => $value) {
            $this->$var = $value;
        }

        $protocol = 'http'.(isset($_SERVER['HTTPS']) ? 's' : '');
        $this->url = $protocol.'://'.$this->server->host.'/'.$this->server->path;
        $this->url = Str::finish($this->url, '/');

        foreach ($this->langs as $sigla => $lang) {
            if ($lang->default) {
                $this->lang_default = $sigla;
                break;
            }
        }
    }

    public function start()
    {
        $host = self::getHost();

        if (isset($this->servers[$host])) {
            $this->setServer($this->servers[$host]);
        }

        self::setConfig($this);
    }

    public static function getHost()
    {
        return parse_url(config('app.url'))['host'];
    }

    public static function __set_state($array)
    {
        $instance = new self();
        foreach ($array as $key => $value) {
            $instance->$key = $value;
        }

        return $instance;
    }

    public function export()
    {
        $code = var_export($this, true);

        $code = preg_replace("/' => \n\s+/", "' => ", $code);
        $code = str_replace('stdClass::__set_state', '(object)', $code);

        return $code;
    }
}
