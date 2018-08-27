<?php

namespace Tests\Unit;

use FHALicenses\Licenses;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /** @test */
    public function it_loads()
    {
        $records = (new Licenses())->setState('AK')->all();
        
        print_r($records);
    }
}
