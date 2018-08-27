<?php

namespace FHALicenses;

use Exception;
use GuzzleHttp\Client;
use FHALicenses\Parser;
use Psr\SimpleCache\CacheInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Cache\Simple\FilesystemCache;

class Licenses
{
    const CACHE_KEY = '__fha_licenses__';

    protected $client;

    protected $location = 'https://entp.hud.gov/idapp/html/appr1.cfm';

    protected $state = '';

    protected $license = '';

    protected $lastName = '';

    protected $firstName = '';

    protected $city = '';

    protected $zip = '';

    protected $appraisers = [];

    protected $cache;
    
    protected $ttl = 3600; // 1 hour

    protected $perPage = 50;

    protected $startAt = '1';
    protected $maxRows = '50';
    
    public function __construct()
    {
        $this->setClient(new Client([
            'allow_redirects' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
                'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Referer'    => 'https://entp.hud.gov/idapp/html/appr1.cfm'
            ]
        ]))
        ->setCache(new FilesystemCache());
    }

    public function all()
    {
        if(!$this->hasQueryParameters()) {
            throw new Exception("You must specify at least one query parameter");
        }

        if($cached = $this->cached()) {
            return $cached;
        }
        
        while(true) {
            $parser = $this->query();

            $this->appraisers = array_merge($this->appraisers, $parser->rows());

            if(!$parser->canContinue()) {
                break;
            }

            $this->increment();
        }

        $this->store($this->appraisers);

        return $this->appraisers;
    }

    protected function increment()
    {
        $this->setStartAt($this->getStartAt() + $this->perPage)
            ->setMaxRows($this->getMaxRows() + $this->perPage);
    }

    protected function query()
    {
        $response = $this->client->post($this->location, ['form_params' => $this->searchFilters()]);

        $body = (string) $response->getBody();
        
        $parser = new Parser($body);

        return $parser;
    }

    protected function hasQueryParameters()
    {
        return $this->getState() 
                || $this->getLicense()
                || $this->getLastName()
                || $this->getFirstName()
                || $this->getCity()
                || $this->getZip();
    }

    protected function searchFilters()
    {
        return [
            'SORTED' => 'ad.last_name,ad.first_name',
            'STATE' => $this->getState(),
            'LICENSE' => $this->getLicense(),
            'NAME' => $this->getLastName(),
            'FNAME' => $this->getFirstName(),
            'CITY' => $this->getCity(),
            'ZIP' => $this->getZip(),
            'STATUSCODE' => '0',
            'AQB_INDIC' => 'Y',
            'IN_FHA' => 'Yes',
            'startAt' => $this->getStartAt(),
            'maxRows' => $this->getMaxRows()
        ];
    }

    protected function getCacheKey()
    {
        $filters = $this->searchFilters();

        unset($filters['startAt'], $filters['maxRows']);

        return sha1(static::CACHE_KEY . implode('_', $filters));
    }

    protected function store(array $rows)
    {
        $this->cache->set($this->getCacheKey(), $rows);
    }

    protected function cached()
    {
        return $this->cache->get($this->getCacheKey());
    }

    public function isCached()
    {
        return $this->cache->has($this->getCacheKey());
    }

    /**
     * Get the value of client
     */ 
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Set the value of client
     *
     * @return  self
     */ 
    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Get the value of location
     */ 
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set the value of location
     *
     * @return  self
     */ 
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get the value of state
     */ 
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set the value of state
     *
     * @return  self
     */ 
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get the value of license
     */ 
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * Set the value of license
     *
     * @return  self
     */ 
    public function setLicense($license)
    {
        $this->license = $license;

        return $this;
    }

    /**
     * Get the value of lastName
     */ 
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set the value of lastName
     *
     * @return  self
     */ 
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get the value of firstName
     */ 
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set the value of firstName
     *
     * @return  self
     */ 
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get the value of city
     */ 
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set the value of city
     *
     * @return  self
     */ 
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get the value of zip
     */ 
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * Set the value of zip
     *
     * @return  self
     */ 
    public function setZip($zip)
    {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get the value of startAt
     */ 
    public function getStartAt()
    {
        return $this->startAt;
    }

    /**
     * Set the value of startAt
     *
     * @return  self
     */ 
    public function setStartAt($startAt)
    {
        $this->startAt = $startAt;

        return $this;
    }

    /**
     * Get the value of maxRows
     */ 
    public function getMaxRows()
    {
        return $this->maxRows;
    }

    /**
     * Set the value of maxRows
     *
     * @return  self
     */ 
    public function setMaxRows($maxRows)
    {
        $this->maxRows = $maxRows;

        return $this;
    }

    /**
     * Get the value of cache
     */ 
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Set the value of cache
     *
     * @return  self
     */ 
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Get the value of ttl
     */ 
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * Set the value of ttl
     *
     * @return  self
     */ 
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }
}