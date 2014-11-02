<?php

/**
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider;

use Geocoder\Exception\InvalidCredentials;
use Geocoder\Exception\NoResult;
use Geocoder\Exception\UnsupportedOperation;
use Ivory\HttpAdapter\HttpAdapterInterface;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class TomTom extends AbstractProvider implements LocaleAwareProvider
{
    /**
     * @var string
     */
    const GEOCODE_ENDPOINT_URL = 'https://api.tomtom.com/lbs/geocoding/geocode?key=%s&query=%s&maxResults=%d';

    /**
     * @var string
     */
    const REVERSE_ENDPOINT_URL = 'https://api.tomtom.com/lbs/services/reverseGeocode/3/xml?key=%s&point=%F,%F';

    /**
     * @var string
     */
    private $apiKey = null;

    /**
     * @param HttpAdapterInterface $adapter An HTTP adapter.
     * @param string               $apiKey  An API key.
     * @param string               $locale  A locale (optional).
     */
    public function __construct(HttpAdapterInterface $adapter, $apiKey, $locale = null)
    {
        parent::__construct($adapter, $locale);

        $this->apiKey = $apiKey;
    }

    /**
     * {@inheritDoc}
     */
    public function geocode($address)
    {
        if (null === $this->apiKey) {
            throw new InvalidCredentials('No Geocoding API Key provided');
        }

        // This API doesn't handle IPs
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The TomTom does not support IP addresses.');
        }

        $query = sprintf(self::GEOCODE_ENDPOINT_URL, $this->apiKey, rawurlencode($address), $this->getLimit());

        return $this->executeQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function reverse($latitude, $longitude)
    {
        if (null === $this->apiKey) {
            throw new InvalidCredentials('No Map API Key provided');
        }

        $query = sprintf(self::REVERSE_ENDPOINT_URL, $this->apiKey, $coordinates[0], $coordinates[1]);

        return $this->executeQuery($query);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'tomtom';
    }

    /**
     * @param string $query
     *
     * @return array
     */
    private function executeQuery($query)
    {
        if (null !== $this->getLocale()) {
            // Supported 2- character values are de, en, es, fr, it, nl, pl, pt, and sv.
            // Equivalent 3-character values are GER, ENG, SPA, FRE, ITA, DUT, POL, POR, and SWE.
            $query = sprintf('%s&language=%s', $query, substr($this->getLocale(), 0, 2));
        }

        $content = (string) $this->getAdapter()->get($query)->getBody();

        try {
            $xml = new \SimpleXmlElement($content);
        } catch (\Exception $e) {
            throw new NoResult(sprintf('Could not execute query %s', $query));
        }

        $attributes = $xml->attributes();

        if (isset($attributes['count']) && 0 === (int) $attributes['count']) {
            throw new NoResult(sprintf('Could not execute query %s', $query));
        }

        if (isset($attributes['errorCode'])) {
            if ('403' === (string) $attributes['errorCode']) {
                throw new InvalidCredentials('Map API Key provided is not valid.');
            }

            throw new NoResult(sprintf('Could not execute query %s', $query));
        }

        $data = isset($xml->geoResult) ? $xml->geoResult : $xml->reverseGeoResult;


        if (0 === count($data)) {
            return array($this->getResultArray($data));
        }

        $results = array();

        foreach ($data as $item) {
            $results[] = $this->getResultArray($item);
        }

        return $results;
    }

    /**
     * @param \SimpleXmlElement $data
     *
     * @return array
     */
    private function getResultArray(\SimpleXmlElement $data)
    {
        return array_merge($this->getDefaults(), array(
            'latitude'    => isset($data->latitude) ? (double) $data->latitude : null,
            'longitude'   => isset($data->longitude) ? (double) $data->longitude : null,
            'streetName'  => isset($data->street) ? (string) $data->street : null,
            'locality'    => isset($data->city) ? (string) $data->city : null,
            'region'      => isset($data->state) ? (string) $data->state : null,
            'country'     => isset($data->country) ? (string) $data->country : null,
            'countryCode' => isset($data->countryISO3) ? (string) $data->countryISO3 : null,
        ));
    }
}
