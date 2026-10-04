<?php

namespace Tests\Feature;

use App\Models\Keyword;
use App\Models\KeywordGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeywordGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_a_group_replaces_its_keywords(): void
    {
        $user = User::factory()->create();
        [$kept, $removed, $added] = collect(['kept', 'removed', 'added'])
            ->map(fn ($label) => Keyword::create(['label' => $label]));

        $group = KeywordGroup::create(['label' => 'Group']);
        $group->keywords()->attach([$kept->id, $removed->id]);

        $this->actingAs($user)
            ->put(route('dashboard.keyword-groups.update', $group), [
                'label' => 'Group',
                'keyword_ids' => [$kept->id, $added->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            [$kept->id, $added->id],
            $group->keywords()->pluck('keywords.id')->all()
        );
    }

    public function test_updating_a_group_with_the_same_keywords_does_not_duplicate_them(): void
    {
        $user = User::factory()->create();
        $keyword = Keyword::create(['label' => 'keyword']);

        $group = KeywordGroup::create(['label' => 'Group']);
        $group->keywords()->attach($keyword->id);

        $this->actingAs($user)
            ->put(route('dashboard.keyword-groups.update', $group), [
                'label' => 'Group',
                'keyword_ids' => [$keyword->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $group->keywords()->count());
    }
}
