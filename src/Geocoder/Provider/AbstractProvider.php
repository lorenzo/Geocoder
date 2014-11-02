<?php

/**
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider;

use Geocoder\Geocoder;
use Geocoder\Model\AddressFactory;
use Ivory\HttpAdapter\HttpAdapterInterface;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
abstract class AbstractProvider
{
    /**
     * @var HttpAdapterInterface
     */
    private $adapter;

    /**
     * @var AddressFactory
     */
    private $factory;

    /**
     * @var integer
     */
    private $limit = Provider::MAX_RESULTS;

    /**
     * @param HttpAdapterInterface $adapter An HTTP adapter
     */
    public function __construct(HttpAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->factory = new AddressFactory();
    }

    /**
     * {@inheritDoc}
     */
    public function limit($limit)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Returns the HTTP adapter.
     *
     * @return HttpAdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Returns the default results.
     *
     * @return array
     */
    protected function getDefaults()
    {
        return [
            'latitude'     => null,
            'longitude'    => null,
            'bounds'       => null,
            'streetNumber' => null,
            'streetName'   => null,
            'locality'     => null,
            'postalCode'   => null,
            'subLocality'  => null,
            'county'       => null,
            'countyCode'   => null,
            'region'       => null,
            'regionCode'   => null,
            'country'      => null,
            'countryCode'  => null,
            'timezone'     => null,
        ];
    }

    /**
     * Returns the results for the 'localhost' special case.
     *
     * @return array
     */
    protected function getLocalhostDefaults()
    {
        return [
            'locality' => 'localhost',
            'region'   => 'localhost',
            'county'   => 'localhost',
            'country'  => 'localhost',
        ];
    }

    /**
     * @param array $data An array of data.
     *
     * @return \Geocoder\Model\Address[]
     */
    protected function returnResults(array $data = [])
    {
        if (0 < $this->getLimit()) {
            $data = array_slice($data, 0, $this->getLimit());
        }

        return $this->factory->createFromArray($data);
    }
}
