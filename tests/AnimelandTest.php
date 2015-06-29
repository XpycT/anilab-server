<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AnimelandTest extends TestCase
{

    /**
     * Test home page
     */
    public function testHome()
    {
        $this->get('api/v1/animeland/page')
            ->seeJson([
                'status' => "success",
                'page' => 1,
            ]);
    }

    public function testPagging()
    {
        $this->get('api/v1/animeland/page/8')
            ->seeJson([
                'status' => "success",
                'page' => 8,
            ]);
    }

    public function testPaggingCategory()
    {
        $this->get('api/v1/animeland/page/2', ['path' => '/anime-sub/movie-sub/'])
            ->seeJson([
                'status' => "success",
                'page' => 2,
            ]);
    }

    public function testDescriptionPage()
    {
        $this->get('api/v1/animeland/show/37189')
            ->seeJson([
                'id' => "98",
                'movie_id' => "37189",
            ]);
    }
    public function testDescriptionPageComments()
    {
        $this->get('api/v1/animeland/show/37189/comments')
            ->seeJson([
                'id' => "98",
                'movie_id' => "37189",
                'title' => "Синтетические воспоминания / Plastic Memories (RUS)"
            ]);
    }
}
