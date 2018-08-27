<?php

namespace FHALicenses;

use Symfony\Component\DomCrawler\Crawler;
use TheIconic\NameParser\Parser as NameParser;

class Parser
{
    protected $html;
    protected $crawler;
    
    public function __construct($html)
    {
        $this->html = $html;
        $this->crawler = new Crawler($this->html);
    }

    public function rows()
    {
        return $this->crawler->filter('body > table')
            ->eq(1)
            ->filter('tr')
            ->reduce(function(Crawler $node, $i) {
                return $i > 0;
            })
            ->each(function(Crawler $node, $i) {
                return $this->parseAppraiser($node);
            });
    }

    protected function parseAppraiser(Crawler $node)
    {
        return $this->parseName($node) + [
            'license_number' => $this->parserLicenseNumber($node),
            'license_type' => $this->parseLicenseType($node),
            'expiration' => $this->parserExpiration($node),
            'company' => $this->parseCompany($node)
        ] + $this->parseAddress($node);
    }

    protected function parseCompany(Crawler $node)
    {
        // Company is available in the address column
        // if there are 2 <br> tags
        $html = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $node->filter('td')->eq(2)->filter('font')->eq(0)->html());
        $parts = explode("\n", $html);
        $hasCompany = count($parts) === 3;

        if($hasCompany && isset($parts[0])) {
            return htmlspecialchars_decode($parts[0]);
        }

        return null;
    }

    protected function parseAddress(Crawler $node)
    {
        $hasCompany = $this->parseCompany($node);

        $html = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $node->filter('td')->eq(2)->filter('font')->eq(0)->html());

        $html = htmlentities($html, null, 'utf-8');

        $parts = explode("\n", $html);

        if($hasCompany) {
            return $this->parserInternalAddress($parts[1], $parts[2]);   
        }

        return $this->parserInternalAddress($parts[0], $parts[1]);
    }

    protected function parserInternalAddress($address, $other)
    {
        list($city, $state, $zip) = explode('&nbsp;', $other);

        $zip4 = preg_replace('/(\d{5})(\d{4})/', '$2', trim($zip));
        $zip = preg_replace('/(\d{5})(\d{4})/', '$1', trim($zip));

        return [
            'address' => $address,
            'city' => trim(trim($city, ',')),
            'state' => trim($state),
            'zip' => $zip,
            'zip4' => $zip4
        ];
    }

    protected function parseLicenseType(Crawler $node)
    {
        return $node->filter('td')->eq(1)->filter('font')->eq(1)->text();
    }

    protected function parserExpiration(Crawler $node)
    {
        return $node->filter('td')->eq(1)->filter('font')->eq(2)->text();
    }

    protected function parserLicenseNumber(Crawler $node)
    {
        return trim($node->filter('td')->eq(1)->filter('font')->eq(0)->text());
    }

    protected function parseName(Crawler $node)
    {
        $fullName = $node->filter('td')->eq(0)->filter('font')->text();
        $parsed = (new NameParser)->parse($fullName);

        return [
            'firstname' => trim($parsed->getFirstname()),
            'lastname' => trim($parsed->getLastName()),
            'middlename' => trim($parsed->getInitials())
        ];
    }

    public function totalRecords()
    {
        $result = $this->crawler->filter('body > table')->eq(0)
                    ->filter('td')->eq(0)
                    ->filter('span')->eq(1)
                    ->text();

        if(!empty($result)) {
            // Get the number
            preg_match('/([\d\,]+) records were/', $result, $matches);

            return $matches[1] ?? null;
        }

        return null;
    }

    public function canContinue()
    {
        return $this->crawler->filter('body > table')->eq(3)->filter('td')->eq(1)->count() > 0;
    }
}