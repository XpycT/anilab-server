<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AnidubTest extends TestCase
{

    /**
     * Test home page
     */
    public function testHome()
    {
        $this->get('api/v1/anidub/page')
            ->seeJson([
                'status' => "success",
                'page' => 1,
            ]);
    }

    public function testPagging()
    {
        $this->get('api/v1/anidub/page/5')
            ->seeJson([
                'status' => "success",
                'page' => 5,
            ]);
    }

    public function testPaggingCategory()
    {
        $this->get('api/v1/anidub/page/4', ['path' => '/anime_tv/full/'])
            ->seeJson([
                'status' => "success",
                'page' => 4,
            ]);
    }

    public function testDescriptionPage()
    {
        $this->get('api/v1/anidub/show/9278')
            ->seeJson([
                'id' => "8",
                'movie_id' => "9278",
            ]);
    }
    public function testDescriptionPageComments()
    {
        $this->get('api/v1/anidub/show/9278/comments')
            ->seeJson([
                'id' => "8",
                'movie_id' => "9278",
                'title' => "Я ни хрена не понимаю о чем говорит мой муж ТВ-2 / Danna ga Nani o Itteiru ka Wakaranai Ken TV-2 [12 из 13]"
            ]);
    }
}
