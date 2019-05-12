<?php

namespace Tests\Feature;

use App\Reply;
use App\Thread;
use App\Reputation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReputationTest extends TestCase
{
    use RefreshDatabase;

    protected $points = [];

    /**
     * Fetch current reputation points on class initialization.
     */
    public function setUp()
    {
        parent::setUp();
        $this->points = config('council.reputation');
    }

    /** @test */
    public function a_user_gains_points_when_they_create_a_thread()
    {
        $thread = create(Thread::class);

        $this->assertEquals($this->points['thread_published'], $thread->creator->reputation);
    }


    /** @test */
    public function a_user_gains_points_when_they_reply_to_a_thread()
    {
        $thread = create(Thread::class);

        $reply = $thread->addReply([
            'user_id' => create(\App\User::class)->id,
            'body' => 'Here is a reply.'
        ]);

        $this->assertEquals($this->points['reply_posted'], $reply->owner->reputation);
    }


    /** @test */
    public function a_user_gains_points_when_their_reply_is_marked_as_best()
    {
        $thread = create(Thread::class);

        $thread->markBestReply($reply = $thread->addReply([
            'user_id' => create(\App\User::class)->id,
            'body' => 'Here is a reply.'
        ]));

        $total = $this->points['reply_posted'] + $this->points['best_reply_awarded'];
        $this->assertEquals($total, $reply->owner->reputation);
    }

    /** @test */
    public function a_user_gains_points_when_their_reply_is_favorited()
    {
        // Given we have a signed in user, John.
        $this->signIn($john = create(\App\User::class));

        // And also Jane...
        $jane = create(\App\User::class);

        // If Jane adds a new reply to a thread...
        $reply = create(Thread::class)->addReply([
            'user_id' => $jane->id,
            'body' => 'Some reply'
        ]);

        // And John favorites that reply.
        $this->post(route('replies.favorite', $reply));

        // Then, Jane's reputation should grow, accordingly.
        $this->assertEquals(
            $this->points['reply_posted'] + $this->points['reply_favorited'],
            $jane->fresh()->reputation
        );

        // While John's should remain unaffected.
        $this->assertEquals(0, $john->reputation);
    }

}
