<?php

namespace service;

use Geocoder\Query\GeocodeQuery;
use Http\Adapter\Guzzle6\Client as HttpClient;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\StatefulGeocoder;

class geocode
{
    private $geocoder;

    public function __construct()
    {
        $httpClient = new HttpClient();
        $provider = new GoogleMaps($httpClient, 'be', getenv('GOOGLE_GEO_API_KEY'));
        $this->geocoder = new StatefulGeocoder($provider, 'nl');
        $this->geocoder->setLimit(1);
    }

    public function getCoordinates(string $adress):array
    {
        try 
        {
            $addressCollection = $this->geocoder->geocodeQuery(GeocodeQuery::create($adress));

            if (is_object($addressCollection))
            {
                $address = $addressCollection->first();

                $ary = [
                    'lat'	=> $address->getLatitude(),
                    'lng'	=> $address->getLongitude(),
                ];
        
                return $ary;
            }

            return [];
        }

        catch (Exception $e)
		{
			error_log('Geocode adr: ' . $address . ' exception: ' . $e->getMessage());
			return [];
		}
    }
}
