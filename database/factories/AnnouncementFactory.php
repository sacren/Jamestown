<?php

namespace Database\Factories;

use App\Enums\AnnouncementAudience;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Announcement> */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'body' => fake()->paragraphs(2, true),
            'audience' => AnnouncementAudience::All,
            'published_at' => now(),
            'author_id' => UserFactory::new(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published_at' => null]);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => ['published_at' => now()->addDays(7)]);
    }

    public function forAuthor(User $author): static
    {
        return $this->state(fn () => ['author_id' => $author->id]);
    }

    public function forAudience(AnnouncementAudience $audience): static
    {
        return $this->state(fn () => ['audience' => $audience]);
    }
}
