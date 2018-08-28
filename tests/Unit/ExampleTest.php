<?php

namespace Tests\Unit;

use FHALicenses\Licenses;
use PHPUnit\Framework\TestCase;
use TheIconic\NameParser\Parser as NameParser;

class ExampleTest extends TestCase
{
    /** @test */
    public function it_loads()
    {
        $records = (new Licenses())->setState('AK')->all();
        
        $this->assertNotEmpty($records);
    }

    /** @test */
    public function it_can_parse_saluation_at_end()
    {
        $parsed = (new NameParser)->parse('PAUL M LEWIS MR');
        
        $this->assertEquals('Paul', $parsed->getFirstName());
        $this->assertEquals('Lewis', $parsed->getLastName());
    }

    /** @test */
    public function it_can_parse_saluation_lastname()
    {
        $parsed = (new NameParser)->parse('SUJAN MASTER');

        $this->assertEquals('Sujan', $parsed->getFirstName());
        $this->assertEquals('Master', $parsed->getLastName());
    }

    /** @test */
    public function it_can_parse_saluation_lastname_with_middlename()
    {
        $parsed = (new NameParser)->parse('JAMES J MA');
        
        $this->assertEquals('James', $parsed->getFirstName());
        $this->assertEquals('Ma', $parsed->getLastName());
    }
}
