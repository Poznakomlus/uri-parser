<?php
/**
 * League.Url (http://url.thephpleague.com)
 *
 * @link      https://github.com/thephpleague/url/
 * @copyright Copyright (c) 2013-2015 Ignace Nyamagana Butera
 * @license   https://github.com/thephpleague/url/blob/master/LICENSE (MIT License)
 * @version   4.0.0
 * @package   League.url
 */
namespace League\Url\Utilities;

use InvalidArgumentException;
use League\Url;
use League\Url\Interfaces;

/**
 * A Trait to help parse an URL
 * and create a new League\Url\Url instance
 *
 * @package League.url
 * @since   4.0.0
 */
trait UrlFactory
{
    /**
     * A Factory trait fetch info from Server environment variables
     */
    use ServerInfo;

    /**
     * Create a new League\Url\Url object from the environment
     *
     * @param array                          $server the environment server typically $_SERVER
     * @param Interfaces\SchemeRegistry|null $registry
     *
     * @throws \InvalidArgumentException If the URL can not be parsed
     *
     * @return Url\Url
     */
    public static function createFromServer(array $server, Interfaces\SchemeRegistry $registry = null)
    {
        return static::createFromUrl(
            static::fetchServerScheme($server).'//'
            .static::fetchServerUserInfo($server)
            .static::fetchServerHost($server)
            .static::fetchServerPort($server)
            .static::fetchServerRequestUri($server),
            $registry
        );
    }

    /**
     * Create a new League\Url\Url instance from a string
     *
     * @param string                         $url
     * @param Interfaces\SchemeRegistry|null $registry
     *
     * @throws \InvalidArgumentException If the URL can not be parsed
     *
     * @return Url\Url
     */
    public static function createFromUrl($url, Interfaces\SchemeRegistry $registry = null)
    {
        return static::createFromComponents(static::parse($url), $registry);
    }

    /**
     * Create a new League\Url\Url instance from an array returned by
     * PHP parse_url function
     *
     * @param array                          $components
     * @param Interfaces\SchemeRegistry|null $registry
     *
     * @return Url\Url
     */
    public static function createFromComponents(array $components, Interfaces\SchemeRegistry $registry = null)
    {
        $components = array_merge([
            "scheme" => null, "user" => null, "pass"  => null, "host"     => null,
            "port"   => null, "path" => null, "query" => null, "fragment" => null,
        ], $components);

        return new Url\Url(
            new Url\Scheme($components["scheme"], $registry),
            new Url\UserInfo($components["user"], $components["pass"]),
            new Url\Host($components["host"]),
            new Url\Port($components["port"]),
            new Url\Path($components["path"]),
            new Url\Query($components["query"]),
            new Url\Fragment($components["fragment"])
        );
    }

    /**
     * Parse a string as an URL
     *
     * Parse an URL string using PHP parse_url while applying bug fixes
     *
     * @param string $url The URL to parse
     *
     * @throws InvalidArgumentException if the URL can not be parsed
     *
     * @return array
     */
    public static function parse($url)
    {
        $url               = trim($url);
        $components        = @parse_url($url);
        $defaultComponents = [
            "scheme" => null, "user" => null, "pass" => null, "host" => null,
            "port" => null, "path" => null, "query" => null, "fragment" => null,
        ];
        if (is_array($components)) {
            return array_merge($defaultComponents, $components);
        }

        $components = @parse_url(static::fixUrlScheme($url));
        if (is_array($components)) {
            unset($components['scheme']);
            return array_merge($defaultComponents, $components);
        }

        throw new InvalidArgumentException(sprintf("The given URL: `%s` could not be parse", $url));
    }

    /**
     * bug fix for unpatched PHP version
     *
     * in the following versions
     *    - PHP 5.4.7 => 5.5.24
     *    - PHP 5.6.0 => 5.6.8
     *    - HHVM all versions
     *
     * We must prepend a temporary missing scheme to allow
     * parsing with parse_url function
     *
     * @see https://bugs.php.net/bug.php?id=68917
     *
     * @param string $url The URL to parse
     *
     * @return array
     */
    protected static function fixUrlScheme($url)
    {
        static $is_bugged;

        if (is_null($is_bugged)) {
            $is_bugged = !is_array(@parse_url("//a:1"));
        }

        if (!$is_bugged || strpos($url, '/') !== 0) {
            throw new InvalidArgumentException(sprintf("The given URL: `%s` could not be parse", $url));
        }

        return 'php-bugfix-scheme:'.$url;
    }
}
