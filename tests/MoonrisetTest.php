<?php
namespace JiriJozif\Moonriset\Tests;

use PHPUnit\Framework\TestCase;
use JiriJozif\Moonriset\Moonriset;

class MoonrisetTest extends TestCase
{
    public function testCalculateGreenwich()
    {
        $mrs = new Moonriset(51.48, 0.0, "Europe/London"); //Royal Observatory, Greenwich
        $mrs->setDate(2025, 2, 17);

        $this->assertEquals(1739833560, $mrs->rise["timestamp"]);
        $this->assertEquals(1739781180, $mrs->set["timestamp"]);
        $this->assertEquals(1739762400, $mrs->transit["timestamp"]);
    }

    public function testCalculateNorthPolarCircle()
    {
        $mrs = new Moonriset(66.5, 0.0, "Etc/GMT");
        $mrs->setDate(2025, 6, 7);

        $this->assertEquals(1749320280, $mrs->rise["timestamp"]);
        $this->assertEquals(1749255660, $mrs->set["timestamp"]);
        $this->assertEquals(1749331020, $mrs->transit["timestamp"]);
        $this->assertEquals(1749340680, $mrs->set2["timestamp"]);
    }

    public function testCalculateSouthPole()
    {
        $mrs = new Moonriset(-90, 0.0, "Etc/GMT");
        $mrs->setDate(2025, 2, 17);

        $this->assertEquals(true, $mrs->rise["timestamp"]);
        $this->assertEquals(true, $mrs->set["timestamp"]);
        $this->assertEquals(1739762400, $mrs->transit["timestamp"]);
    }
}
